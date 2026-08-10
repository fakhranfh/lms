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

        this.$wire.upload(
            'pendingRichTextFile',
            file,
            () => {
                this.$wire.call('insertRichTextFile').then((url) => {
                    if (file.type.startsWith('image/')) {
                        this.exec('insertImage', url);
                    } else {
                        this.exec('insertHTML', `<a href="${url}" target="_blank" rel="noopener">${file.name}</a>`);
                    }
                    this.uploading = false;
                    event.target.value = '';
                });
            },
            () => {
                this.uploading = false;
                event.target.value = '';
                window.alert('Gagal mengunggah file.');
            }
        );
    },
});
