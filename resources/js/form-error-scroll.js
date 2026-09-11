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
 * Client-side gate mirroring AssessmentFinalExamForm::persist()'s "essay
 * points must total 100" rule (see assessment-final-exam-form.blade.php), so
 * an obviously-wrong total is caught before a round trip. Only rows whose
 * "Question Type" radio is currently checked essay count — a hidden
 * multiple-choice row's leftover points value must not pollute the sum, and
 * a pure multiple-choice exam (no essay rows at all) has nothing to check.
 */
export function validateFinalExamEssayTotal(formEl) {
    const errorBox = formEl.querySelector('#final-exam-essay-total-error');
    const rows = Array.from(formEl.querySelectorAll('[data-question-row]'));

    const essayPointsInputs = rows
        .filter((row) => row.querySelector('[data-question-type-radio][value="essay"]:checked') !== null)
        .map((row) => row.querySelector('[data-essay-fields] input[type="number"]'))
        .filter(Boolean);

    if (essayPointsInputs.length === 0) {
        errorBox?.classList.add('hidden');
        errorBox?.removeAttribute('data-field-error');

        return true;
    }

    const total = essayPointsInputs.reduce((sum, input) => sum + (parseFloat(input.value) || 0), 0);

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
/**
 * A rich text editor field counts as filled if it has text or an inserted
 * attachment (image/file chip) — a file-only answer has no text at all.
 */
function rteHasContent(rteContentEl) {
    if (!rteContentEl) {
        return false;
    }

    return rteContentEl.textContent.trim() !== '' || rteContentEl.querySelector('img, a.rte-file-chip') !== null;
}

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
        if (!rteHasContent(row.querySelector('[data-field="description"] .rte-content'))) {
            markField(descriptionWrapper, 'Description is required.');
        } else {
            clearField(descriptionWrapper);
        }

        const optionsWrapper = row.querySelector('[data-field="options"]');
        const filledOptions = Array.from(row.querySelectorAll('[data-option-row]')).filter((optionRow) => rteHasContent(optionRow.querySelector('.rte-content')));
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
 * Full client-side gate for the final exam form (see
 * assessment-final-exam-form.blade.php), marking every empty/invalid field
 * at once rather than stopping at the first one. Without this, a fully
 * empty form would only ever surface validateFinalExamEssayTotal's single
 * "essay points must total 100" error and never reach a server round trip
 * (the @submit.prevent handler returns early on any client-side failure),
 * so the required title/dates/description/options fields would silently go
 * unflagged. Mirrors AssessmentFinalExamForm::persist()'s required-field
 * rules; take-home exams only ever render a single forced-essay question.
 */
export function validateFinalExamForm(formEl) {
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

    const weightWrapper = formEl.querySelector('[data-field="weight"]');
    if (!weightWrapper?.querySelector('input')?.value.trim()) {
        markField(weightWrapper, 'Weight is required.');
    } else {
        clearField(weightWrapper);
    }

    const startWrapper = formEl.querySelector('[data-field="startDate"]');
    if (!startWrapper?.querySelector('input')?.value) {
        markField(startWrapper, 'Start date is required.');
    } else {
        clearField(startWrapper);
    }

    const endWrapper = formEl.querySelector('[data-field="endDate"]');
    if (!endWrapper?.querySelector('input')?.value) {
        markField(endWrapper, 'End date is required.');
    } else {
        clearField(endWrapper);
    }

    formEl.querySelectorAll('[data-question-row]').forEach((row) => {
        const descriptionWrapper = row.querySelector('[data-field="description"]');
        if (!rteHasContent(row.querySelector('[data-field="description"] .rte-content'))) {
            markField(descriptionWrapper, 'Description is required.');
        } else {
            clearField(descriptionWrapper);
        }

        const isEssay = row.querySelector('[data-question-type-radio][value="essay"]:checked') !== null;

        if (isEssay) {
            const pointsWrapper = row.querySelector('[data-essay-fields]');
            const pointsInput = pointsWrapper?.querySelector('input[type="number"]');
            if (!pointsInput?.value.trim()) {
                markField(pointsWrapper, 'Points is required.');
            } else {
                clearField(pointsWrapper);
            }

            return;
        }

        const optionsWrapper = row.querySelector('[data-field="options"]');
        const filledOptions = Array.from(row.querySelectorAll('[data-option-row]')).filter((optionRow) => rteHasContent(optionRow.querySelector('.rte-content')));
        const hasCorrect = row.querySelector('[data-option-correct]:checked') !== null;

        if (filledOptions.length < 2) {
            markField(optionsWrapper, 'At least two options are required.');
        } else if (!hasCorrect) {
            markField(optionsWrapper, 'Please mark one option as correct.');
        } else {
            clearField(optionsWrapper);
        }
    });

    const essayTotalValid = validateFinalExamEssayTotal(formEl);

    return valid && essayTotalValid;
}

/**
 * Scrolls to and focuses a field marked with `data-field-error` (see
 * assessment-form.blade.php), since Livewire's per-field @error messages give
 * no visual cue when the invalid input is off-screen after a failed submit.
 */
function focusField(target) {
    target.scrollIntoView({ behavior: 'smooth', block: 'center' });

    const focusable = target.matches('input, select, textarea')
        ? target
        : target.querySelector('input, select, textarea, [contenteditable="true"]');
    focusable?.focus({ preventScroll: true });
}

/**
 * Builds a human-readable label for a failed field's snackbar entry without
 * requiring every field to be annotated by hand: an explicit
 * `data-field-label` wins (used by fields with no visible <label>, e.g. the
 * "questions" summary error), otherwise the field's own <label> text is
 * combined with its parent question row's heading ("Question 2 — Points").
 */
function describeField(target, index) {
    if (target.dataset.fieldLabel) {
        return target.dataset.fieldLabel;
    }

    const label = target.querySelector('label')?.textContent?.trim();
    const row = target.closest('[data-question-row]');
    const rowLabel = row && row !== target ? row.querySelector('p')?.textContent?.trim() : null;

    const parts = [rowLabel, label].filter(Boolean);

    return parts.length > 0 ? parts.join(' — ') : `Field ${index + 1}`;
}

function fieldMessage(target) {
    return target.querySelector('.text-error')?.textContent?.trim() || 'This field is invalid.';
}

/**
 * Legacy single-field jump, kept for any caller that only ever expects one
 * error at a time. New code should prefer handleFormValidationErrors, which
 * also covers the multi-error case.
 */
export function scrollToFirstFormError(root = document) {
    const target = root.querySelector('[data-field-error]');
    if (!target) {
        return;
    }

    focusField(target);
}

/**
 * Reacts to a failed form submission (client-side gate or a Livewire
 * validation round trip): always shows the dismissible snackbar (see
 * resources/views/components/ui/error-snackbar.blade.php) listing every
 * failed field — even a single one — so a validation error is never only a
 * silent scroll, and also jumps straight to the first invalid field.
 * Clicking a snackbar entry scrolls to and focuses that field.
 */
export function handleFormValidationErrors(root = document) {
    const targets = Array.from(root.querySelectorAll('[data-field-error]'));

    if (targets.length === 0) {
        window.hideFormErrorSnackbar?.();

        return;
    }

    focusField(targets[0]);

    window.showFormErrorSnackbar?.(targets.map((target, index) => ({
        label: describeField(target, index),
        message: fieldMessage(target),
        focus: () => focusField(target),
    })));
}
