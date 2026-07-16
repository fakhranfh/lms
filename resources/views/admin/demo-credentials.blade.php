@extends('layouts.admin', ['topbarTitle' => 'Demo Credentials'])

@section('admin-content')
<div class="space-y-space-lg">
    <div class="flex items-center justify-between">
        <h1 class="text-headline-lg font-headline-lg">Demo Credentials</h1>
    </div>

    <livewire:admin.demo-credentials />
</div>
@endsection
