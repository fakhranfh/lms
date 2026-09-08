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
