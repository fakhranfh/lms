@extends('layouts.admin')

@section('title', 'Test Payment Result — E-Wallet')

@section('admin-content')
    <div class="p-gutter max-w-2xl">
        @include('admin.gateways.test-result._header')

        <div class="bg-surface border border-outline rounded-lg p-8 text-center">
            <h2 class="text-body-lg font-medium text-on-surface mb-4">Complete Payment in {{ $channel->label() }}</h2>

            @if ($response['payment_url'] ?? null)
                <p class="text-on-surface-variant mb-6">Customer will be redirected to {{ $channel->label() }} to authorize this payment.</p>
                <a href="{{ $response['payment_url'] }}" target="_blank" rel="noopener" class="inline-block px-6 py-3 bg-primary text-on-primary rounded-lg font-medium hover:opacity-90 transition">
                    Open {{ $channel->label() }}
                </a>
            @else
                <p class="text-on-surface-variant">No redirect URL was returned for this transaction.</p>
            @endif
        </div>
    </div>
@endsection
