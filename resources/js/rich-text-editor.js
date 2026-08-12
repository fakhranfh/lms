export default (initialValue, wireModel, id, disabled = false) => ({
    id,
    disabled,
    uploading: false,
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
        if (this.disabled) {
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
        const badge = this.badgeForFile(uploadedName);
        const size = this.formatFileSize(file.size);
        const name = this.escapeHtml(uploadedName);

        return `<a href="${url}" target="_blank" rel="noopener" contenteditable="false" class="rte-file-chip">`
            + `<span class="rte-file-chip-icon rte-file-chip-icon--${badge.type}">${badge.label}</span>`
            + '<span class="rte-file-chip-info">'
            + `<span class="rte-file-chip-name">${name}</span>`
            + `<span class="rte-file-chip-size">${size}</span>`
            + '</span>'
            + '</a>';
    },

    triggerFilePicker() {
        if (this.disabled) {
            return;
        }

        this.$refs.fileInput.click();
    },

    uploadFile(event) {
        const file = event.target.files[0];

        if (!file || this.disabled) {
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
