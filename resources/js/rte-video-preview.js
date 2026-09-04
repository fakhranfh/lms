/**
 * Click delegation + preview modal for video chips rendered inside static
 * (non-editable) rich text output — e.g. the syllabus read-only view. The
 * editor itself (rich-text-editor.js) has its own copy of this behavior
 * since it manages its own contenteditable surface; this one covers plain
 * {!! !!} HTML blocks that aren't wrapped in an editor instance.
 */
export default () => ({
    videoPreviewUrl: null,
    filePreviewUrl: null,
    filePreviewName: null,

    onContentClick(event) {
        const videoChip = event.target.closest('[data-video-preview]');

        if (videoChip) {
            event.preventDefault();
            this.videoPreviewUrl = videoChip.dataset.videoPreview;

            return;
        }

        const fileChip = event.target.closest('[data-file-preview]');

        if (fileChip) {
            event.preventDefault();
            this.filePreviewUrl = fileChip.dataset.filePreview;
            this.filePreviewName = fileChip.dataset.filePreviewName;

            return;
        }

        // Chips saved before file previews existed still carry a plain
        // href + target="_blank" instead of data-file-preview.
        const legacyChip = event.target.closest('a.rte-file-chip');
        const legacyHref = legacyChip?.getAttribute('href');

        if (legacyChip && legacyHref && legacyHref !== '#') {
            event.preventDefault();
            this.filePreviewUrl = legacyChip.href;
            this.filePreviewName = legacyChip.querySelector('.rte-file-chip-name')?.textContent || '';
        }
    },

    closeVideoPreview() {
        this.videoPreviewUrl = null;
    },

    closeFilePreview() {
        this.filePreviewUrl = null;
        this.filePreviewName = null;
    },
});
