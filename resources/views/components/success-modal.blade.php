<!-- Success Modal -->
<div id="{{ $id ?? 'successModal' }}" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-sm w-full mx-4">
        <div class="p-6">
            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-green-100 dark:bg-green-900 rounded-full">
                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white text-center">{{ __('Success') }}</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 text-center" id="{{ ($id ?? 'successModal') . 'Message' }}"></p>
            <div class="mt-6">
                <button onclick="closeModal('{{ $id ?? 'successModal' }}')" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">OK</button>
            </div>
        </div>
    </div>
</div>
