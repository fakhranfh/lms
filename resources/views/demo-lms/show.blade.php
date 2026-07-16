@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header Section -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-8">
            <div class="px-6 py-8 bg-gradient-to-r from-blue-600 to-blue-700">
                <h1 class="text-3xl font-bold text-white mb-2">Demo LMS Access</h1>
                <p class="text-blue-100">Generate and manage demo LMS credentials for testing</p>
            </div>

            <!-- Main Content -->
            <div class="px-6 py-8">
                @if ($demoAccess && \Carbon\Carbon::parse($demoAccess->expires_at)->isFuture())
                    <!-- Active Demo Access -->
                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 border border-green-200 rounded-lg p-6 mb-8">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-green-900 mb-2">Active Demo Access</h3>
                                <p class="text-green-700 text-sm mb-4">Your demo access is currently active and valid until {{ $demoAccess->expires_at->format('M d, Y') }}</p>

                                <!-- Credentials Card -->
                                <div class="bg-white rounded-lg p-4 border border-green-100 mb-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-600 mb-1">Demo Email</label>
                                            <div class="flex items-center gap-2">
                                                <code class="flex-1 bg-gray-100 rounded px-3 py-2 text-sm font-mono text-gray-800">
                                                    {{ $demoAccess->user->email }}
                                                </code>
                                                <button onclick="copyToClipboard(this, '{{ $demoAccess->user->email }}')" class="p-2 hover:bg-gray-200 rounded transition">
                                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Access Token</label>
                            <div class="flex items-center gap-2">
                                <code class="flex-1 bg-gray-100 rounded px-3 py-2 text-sm font-mono text-gray-800">
                                    {{ Str::limit($demoAccess->access_token, 16) }}***
                                </code>
                                <button onclick="copyToClipboard(this, '{{ $demoAccess->access_token }}')" class="p-2 hover:bg-gray-200 rounded transition">
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Login Button -->
                    <div class="flex items-center gap-4">
                        <a href="{{ route('demo-lms.login', $demoAccess->access_token) }}" class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                            </svg>
                            Login to Demo LMS
                        </a>

                        <form action="{{ route('demo-lms.generate') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Generate New Credentials
                            </button>
                        </form>
                    </div>

                    <!-- Usage Info -->
                    <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h4 class="font-semibold text-blue-900 mb-2">How to Use</h4>
                        <ol class="list-decimal list-inside space-y-2 text-sm text-blue-800">
                            <li>Click "Login to Demo LMS" to auto-login as demo admin</li>
                            <li>Explore the LMS features with pre-populated demo data</li>
                            <li>Generate new credentials if you need a fresh demo session</li>
                        </ol>
                    </div>

                    <!-- Last Accessed Info -->
                    @if ($demoAccess->accessed_at)
                        <div class="mt-4 text-sm text-gray-600">
                            Last accessed: {{ $demoAccess->accessed_at->format('M d, Y \a\t H:i A') }}
                        </div>
                    @endif
                @else
                    <!-- No Active Demo Access -->
                    <div class="bg-gradient-to-br from-yellow-50 to-amber-50 border border-yellow-200 rounded-lg p-6 mb-8">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-yellow-900 mb-2">No Active Demo Access</h3>
                                <p class="text-yellow-700 text-sm mb-6">Generate demo LMS credentials to test the system. You'll get a demo admin account with 14 days of access.</p>

                                <form action="{{ route('demo-lms.generate') }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        Generate Demo Credentials
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Features Info -->
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
                        <h4 class="font-semibold text-gray-900 mb-4">What's Included in Demo Access?</h4>
                        <ul class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-green-600 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-gray-700">Demo admin account with full access</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-green-600 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-gray-700">Pre-populated sample courses and students</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-green-600 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-gray-700">14 days of access from generation date</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-green-600 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-gray-700">Full LMS feature exploration</span>
                            </li>
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    function copyToClipboard(button, text) {
        navigator.clipboard.writeText(text);

        const originalContent = button.innerHTML;
        button.innerHTML = '<svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>';

        setTimeout(() => {
            button.innerHTML = originalContent;
        }, 2000);
    }
</script>
@endsection
