@extends('master')

@section('title', 'Try Demo')

@section('body_class', 'bg-background text-on-background min-h-screen flex flex-col font-body-md')

@section('content')

    @include('partials.topbar')

    <main class="flex flex-1 items-center justify-center p-gutter">
        <div class="w-full max-w-[420px] bg-surface rounded-xl p-space-xl border border-outline-variant shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
            <div class="text-center mb-space-xl">
                <h2 class="font-headline-md text-headline-md text-on-surface mb-space-xxs">Try the demo</h2>
                <p class="font-body-md text-body-md text-secondary">Explore the LMS instantly with a pre-loaded demo course. No account needed.</p>
            </div>

            <div class="space-y-space-md" x-data="{ loading: null }">
                <a href="{{ route('try-demo.login'.\App\Support\RootDomains::currentSuffix(), ['role' => 'school-admin']) }}"
                    @click="loading = 'school-admin'"
                    :class="loading ? (loading === 'school-admin' ? 'pointer-events-none opacity-75' : 'pointer-events-none opacity-50') : ''"
                    class="flex items-center justify-between h-[56px] px-space-md rounded-lg border border-outline-variant bg-surface-container-lowest hover:border-primary hover:ring-1 hover:ring-primary transition-colors group">
                    <span class="flex items-center gap-space-md">
                        <template x-if="loading === 'school-admin'">
                            <span class="material-symbols-outlined text-primary text-[24px] animate-spin">progress_activity</span>
                        </template>
                        <template x-if="loading !== 'school-admin'">
                            <span class="material-symbols-outlined text-primary text-[24px]">admin_panel_settings</span>
                        </template>
                        <span class="text-left">
                            <span class="block font-label-md text-label-md text-on-surface">Login as School Admin</span>
                            <span class="block font-body-sm text-body-sm text-secondary">Manage school settings, roles, and users</span>
                        </span>
                    </span>
                    <span class="material-symbols-outlined text-secondary/60 text-[20px] group-hover:translate-x-0.5 transition-transform">arrow_forward</span>
                </a>

                <a href="{{ route('try-demo.login'.\App\Support\RootDomains::currentSuffix(), ['role' => 'instructor']) }}"
                    @click="loading = 'instructor'"
                    :class="loading ? (loading === 'instructor' ? 'pointer-events-none opacity-75' : 'pointer-events-none opacity-50') : ''"
                    class="flex items-center justify-between h-[56px] px-space-md rounded-lg border border-outline-variant bg-surface-container-lowest hover:border-primary hover:ring-1 hover:ring-primary transition-colors group">
                    <span class="flex items-center gap-space-md">
                        <template x-if="loading === 'instructor'">
                            <span class="material-symbols-outlined text-primary text-[24px] animate-spin">progress_activity</span>
                        </template>
                        <template x-if="loading !== 'instructor'">
                            <span class="material-symbols-outlined text-primary text-[24px]">school</span>
                        </template>
                        <span class="text-left">
                            <span class="block font-label-md text-label-md text-on-surface">Login as Instructor</span>
                            <span class="block font-body-sm text-body-sm text-secondary">Build courses, grade assignments</span>
                        </span>
                    </span>
                    <span class="material-symbols-outlined text-secondary/60 text-[20px] group-hover:translate-x-0.5 transition-transform">arrow_forward</span>
                </a>

                <a href="{{ route('try-demo.login'.\App\Support\RootDomains::currentSuffix(), ['role' => 'student']) }}"
                    @click="loading = 'student'"
                    :class="loading ? (loading === 'student' ? 'pointer-events-none opacity-75' : 'pointer-events-none opacity-50') : ''"
                    class="flex items-center justify-between h-[56px] px-space-md rounded-lg border border-outline-variant bg-surface-container-lowest hover:border-primary hover:ring-1 hover:ring-primary transition-colors group">
                    <span class="flex items-center gap-space-md">
                        <template x-if="loading === 'student'">
                            <span class="material-symbols-outlined text-primary text-[24px] animate-spin">progress_activity</span>
                        </template>
                        <template x-if="loading !== 'student'">
                            <span class="material-symbols-outlined text-primary text-[24px]">person</span>
                        </template>
                        <span class="text-left">
                            <span class="block font-label-md text-label-md text-on-surface">Login as Student</span>
                            <span class="block font-body-sm text-body-sm text-secondary">Take courses, submit work</span>
                        </span>
                    </span>
                    <span class="material-symbols-outlined text-secondary/60 text-[20px] group-hover:translate-x-0.5 transition-transform">arrow_forward</span>
                </a>
            </div>
        </div>
    </main>

    @include('partials.footer')

@endsection
