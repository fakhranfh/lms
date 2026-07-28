@extends('layouts.admin')

@section('title', 'Test Payment Result — Virtual Account')

@section('admin-content')
    <div class="p-gutter max-w-2xl">
        @include('admin.gateways.test-result._header')

        <div class="bg-surface border border-outline rounded-lg p-8 text-center">
            <h2 class="text-body-lg font-medium text-on-surface mb-4">{{ $channel->label() }}</h2>

            @if ($response['payment_url'] ?? null)
                <p class="text-on-surface-variant mb-2">Virtual Account Number</p>
                <p class="font-mono text-headline-sm text-on-surface tracking-wider">{{ $response['payment_url'] }}</p>
                <p class="text-body-sm text-on-surface-variant mt-4">Transfer the exact amount above to this virtual account number.</p>
            @else
                <p class="text-on-surface-variant">No virtual account number was returned for this transaction.</p>
            @endif
        </div>
    </div>
@endsection
