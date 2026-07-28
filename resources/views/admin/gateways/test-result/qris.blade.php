@extends('layouts.admin')

@section('title', 'Test Payment Result — QRIS')

@section('admin-content')
    <div class="p-gutter max-w-2xl">
        @include('admin.gateways.test-result._header')

        <div class="bg-surface border border-outline rounded-lg p-8 text-center">
            <h2 class="text-body-lg font-medium text-on-surface mb-4">Scan QR Code</h2>

            @if ($qrCodeSvg)
                <div class="inline-block p-4 bg-white border border-outline rounded-lg mb-4">
                    {!! $qrCodeSvg !!}
                </div>
                <p class="text-body-sm text-on-surface-variant">Scan with any QRIS-supported app</p>
                <details class="mt-4 text-left max-w-md mx-auto">
                    <summary class="text-body-sm text-on-surface-variant cursor-pointer">Show raw QR string</summary>
                    <p class="font-mono text-body-sm text-on-surface break-all mt-2">{{ $response['payment_url'] }}</p>
                </details>
            @else
                <p class="text-on-surface-variant">No QR data was returned for this transaction.</p>
            @endif
        </div>
    </div>
@endsection
