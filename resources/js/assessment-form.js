import { removeSyllabusRow, setSyllabusRadio } from './syllabus-form';

/**
 * Adds a new empty option to a quiz question, cloned from the form's
 * #option-template (a server-rendered <x-rich-text-editor> row with
 * __QINDEX__/__OPTINDEX__ placeholders) so the option's editor gets the same
 * markup Blade would produce — hand-building the rich text editor's internals
 * in JS would drift from resources/views/components/rich-text-editor.blade.php.
 * Options are nested one level inside questions, and the <template>-clone
 * approach addSyllabusRow uses only substitutes a single index per clone (see
 * its own docblock) — reusing it here would collide the option's index with
 * the parent question's index for any question added client-side, so this
 * clones its own template and substitutes both indexes instead. Works the
 * same whether the question was rendered by the server or added moments ago
 * via addSyllabusRow.
 */
export function addQuizOption($wire, questionIndex, containerEl) {
    if (!containerEl) {
        return;
    }

    const template = document.getElementById('option-template');
    if (!template) {
        return;
    }

    const arrayPath = `questions.${questionIndex}.options`;
    const options = $wire.get(arrayPath) || [];
    const optionIndex = options.length;

    $wire.set(`${arrayPath}.${optionIndex}`, { id: null, label: '', isCorrect: false }, false);

    let html = template.innerHTML;
    html = html.replaceAll('__QINDEX__', questionIndex).replaceAll('__OPTINDEX__', optionIndex);

    const wrapper = document.createElement('div');
    wrapper.innerHTML = html.trim();
    const row = wrapper.firstElementChild;

    row.querySelector('[data-option-correct]').addEventListener('change', () => {
        setSyllabusRadio($wire, arrayPath, optionIndex);
    });
    row.querySelector('[data-remove-option]').addEventListener('click', () => {
        removeSyllabusRow($wire, `${arrayPath}.${optionIndex}`, row);
    });

    containerEl.appendChild(row);
}

/**
 * Shows the multiple-choice options fields or the essay points field on a
 * final exam question row, based on which question-type radio was just
 * changed. Purely a DOM visibility toggle (both field sets are always
 * present in the row's markup) so it works the same for server-rendered
 * rows and rows cloned client-side via addSyllabusRow, without needing a
 * Livewire round trip to react to the type change. Switching a row to
 * multiple choice seeds two empty options (deferred, no round trip) so
 * there is something for "Add Option"/the correct-answer radios to build on.
 */
export function toggleFinalExamQuestionType($wire, questionIndex, radioEl) {
    const row = radioEl.closest('[data-question-row]');
    if (!row) {
        return;
    }

    const isMultipleChoice = radioEl.value === 'multiple_choice';
    row.querySelector('[data-mc-fields]')?.classList.toggle('hidden', !isMultipleChoice);
    row.querySelector('[data-essay-fields]')?.classList.toggle('hidden', isMultipleChoice);

    if (isMultipleChoice) {
        const options = $wire.get(`questions.${questionIndex}.options`) || [];
        if (options.length < 2) {
            $wire.set(`questions.${questionIndex}.options`, [
                { id: null, label: '', isCorrect: false },
                { id: null, label: '', isCorrect: false },
            ], false);
        }
    }
}

/**
 * Take-home final exams are a single essay prompt, not a full question set.
 * Purely client-side (deferred $wire.set, no round trip): collapses the
 * questions array down to its first entry forced to essay, drops any of its
 * options, removes every extra question row from the DOM, and hides the
 * per-question "Question Type" picker and Remove button plus the "Add
 * Question" button — there's nothing left to add or reclassify while
 * take-home is selected. Switching to another exam type just reveals those
 * controls again; the single remaining question is left as-is.
 */
export function toggleFinalExamType($wire, radioEl) {
    const form = radioEl.closest('form');
    if (!form) {
        return;
    }

    const isTakeHome = radioEl.value === 'take_home';

    form.querySelectorAll('[data-take-home-hide]').forEach((el) => {
        el.classList.toggle('hidden', isTakeHome);
    });
    form.querySelectorAll('[data-take-home-only]').forEach((el) => {
        el.classList.toggle('hidden', !isTakeHome);
    });

    if (!isTakeHome) {
        return;
    }

    const questions = $wire.get('questions') || [];
    const first = questions.find((question) => question !== null) || { id: null, description: '', questionType: 'essay', points: '', options: [] };

    $wire.set('questions', [{ ...first, questionType: 'essay', options: [], order: 1 }], false);

    const rows = Array.from(form.querySelectorAll('[data-question-row]'));
    rows.slice(1).forEach((row) => row.remove());

    const firstRow = rows[0];
    if (firstRow) {
        firstRow.querySelector('[data-mc-fields]')?.classList.add('hidden');
        firstRow.querySelector('[data-essay-fields]')?.classList.remove('hidden');
        firstRow.querySelectorAll('input[type="radio"][value="essay"], input[type="radio"][value="multiple_choice"]').forEach((radio) => {
            radio.checked = radio.value === 'essay';
        });
    }
}
