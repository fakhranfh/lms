@push('scripts')
<script>
// Global variable for delete URL
let deleteUrl = null;

// Show success/info modal
function showModal(message, modalId = 'successModal') {
    const messageEl = document.getElementById(modalId + 'Message');
    if (messageEl) messageEl.textContent = message;
    document.getElementById(modalId).classList.remove('hidden');
}

// Close modal
function closeModal(modalId = 'successModal') {
    document.getElementById(modalId).classList.add('hidden');
}

// Show confirmation modal for delete
function showConfirmModal(url, modalId = 'confirmDeleteModal') {
    deleteUrl = url;
    document.getElementById(modalId).classList.remove('hidden');
}

// Cancel action (close confirmation modal)
function cancelAction(modalId) {
    deleteUrl = null;
    document.getElementById(modalId).classList.add('hidden');
}

// Confirm action (execute delete)
function confirmAction(modalId) {
    if (modalId === 'confirmDeleteModal' && deleteUrl) {
        document.getElementById(modalId).classList.add('hidden');
        performDelete(deleteUrl);
    }
}

// Legacy function for backward compatibility
function cancelDelete() {
    cancelAction('confirmDeleteModal');
}

// Legacy function for backward compatibility
function confirmDelete() {
    confirmAction('confirmDeleteModal');
}

// Perform DELETE request
function performDelete(url) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!csrf) return showModal('{{ __("CSRF token not found") }}');

    fetch(url, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
    }).then(async r => {
        if (r.ok) {
            showModal('{{ __("Item deleted successfully.") }}');
            setTimeout(() => {
                // Try to reload active table
                const tables = document.querySelectorAll('[data-list-url]');
                tables.forEach(table => {
                    const functionName = 'load' + table.id.charAt(0).toUpperCase() + table.id.slice(1);
                    if (typeof window[functionName] === 'function') {
                        window[functionName]();
                    }
                });
            }, 500);
        } else {
            const text = await r.text();
            console.error('Delete failed:', r.status, text);
            showModal('{{ __("Failed to delete") }} (Status: ' + r.status + ')');
        }
    }).catch(e => {
        console.error('Delete error:', e);
        showModal('{{ __("Failed to delete") }}: ' + e.message);
    });
}
</script>
@endpush
