@php
    $successMessage = session('success');
    if (!$successMessage && session('status') === 'password-updated') {
        $successMessage = 'Your password has been changed successfully. Please remember to use your new password for future logins.';
    } elseif (!$successMessage) {
        $successMessage = session('status');
    }
@endphp

@if ($successMessage)
<div id="success-alert" class="mb-6 rounded-lg bg-success-container p-4 border border-success/30">
    <div class="flex">
        <div class="flex-shrink-0">
            <span class="material-symbols-outlined text-success" data-icon="check_circle">check_circle</span>
        </div>
        <div class="ml-3 flex-1">
            <h3 class="font-label-md text-label-md text-success">Success</h3>
            <div class="mt-2 font-body-sm text-body-sm text-on-success-container">
                {{ $successMessage }}
            </div>
        </div>
        <button type="button" class="flex-shrink-0 ml-3 text-success hover:text-on-success-container transition-colors" onclick="document.getElementById('success-alert').classList.add('hidden');" aria-label="Close alert">
            <span class="material-symbols-outlined" data-icon="close">close</span>
        </button>
    </div>
</div>
@endif

<!-- Error Alert -->
@php
    $updatePasswordErrors = $errors->getBag('updatePassword')->all();
    $allErrors = array_merge($errors->all(), $updatePasswordErrors);
    $hasErrors = count($allErrors) > 0;
    $errorCount = count($allErrors);
    $errorTitle = $errorCount === 1 ? 'Please fix the following error:' : 'Please fix the following errors:';
@endphp

<div id="error-alert" class="@if ($hasErrors) mb-6 @else hidden mb-6 @endif rounded-lg bg-error-container p-4 border border-error/30">
    <div class="flex">
        <div class="flex-shrink-0">
            <span class="material-symbols-outlined text-error" data-icon="error">error</span>
        </div>
        <div class="ml-3 flex-1">
            <h3 class="font-label-md text-label-md text-error">{{ $errorTitle }}</h3>
            <div class="mt-2 font-body-sm text-body-sm text-on-error-container">
                <ul id="error-list" class="list-disc pl-5 space-y-1">
                    @forelse ($allErrors as $error)
                    <li>{{ $error }}</li>
                    @empty
                    @endforelse
                </ul>
            </div>
        </div>
        <button type="button" id="close-error-alert" class="flex-shrink-0 ml-3 text-error hover:text-on-error-container transition-colors" onclick="document.getElementById('error-alert').classList.add('hidden');" aria-label="Close alert">
            <span class="material-symbols-outlined" data-icon="close">close</span>
        </button>
    </div>
</div>