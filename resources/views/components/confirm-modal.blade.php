<!-- Confirm Modal -->
<div id="{{ $id ?? 'confirmModal' }}" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-sm w-full mx-4">
        <div class="p-6">
            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-{{ $iconBgColor ?? 'red' }}-100 dark:bg-{{ $iconBgColor ?? 'red' }}-900 rounded-full">
                {{ $icon ?? '' }}
            </div>
            <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white text-center">{{ $title ?? __('Are you sure?') }}</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 text-center">{{ $message ?? __('This action cannot be undone.') }}</p>
            <div class="mt-6 flex gap-3">
                <button onclick="cancelAction('{{ $id ?? 'confirmModal' }}')" class="flex-1 px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-900 dark:text-white rounded-lg hover:bg-gray-400 dark:hover:bg-gray-700 font-medium">{{ $cancelLabel ?? __('Cancel') }}</button>
                <button onclick="confirmAction('{{ $id ?? 'confirmModal' }}')" class="flex-1 px-4 py-2 bg-{{ $confirmBtnColor ?? 'red' }}-600 text-white rounded-lg hover:bg-{{ $confirmBtnColor ?? 'red' }}-700 font-medium">{{ $confirmLabel ?? __('Delete') }}</button>
            </div>
        </div>
    </div>
</div>
