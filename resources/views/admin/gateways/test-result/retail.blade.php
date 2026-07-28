@extends('layouts.admin')

@section('title', 'Test Payment Result — Retail Outlet')

@section('admin-content')
    <div class="p-gutter max-w-2xl">
        @include('admin.gateways.test-result._header')

        <div class="bg-surface border border-outline rounded-lg p-8 text-center">
            <h2 class="text-body-lg font-medium text-on-surface mb-4">Pay at {{ $channel->label() }}</h2>

            @if ($response['payment_url'] ?? null)
                <p class="text-on-surface-variant mb-2">Payment Code</p>
                <p class="font-mono text-headline-sm text-on-surface tracking-wider">{{ $response['payment_url'] }}</p>
                <p class="text-body-sm text-on-surface-variant mt-4">Give this code to the cashier at any {{ $channel->label() }} outlet.</p>
            @else
                <p class="text-on-surface-variant">No payment code was returned for this transaction.</p>
            @endif
        </div>
    </div>
@endsection
