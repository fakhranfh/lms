import richTextEditor from './rich-text-editor';
import rteVideoPreview from './rte-video-preview';
import headMovementTest from './head-movement-test';
import materialPicker from './material-picker';
import { createReadingDetector } from './reading-detector';
import { addQuizOption, addSyllabusRow, removeSyllabusRow, setSyllabusRadio, toggleFinalExamQuestionType, toggleWireArrayValue } from './syllabus-form';
import { scrollToFirstFormError, validateAssessmentQuestionsTotal, validateQuizForm } from './form-error-scroll';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('richTextEditor', richTextEditor);
    window.Alpine.data('rteVideoPreview', rteVideoPreview);
    window.Alpine.data('headMovementTest', headMovementTest);
    window.Alpine.data('materialPicker', materialPicker);
});

// Exposed globally so the proctor exam runner (an inline, non-Alpine.data x-data
// literal in resources/views/livewire/courses/proctor-exam-show.blade.php) can use it.
window.createReadingDetector = createReadingDetector;

// Exposed globally so the syllabus form's "Add" buttons (inline @click, not
// Alpine.data components) can clone a <template> row without a server call.
window.addSyllabusRow = addSyllabusRow;
window.removeSyllabusRow = removeSyllabusRow;
window.setSyllabusRadio = setSyllabusRadio;
window.addQuizOption = addQuizOption;
window.toggleFinalExamQuestionType = toggleFinalExamQuestionType;
window.toggleWireArrayValue = toggleWireArrayValue;

// Exposed globally so forms can jump to their first invalid field on a
// failed submit (inline @window event handlers, not Alpine.data components).
window.scrollToFirstFormError = scrollToFirstFormError;
window.validateAssessmentQuestionsTotal = validateAssessmentQuestionsTotal;
window.validateQuizForm = validateQuizForm;
