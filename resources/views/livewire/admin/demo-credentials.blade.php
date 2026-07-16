@section('title', 'Demo Credentials')

<div>
    <div id="snack-container"></div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <!-- Content -->
        <div class="p-6">
            <!-- School Selection -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-900 mb-2">Select School</label>
                <div wire:loading.remove wire:target="selectedSchoolId">
                    <select wire:model.live="selectedSchoolId"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">-- Choose a school --</option>
                        @foreach ($schools as $school)
                            <option value="{{ $school->id }}">{{ $school->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div wire:loading wire:target="selectedSchoolId" class="w-full h-10 bg-gray-200 rounded-lg animate-pulse"></div>
            </div>

            @if ($selectedSchool)
                <!-- Loading Skeleton -->
                <div wire:loading wire:target="generateCredentials,selectedSchoolId" class="w-full mb-6">
                    <div class="w-full bg-green-50 border border-green-200 rounded-lg p-4 mb-6 animate-pulse">
                        <div class="flex items-start gap-3">
                            <div class="w-5 h-5 bg-green-300 rounded-full flex-shrink-0"></div>
                            <div class="flex-1 w-full">
                                <div class="h-6 bg-green-300 rounded w-40 mb-4"></div>
                                <div class="space-y-3">
                                    <div class="w-full bg-white rounded p-3 border border-green-100">
                                        <div class="h-3 bg-gray-300 rounded w-12 mb-2"></div>
                                        <div class="h-10 bg-gray-200 rounded w-full"></div>
                                    </div>
                                    <div class="w-full bg-white rounded p-3 border border-green-100">
                                        <div class="h-3 bg-gray-300 rounded w-20 mb-2"></div>
                                        <div class="h-10 bg-gray-200 rounded w-full"></div>
                                    </div>
                                    <div class="w-full bg-white rounded p-3 border border-green-100">
                                        <div class="h-3 bg-gray-300 rounded w-16 mb-2"></div>
                                        <div class="h-10 bg-gray-200 rounded w-full"></div>
                                    </div>
                                </div>
                                <div class="mt-3 pt-3 border-t border-green-100">
                                    <div class="h-3 bg-green-300 rounded w-56 mb-2"></div>
                                    <div class="h-3 bg-green-300 rounded w-48"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="w-32 h-10 bg-blue-600 rounded-lg animate-pulse"></div>
                </div>

                <!-- Content -->
                <div wire:loading.remove wire:target="generateCredentials,selectedSchoolId">
                    @if ($demoAccess && $demoAccess->expires_at->isFuture())
                        <!-- Active Demo Access -->
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-green-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd"></path>
                                </svg>
                                <div class="flex-1">
                                    <h3 class="font-semibold text-green-900 mb-3">Active Demo Access</h3>
                                    <div class="space-y-3">
                                        <div class="bg-white rounded p-3 border border-green-100">
                                            <label class="text-xs font-medium text-gray-600 block mb-1">Email</label>
                                            <div class="flex items-center gap-2">
                                                <code
                                                    class="flex-1 bg-gray-50 px-3 py-2 rounded text-sm font-mono text-gray-800 break-all">
                                                    {{ $demoAccess->user->email }}
                                                </code>
                                                <button type="button"
                                                    onclick="copyToClipboard(this, '{{ $demoAccess->user->email }}')"
                                                    class="p-2 hover:bg-gray-100 rounded transition flex-shrink-0">
                                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z">
                                                        </path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="bg-white rounded p-3 border border-green-100">
                                            <label class="text-xs font-medium text-gray-600 block mb-1">Access Token</label>
                                            <div class="flex items-center gap-2">
                                                <code
                                                    class="flex-1 bg-gray-50 px-3 py-2 rounded text-sm font-mono text-gray-800 break-all">
                                                    {{ $demoAccess->access_token }}
                                                </code>
                                                <button type="button"
                                                    onclick="copyToClipboard(this, '{{ $demoAccess->access_token }}')"
                                                    class="p-2 hover:bg-gray-100 rounded transition flex-shrink-0">
                                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z">
                                                        </path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="bg-white rounded p-3 border border-green-100">
                                            <label class="text-xs font-medium text-gray-600 block mb-1">Login URL</label>
                                            <div class="flex items-center gap-2">
                                                <code
                                                    class="flex-1 bg-gray-50 px-3 py-2 rounded text-sm font-mono text-gray-800 break-all">
                                                    http://lms.local/demo-lms/login/{{ $demoAccess->access_token }}
                                                </code>
                                                <button type="button"
                                                    onclick="copyToClipboard(this, 'http://lms.local/demo-lms/login/{{ $demoAccess->access_token }}')"
                                                    class="p-2 hover:bg-gray-100 rounded transition flex-shrink-0">
                                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z">
                                                        </path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-3 pt-3 border-t border-green-100">
                                        <p class="text-xs text-green-700">
                                            <strong>Expires:</strong> {{ $demoAccess->expires_at_display->format('M d, Y H:i A') }}
                                            @if ($demoAccess->accessed_at_display)
                                                <br>
                                                <strong>Last accessed:</strong>
                                                {{ $demoAccess->accessed_at_display->format('M d, Y H:i A') }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Regenerate Button -->
                        <button type="button" wire:click="generateCredentials"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                </path>
                            </svg>
                            Regenerate Credentials
                        </button>
                    @else
                        <!-- No Active Access -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                        clip-rule="evenodd"></path>
                                </svg>
                                <div class="flex-1">
                                    <h3 class="font-semibold text-blue-900">No Active Demo Access</h3>
                                    <p class="text-sm text-blue-700 mt-1">Create demo credentials for this school to test the LMS.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Generate Button -->
                        <button type="button" wire:click="generateCredentials"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4"></path>
                            </svg>
                            Generate Demo Credentials
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    window.copyToClipboard = function(button, text) {
        const originalContent = button.innerHTML;
        const showIcon = '<svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>';

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => {
                button.innerHTML = showIcon;
                window.showSnack('Copied to clipboard!');
                setTimeout(() => {
                    button.innerHTML = originalContent;
                }, 2000);
            }).catch(() => {
                fallbackCopy(text, button, originalContent, showIcon);
            });
        } else {
            fallbackCopy(text, button, originalContent, showIcon);
        }
    };

    function fallbackCopy(text, button, originalContent, showIcon) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();
        try {
            document.execCommand('copy');
            button.innerHTML = showIcon;
            window.showSnack('Copied to clipboard!');
            setTimeout(() => {
                button.innerHTML = originalContent;
            }, 2000);
        } catch (err) {
            window.showSnack('Failed to copy', 'error');
        }
        document.body.removeChild(textarea);
    }

    window.showSnack = function(message, type = 'success') {
        const snackContainer = document.getElementById('snack-container');
        if (!snackContainer) return;

        const snackEl = document.createElement('div');
        snackEl.className = `px-4 py-3 rounded-lg shadow-lg text-white font-medium ${
            type === 'success' ? 'bg-green-600' : 'bg-red-600'
        }`;
        snackEl.textContent = message;
        snackEl.style.cssText = `
            position: fixed;
            bottom: 16px;
            right: 16px;
            z-index: 50;
            animation: slideIn 0.3s ease-out;
        `;

        snackContainer.appendChild(snackEl);

        setTimeout(() => {
            snackEl.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => snackEl.remove(), 300);
        }, 2000);
    };
</script>

<style>
    @keyframes slideIn {
        from {
            transform: translateY(100px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    @keyframes slideOut {
        from {
            transform: translateY(0);
            opacity: 1;
        }
        to {
            transform: translateY(100px);
            opacity: 0;
        }
    }
</style>
