import richTextEditor from './rich-text-editor';
import headMovementTest from './head-movement-test';
import { createReadingDetector } from './reading-detector';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('richTextEditor', richTextEditor);
    window.Alpine.data('headMovementTest', headMovementTest);
});

// Exposed globally so the proctor exam runner (an inline, non-Alpine.data x-data
// literal in resources/views/livewire/courses/proctor-exam-show.blade.php) can use it.
window.createReadingDetector = createReadingDetector;
