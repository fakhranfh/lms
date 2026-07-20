@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('admin-content')
    <h1 class="font-headline-md text-headline-md text-on-surface mb-space-xl">Admin Dashboard</h1>

    <!-- Storage Quota -->
    <div class="bg-surface border border-outline-variant rounded-lg p-space-lg max-w-md">
        <div class="flex items-center justify-between mb-space-sm">
            <span class="text-label-md text-on-surface font-label-md">Storage Quota</span>
            <span class="text-label-md font-label-md">{{ round($quota['percentage']) }}%</span>
        </div>
        <div class="w-full h-2 bg-surface-container rounded-full overflow-hidden">
            <div
                class="h-full {{ $quota['percentage'] >= 90 ? 'bg-error' : ($quota['percentage'] >= 80 ? 'bg-warning' : 'bg-primary') }} transition-all duration-300"
                style="width: {{ $quota['percentage'] }}%"
            ></div>
        </div>
        <p class="text-body-sm text-on-surface-variant mt-space-xs">
            {{ \App\Services\R2StorageService::formatBytes($quota['used']) }} / {{ \App\Services\R2StorageService::formatBytes($quota['limit']) }} used globally
        </p>
    </div>
@endsection
