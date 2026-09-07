/**
 * Clones a <template> row, rewrites its __NEW__ index placeholders, and
 * writes the new item straight into the Livewire component's state via
 * $wire.set(..., false) — no network round trip, so "Add" feels instant.
 * The row's own inputs (rich-text editors included) sync further edits the
 * same deferred way, since dynamically inserted elements are never scanned
 * for wire:model by Livewire.
 */
export function addSyllabusRow($wire, templateId, arrayPath, defaults) {
    const items = $wire.get(arrayPath) || [];
    const index = items.length;

    $wire.set(`${arrayPath}.${index}`, { ...defaults, order: index + 1 }, false);

    const template = document.getElementById(templateId);
    if (!template) {
        return;
    }

    // A template can carry more than one placeholder token when it embeds a
    // template of its own (an evaluation group embeds its activity
    // template) — only one of these tokens is ever actually present in any
    // given template, so replacing all of them with the same fresh index is
    // safe and keeps callers from having to know which token applies.
    let html = template.innerHTML;
    ['__NEW__', '__GROUP__', '__ACTIVITY__'].forEach((token) => {
        html = html.replaceAll(token, index);
    });

    const wrapper = document.createElement('div');
    wrapper.innerHTML = html.trim();
    const node = wrapper.firstElementChild;

    if (node) {
        template.parentElement.insertBefore(node, template);
    }

    return node;
}

/**
 * Removes a row purely client-side: nulls out its underlying Livewire
 * array slot(s) (deferred, no round trip) and deletes its DOM node. The
 * slot is left null rather than spliced out — reindexing survivors would
 * desync their already-bound wire:model/index-baked handlers — and
 * SyllabusIndex::save() strips the null holes before persisting.
 */
export function removeSyllabusRow($wire, paths, buttonEl) {
    (Array.isArray(paths) ? paths : [paths]).forEach((path) => {
        $wire.set(path, null, false);
    });

    const row = buttonEl.closest('[data-row]');
    if (row) {
        row.remove();
    }
}

/**
 * Sets a single boolean field to true on one item of a Livewire array and
 * false on every sibling (e.g. picking the correct option of a quiz
 * question), the same deferred, no-round-trip way as addSyllabusRow.
 */
export function setSyllabusRadio($wire, arrayPath, selectedIndex, field = 'isCorrect') {
    const items = $wire.get(arrayPath) || [];

    items.forEach((item, index) => {
        if (item !== null) {
            $wire.set(`${arrayPath}.${index}.${field}`, index === selectedIndex, false);
        }
    });
}

/**
 * Adds a new empty option to a quiz question. Options are nested one level
 * inside questions, and the <template>-clone approach addSyllabusRow uses
 * only substitutes a single index per clone (see its own docblock) — reusing
 * it here would collide the option's index with the parent question's index
 * for any question added client-side. Building the row by hand instead
 * sidesteps that, and works the same whether the question was rendered by
 * the server or added moments ago via addSyllabusRow.
 */
export function addQuizOption($wire, questionIndex, containerEl) {
    if (!containerEl) {
        return;
    }

    const arrayPath = `questions.${questionIndex}.options`;
    const options = $wire.get(arrayPath) || [];
    const optionIndex = options.length;

    $wire.set(`${arrayPath}.${optionIndex}`, { id: null, label: '', isCorrect: false }, false);

    const wrapper = document.createElement('div');
    wrapper.innerHTML = `
        <div data-row data-option-row class="flex items-center gap-space-sm">
            <input type="radio" name="correct-option-${questionIndex}" data-option-correct />
            <input type="text" data-option-label placeholder="Option label" class="flex-1 px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50" />
            <button type="button" data-remove-option class="text-error text-body-sm hover:underline">Remove</button>
        </div>
    `.trim();
    const row = wrapper.firstElementChild;

    row.querySelector('[data-option-correct]').addEventListener('change', () => {
        setSyllabusRadio($wire, arrayPath, optionIndex);
    });
    row.querySelector('[data-option-label]').addEventListener('input', (event) => {
        $wire.set(`${arrayPath}.${optionIndex}.label`, event.target.value, false);
    });
    row.querySelector('[data-remove-option]').addEventListener('click', () => {
        removeSyllabusRow($wire, `${arrayPath}.${optionIndex}`, row);
    });

    containerEl.appendChild(row);
}

/**
 * Toggles a value in/out of a Livewire array property (e.g. the learning
 * outcome checkboxes on an evaluation activity), the same deferred,
 * no-round-trip way as addSyllabusRow.
 */
export function toggleWireArrayValue($wire, path, value) {
    const current = $wire.get(path) || [];
    const stringValue = String(value);
    const index = current.findIndex((item) => String(item) === stringValue);

    const next = index >= 0
        ? current.filter((_, i) => i !== index)
        : [...current, value];

    $wire.set(path, next, false);
}
