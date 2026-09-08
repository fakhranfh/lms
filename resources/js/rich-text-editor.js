export default (initialValue, wireModel, id, disabled = false, allowAttachments = true, allowLinks = true, allowVideo = false, allowAudio = false) => ({
    id,
    disabled,
    allowAttachments,
    allowLinks,
    allowVideo,
    allowAudio,
    uploading: false,
    videoPreviewUrl: null,
    filePreviewUrl: null,
    filePreviewName: null,
    active: {
        bold: false,
        italic: false,
        underline: false,
        ul: false,
        ol: false,
        blockquote: false,
        h1: false,
        h2: false,
    },

    trackedAttachments: [],

    init() {
        this.$refs.editor.innerHTML = initialValue || '';
        this.trackedAttachments = this.extractAttachments(this.$refs.editor.innerHTML);

        this.$wire.$watch(wireModel, (value) => {
            // The watch callback can fire after the row containing this
            // editor has already been removed from the DOM (e.g. a syllabus
            // "remove row" button nulls the whole item client-side, which
            // this editor's own path is nested under) — bail out rather
            // than dereference a $ref that no longer exists.
            if (!this.$refs.editor) {
                return;
            }

            if (!value && document.activeElement !== this.$refs.editor) {
                this.$refs.editor.innerHTML = '';
                this.trackedAttachments = [];
            }
        });
    },

    extractAttachments(html) {
        const doc = new DOMParser().parseFromString(html || '', 'text/html');
        const urls = [];

        doc.querySelectorAll('img[src]').forEach((img) => {
            const src = img.getAttribute('src');
            if (src && src.includes('forum-attachments')) {
                urls.push(src);
            }
        });

        doc.querySelectorAll('a[href]').forEach((a) => {
            const href = a.getAttribute('href');
            if (href && href.includes('forum-attachments')) {
                urls.push(href);
            }
        });

        return urls;
    },

    syncAttachments() {
        const current = this.extractAttachments(this.$refs.editor.innerHTML);
        const removed = this.trackedAttachments.filter((url) => !current.includes(url));

        removed.forEach((url) => {
            this.$wire.call('deleteRichTextAttachment', url);
        });

        this.trackedAttachments = current;
    },

    exec(command, value = null) {
        if (this.disabled) {
            return;
        }

        this.$refs.editor.focus();
        document.execCommand(command, false, value);
        this.onInput();
        this.updateActiveStates();
    },

    toggleBlock(tag) {
        const current = document.queryCommandValue('formatBlock').toLowerCase();
        this.exec('formatBlock', current === tag.toLowerCase() ? 'p' : tag);
    },

    insertLink() {
        if (this.disabled || !this.allowLinks) {
            return;
        }

        const url = window.prompt('Masukkan URL tautan');

        if (url) {
            this.exec('createLink', url);
        }
    },

    onInput() {
        if (this.disabled) {
            return;
        }

        this.syncAttachments();
        this.$wire.set(wireModel, this.$refs.editor.innerHTML, false);
    },

    updateActiveStates() {
        this.active.bold = document.queryCommandState('bold');
        this.active.italic = document.queryCommandState('italic');
        this.active.underline = document.queryCommandState('underline');
        this.active.ul = document.queryCommandState('insertUnorderedList');
        this.active.ol = document.queryCommandState('insertOrderedList');

        const block = document.queryCommandValue('formatBlock').toLowerCase();
        this.active.blockquote = block === 'blockquote';
        this.active.h1 = block === 'h1';
        this.active.h2 = block === 'h2';
    },

    clear() {
        this.$refs.editor.innerHTML = '';
        this.trackedAttachments = [];
        this.$wire.set(wireModel, '', false);
    },

    setContent(value) {
        this.$refs.editor.innerHTML = value || '';
        this.trackedAttachments = this.extractAttachments(this.$refs.editor.innerHTML);
        this.$wire.set(wireModel, value || '');
    },

    onContentClick(event) {
        const videoChip = event.target.closest('[data-video-preview]');

        if (videoChip) {
            event.preventDefault();
            this.openVideoPreview(videoChip.dataset.videoPreview);

            return;
        }

        const fileChip = event.target.closest('[data-file-preview]');

        if (fileChip) {
            event.preventDefault();
            this.openFilePreview(fileChip.dataset.filePreview, fileChip.dataset.filePreviewName);

            return;
        }

        // Chips saved before file previews existed still carry a plain
        // href + target="_blank" instead of data-file-preview.
        const legacyChip = event.target.closest('a.rte-file-chip');
        const legacyHref = legacyChip?.getAttribute('href');

        if (legacyChip && legacyHref && legacyHref !== '#') {
            event.preventDefault();
            const name = legacyChip.querySelector('.rte-file-chip-name')?.textContent || '';
            this.openFilePreview(legacyChip.href, name);
        }
    },

    openVideoPreview(url) {
        this.videoPreviewUrl = url;
    },

    closeVideoPreview() {
        this.videoPreviewUrl = null;
    },

    openFilePreview(url, name) {
        this.filePreviewUrl = url;
        this.filePreviewName = name;
    },

    closeFilePreview() {
        this.filePreviewUrl = null;
        this.filePreviewName = null;
    },

    onPaste(event) {
        event.preventDefault();

        if (this.disabled) {
            return;
        }

        const text = (event.clipboardData || window.clipboardData).getData('text/plain');
        document.execCommand('insertText', false, text);
        this.onInput();
    },

    insertTable(rows, cols) {
        if (this.disabled) {
            return;
        }

        rows = parseInt(rows, 10);
        cols = parseInt(cols, 10);

        if (!rows || !cols || rows < 1 || cols < 1) {
            return;
        }

        const cell = '<td>&nbsp;</td>';
        const row = `<tr>${cell.repeat(cols)}</tr>`;
        const table = `<table><tbody>${row.repeat(rows)}</tbody></table><p></p>`;

        this.exec('insertHTML', table);
    },

    generateUuid() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID();
        }

        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
            const r = (Math.random() * 16) | 0;
            const v = c === 'x' ? r : (r & 0x3) | 0x8;
            return v.toString(16);
        });
    },

    escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value;
        return div.innerHTML;
    },

    formatFileSize(bytes) {
        if (!bytes && bytes !== 0) {
            return '';
        }

        const units = ['B', 'KB', 'MB', 'GB'];
        let size = bytes;
        let unitIndex = 0;

        while (size >= 1024 && unitIndex < units.length - 1) {
            size /= 1024;
            unitIndex += 1;
        }

        return `${unitIndex === 0 ? size : size.toFixed(1)} ${units[unitIndex]}`;
    },

    badgeForFile(name) {
        const extension = (name.split('.').pop() || '').toLowerCase();

        const badges = {
            pdf: { label: 'PDF', type: 'pdf' },
            zip: { label: 'ZIP', type: 'zip' },
            doc: { label: 'DOC', type: 'doc' },
            docx: { label: 'DOC', type: 'doc' },
            xls: { label: 'XLS', type: 'xls' },
            xlsx: { label: 'XLS', type: 'xls' },
            ppt: { label: 'PPT', type: 'ppt' },
            pptx: { label: 'PPT', type: 'ppt' },
            mp3: { label: 'AUD', type: 'audio' },
            wav: { label: 'AUD', type: 'audio' },
            ogg: { label: 'AUD', type: 'audio' },
            m4a: { label: 'AUD', type: 'audio' },
            aac: { label: 'AUD', type: 'audio' },
            flac: { label: 'AUD', type: 'audio' },
        };

        return badges[extension] || { label: extension.slice(0, 4).toUpperCase() || 'FILE', type: 'generic' };
    },

    buildSkeletonChip(placeholderId) {
        return `<span id="${placeholderId}" contenteditable="false" class="rte-file-chip rte-file-chip--skeleton">`
            + '<span class="rte-file-chip-icon rte-file-chip-icon--skeleton"></span>'
            + '<span class="rte-file-chip-info">'
            + '<span class="rte-file-chip-name rte-file-chip-name--skeleton"></span>'
            + '<span class="rte-file-chip-size rte-file-chip-size--skeleton"></span>'
            + '</span>'
            + '</span>&nbsp;';
    },

    buildFileChip(url, file) {
        const uploadedName = decodeURIComponent(url.split('/').pop().split('?')[0]);
        const isVideo = file.type.startsWith('video/');
        const badge = isVideo ? { label: 'VID', type: 'video' } : this.badgeForFile(uploadedName);
        const size = this.formatFileSize(file.size);
        const name = this.escapeHtml(uploadedName);

        // File chips open a preview modal instead of navigating away, so
        // they skip target="_blank" in favor of a data attribute the
        // editor's click handler (onContentClick) picks up.
        const linkAttrs = isVideo
            ? `href="#" data-video-preview="${this.escapeHtml(url)}"`
            : `href="#" data-file-preview="${this.escapeHtml(url)}" data-file-preview-name="${name}"`;

        return `<a ${linkAttrs} contenteditable="false" class="rte-file-chip">`
            + `<span class="rte-file-chip-icon rte-file-chip-icon--${badge.type}">${badge.label}</span>`
            + '<span class="rte-file-chip-info">'
            + `<span class="rte-file-chip-name">${name}</span>`
            + `<span class="rte-file-chip-size">${size}</span>`
            + '</span>'
            + '</a>';
    },

    triggerFilePicker() {
        if (this.disabled || !this.allowAttachments) {
            return;
        }

        this.$refs.fileInput.click();
    },

    uploadFile(event) {
        const file = event.target.files[0];

        if (!file || this.disabled || !this.allowAttachments) {
            return;
        }

        // The file input's accept attribute already keeps video/audio out of
        // the native picker here; this only guards against a file dragged in
        // or otherwise selected outside that picker.
        if (file.type.startsWith('video/') && !this.allowVideo) {
            event.target.value = '';

            return;
        }

        if (file.type.startsWith('audio/') && !this.allowAudio) {
            event.target.value = '';

            return;
        }

        this.uploading = true;

        const extension = (file.name.split('.').pop() || '').toLowerCase();
        const uuid = this.generateUuid();
        const uuidName = extension ? `${uuid}.${extension}` : uuid;
        const uploadFile = new File([file], uuidName, { type: file.type });

        const placeholderId = `rte-upload-${uuid}`;
        this.exec('insertHTML', this.buildSkeletonChip(placeholderId));

        this.$wire.upload(
            'pendingRichTextFile',
            uploadFile,
            () => {
                this.$wire.call('insertRichTextFile').then((url) => {
                    const placeholder = this.$refs.editor.querySelector(`#${placeholderId}`);

                    if (placeholder) {
                        if (file.type.startsWith('image/')) {
                            const img = document.createElement('img');
                            img.src = url;
                            placeholder.replaceWith(img);
                        } else {
                            const wrapper = document.createElement('div');
                            wrapper.innerHTML = this.buildFileChip(url, uploadFile);
                            placeholder.replaceWith(wrapper.firstElementChild);
                        }
                        this.onInput();
                    }

                    this.uploading = false;
                    event.target.value = '';
                });
            },
            () => {
                const placeholder = this.$refs.editor.querySelector(`#${placeholderId}`);

                if (placeholder) {
                    placeholder.remove();
                    this.onInput();
                }

                this.uploading = false;
                event.target.value = '';
                window.alert('Gagal mengunggah file.');
            }
        );
    },
});
