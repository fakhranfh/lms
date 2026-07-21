@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('admin-content')
    <!-- Storage Quota Card -->
    <div class="bg-surface border border-outline-variant rounded-lg p-space-lg hover:border-outline transition-colors duration-150">
        <div class="space-y-space-md">
            <div class="flex items-center justify-between">
                <span class="text-label-md text-secondary uppercase font-label-md">Storage</span>
                <span class="material-symbols-outlined text-[20px]" style="color: {{ $quota['percentage'] >= 80 ? 'var(--md-sys-color-error)' : 'var(--md-sys-color-tertiary)' }}">storage</span>
            </div>
            <div>
                <p class="font-headline-md text-headline-md text-on-surface">{{ \App\Services\R2StorageService::formatBytes($quota['remaining']) }}</p>
                <div class="flex items-center gap-space-xs mt-space-sm">
                    <div class="flex-1 h-2 bg-surface-container-highest rounded-full overflow-hidden">
                        <div class="h-full bg-tertiary transition-all duration-300" style="width: {{ $quota['percentage'] }}%; background-color: {{ $quota['percentage'] >= 80 ? 'var(--md-sys-color-error)' : ($quota['percentage'] >= 50 ? 'var(--md-sys-color-warning)' : 'var(--md-sys-color-tertiary)') }}"></div>
                    </div>
                    <p class="font-body-sm text-body-sm text-secondary whitespace-nowrap">{{ round($quota['percentage'], 1) }}%</p>
                </div>
                <p class="font-body-sm text-body-sm text-secondary mt-space-xs">
                    {{ \App\Services\R2StorageService::formatBytes($quota['used']) }} / {{ \App\Services\R2StorageService::formatBytes($quota['limit']) }} globally
                </p>
            </div>
        </div>
    </div>
@endsection
