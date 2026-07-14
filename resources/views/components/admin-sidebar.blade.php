<!-- Admin Sidebar Navigation -->
<aside id="admin-sidebar" class="fixed left-0 top-16 h-[calc(100vh-64px)] bg-surface border-r border-outline-variant z-20 flex flex-col w-64 shadow-[1px_0_3px_rgba(0,0,0,0.08)] max-sm:hidden transition-all duration-300 overflow-hidden" style="width: 256px;">
    <!-- Navigation Menu -->
    <nav class="flex-1 overflow-y-auto py-space-md px-space-md">
        <ul class="space-y-space-xs">
            @foreach (config('admin-sidebar') as $item)
                @continue(($item['requires_permission'] ?? null) && ! auth()->user()->can($item['requires_permission']))
                <li>
                    <a href="{{ route($item['route']) }}" class="flex items-center gap-space-md px-space-md py-space-sm rounded-lg text-black hover:bg-primary/10 transition-colors duration-150 {{ request()->routeIs($item['active_pattern']) ? 'bg-primary/20 text-primary' : 'hover:text-on-surface' }}">
                        <span class="material-symbols-outlined text-[24px]">{{ $item['icon'] }}</span>
                        <span class="font-body-md text-body-md">{{ $item['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
</aside>

<!-- Admin Sidebar Spacer for Main Content (hides on mobile) -->
<div id="admin-sidebar-spacer" class="hidden sm:block transition-all duration-300 overflow-hidden" style="width: 256px;"></div>
