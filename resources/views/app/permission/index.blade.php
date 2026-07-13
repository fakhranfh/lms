@extends('layouts.app')

@section('title', 'Permissions')

@php
    $topbarTitle = 'Permissions';
@endphp

@section('app-content')
    <div class="space-y-space-lg">
        <h1 class="font-headline-sm text-headline-sm text-on-surface">Permissions</h1>

        @forelse ($groupedPermissions as $group => $permissions)
            <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden">
                <div class="px-space-lg py-space-md border-b border-outline-variant bg-surface-container-lowest">
                    <h2 class="font-label-md text-label-md text-secondary uppercase">{{ $group }}</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-outline-variant">
                                <th scope="col" class="px-space-lg py-space-md text-left font-label-md text-label-md text-secondary uppercase">Label</th>
                                <th scope="col" class="px-space-lg py-space-md text-left font-label-md text-label-md text-secondary uppercase">Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($permissions as $permission)
                                <tr class="border-b border-outline-variant last:border-0">
                                    <td class="px-space-lg py-space-md font-body-md text-body-md text-on-surface">{{ $permission->label ?? '—' }}</td>
                                    <td class="px-space-lg py-space-md font-body-sm text-body-sm text-secondary">{{ $permission->name }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="bg-surface border border-outline-variant rounded-lg p-space-lg text-center font-body-md text-body-md text-secondary">
                No permissions yet.
            </div>
        @endforelse
    </div>
@endsection
