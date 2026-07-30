@extends($isTierChange ? 'layouts.app' : 'master', $isTierChange ? ['topbarTitle' => 'Complete Your Payment'] : [])

@section('title', 'Complete Your Payment')

@section('body_class', 'bg-background text-on-background min-h-screen flex flex-col font-body-md')

@unless ($isTierChange)
    @section('content')
        @include('partials.topbar')
        @include('partials.school-payment-content')
    @endsection
@endunless

@if ($isTierChange)
    @section('app-content')
        @include('partials.school-payment-content')
    @endsection
@endif
