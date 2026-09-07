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
