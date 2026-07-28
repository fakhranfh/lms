@extends('layouts.admin')

@section('title', 'Test Payment Result')

@section('admin-content')
    <div class="p-gutter max-w-2xl">
        @include('admin.gateways.test-result._header')

        <div class="bg-surface border border-outline rounded-lg p-8 text-center">
            <p class="text-on-surface-variant">Connection to {{ $gateway->paymentGatewayType->label }} was successful.</p>
        </div>
    </div>
@endsection
