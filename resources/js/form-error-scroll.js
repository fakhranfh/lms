/**
 * Client-side gate for the "questions must total 100 points" rule (see
 * AssessmentForm::persist()) so an obviously-wrong total is caught before a
 * request is sent, instead of only after a Livewire round trip. Points
 * inputs are read straight from the DOM rather than $wire.get(), since
 * wire:model (non-.live) only syncs to the component on the next request.
 * Server-side validation still re-checks the same rule as the source of truth.
 */
export function validateAssessmentQuestionsTotal(formEl) {
    const pointsInputs = formEl.querySelectorAll('[data-question-points]');
    const errorBox = formEl.querySelector('#questions-total-error');

    if (pointsInputs.length === 0) {
        return true;
    }

    const total = Array.from(pointsInputs).reduce((sum, input) => sum + (parseFloat(input.value) || 0), 0);

    if (Math.abs(total - 100) > 0.001) {
        errorBox?.classList.remove('hidden');
        errorBox?.setAttribute('data-field-error', '');

        return false;
    }

    errorBox?.classList.add('hidden');
    errorBox?.removeAttribute('data-field-error');

    return true;
}

/**
 * Client-side gate for the quiz form (see assessment-quiz-form.blade.php),
 * catching obviously-invalid input (empty title/session/description, fewer
 * than two options, no option marked correct) before a request is sent.
 * Questions are not individually weighted — points are split evenly across
 * them server-side, so there is nothing to validate there. Server-side
 * validation still re-checks everything as the source of truth. Question
 * descriptions live in a wire:ignore rich text editor, so they're read
 * straight from its contenteditable DOM.
 */
export function validateQuizForm(formEl) {
    let valid = true;

    const markField = (wrapper, message) => {
        if (!wrapper) {
            return;
        }

        wrapper.setAttribute('data-field-error', '');
        const messageEl = wrapper.querySelector('[data-js-error]');
        if (messageEl) {
            messageEl.textContent = message;
            messageEl.classList.remove('hidden');
        }
        wrapper.querySelector('input, select, textarea, .rte-content')?.classList.add('border-error', 'ring-2', 'ring-error/30');
        valid = false;
    };

    const clearField = (wrapper) => {
        if (!wrapper) {
            return;
        }

        wrapper.removeAttribute('data-field-error');
        wrapper.querySelector('[data-js-error]')?.classList.add('hidden');
        wrapper.querySelector('input, select, textarea, .rte-content')?.classList.remove('border-error', 'ring-2', 'ring-error/30');
    };

    const titleWrapper = formEl.querySelector('[data-field="title"]');
    if (!titleWrapper?.querySelector('input')?.value.trim()) {
        markField(titleWrapper, 'Title is required.');
    } else {
        clearField(titleWrapper);
    }

    const sessionWrapper = formEl.querySelector('[data-field="sessionId"]');
    if (!sessionWrapper?.querySelector('select')?.value) {
        markField(sessionWrapper, 'Please select a session.');
    } else {
        clearField(sessionWrapper);
    }

    formEl.querySelectorAll('[data-question-row]').forEach((row) => {
        const descriptionWrapper = row.querySelector('[data-field="description"]');
        if (!row.querySelector('.rte-content')?.textContent.trim()) {
            markField(descriptionWrapper, 'Description is required.');
        } else {
            clearField(descriptionWrapper);
        }

        const optionsWrapper = row.querySelector('[data-field="options"]');
        const filledOptions = Array.from(row.querySelectorAll('[data-option-label]')).filter((input) => input.value.trim() !== '');
        const hasCorrect = row.querySelector('[data-option-correct]:checked') !== null;

        if (filledOptions.length < 2) {
            markField(optionsWrapper, 'At least two options are required.');
        } else if (!hasCorrect) {
            markField(optionsWrapper, 'Please mark one option as correct.');
        } else {
            clearField(optionsWrapper);
        }
    });

    return valid;
}

/**
 * Scrolls to and focuses the first field marked with `data-field-error` (see
 * assessment-form.blade.php), since Livewire's per-field @error messages give
 * no visual cue when the invalid input is off-screen after a failed submit.
 */
export function scrollToFirstFormError(root = document) {
    const target = root.querySelector('[data-field-error]');
    if (!target) {
        return;
    }

    target.scrollIntoView({ behavior: 'smooth', block: 'center' });

    const focusable = target.matches('input, select, textarea')
        ? target
        : target.querySelector('input, select, textarea, [contenteditable="true"]');
    focusable?.focus({ preventScroll: true });
}
