/**
 * Click delegation + preview modal for video chips rendered inside static
 * (non-editable) rich text output — e.g. the syllabus read-only view. The
 * editor itself (rich-text-editor.js) has its own copy of this behavior
 * since it manages its own contenteditable surface; this one covers plain
 * {!! !!} HTML blocks that aren't wrapped in an editor instance.
 */
export default () => ({
    videoPreviewUrl: null,

    onContentClick(event) {
        const chip = event.target.closest('[data-video-preview]');

        if (chip) {
            event.preventDefault();
            this.videoPreviewUrl = chip.dataset.videoPreview;
        }
    },

    closeVideoPreview() {
        this.videoPreviewUrl = null;
    },
});
