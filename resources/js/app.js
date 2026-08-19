import richTextEditor from './rich-text-editor';
import headMovementTest from './head-movement-test';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('richTextEditor', richTextEditor);
    window.Alpine.data('headMovementTest', headMovementTest);
});
