<!-- Admin Sidebar Navigation -->
<aside id="admin-sidebar" class="w-64 bg-surface border-r border-outline-variant flex flex-col shadow-[1px_0_3px_rgba(0,0,0,0.08)] max-sm:hidden transition-all duration-300 overflow-hidden" style="width: 256px;">
    <!-- Navigation Menu -->
    <nav class="flex-1 overflow-y-auto py-space-md px-space-md">
        <ul class="space-y-space-xs">
            @foreach (config('admin-sidebar') as $item)
                @continue(($item['requires_permission'] ?? null) && ! auth()->user()->can($item['requires_permission']))
                <li>
                    @if (isset($item['children']))
                        <div class="flex items-center gap-space-md px-space-md py-space-sm rounded-lg text-black {{ request()->routeIs(...(array) $item['active_pattern']) ? 'text-primary' : '' }}">
                            <span class="material-symbols-outlined text-[24px]">{{ $item['icon'] }}</span>
                            <span class="font-body-md text-body-md">{{ $item['label'] }}</span>
                        </div>
                        <ul class="mt-space-xs ml-space-lg space-y-space-xs border-l border-outline-variant pl-space-md">
                            @foreach ($item['children'] as $child)
                                @continue(($child['requires_permission'] ?? null) && ! auth()->user()->can($child['requires_permission']))
                                <li>
                                    <a href="{{ $child['url'] ?? route($child['route']) }}" class="flex items-center gap-space-md px-space-md py-space-sm rounded-lg text-black hover:bg-primary/10 transition-colors duration-150 {{ request()->routeIs(...(array) $child['active_pattern']) ? 'bg-primary/20 text-primary' : 'hover:text-on-surface' }}">
                                        <span class="font-body-md text-body-md">{{ $child['label'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <a href="{{ $item['url'] ?? route($item['route']) }}" class="flex items-center gap-space-md px-space-md py-space-sm rounded-lg text-black hover:bg-primary/10 transition-colors duration-150 {{ request()->routeIs(...(array) $item['active_pattern']) ? 'bg-primary/20 text-primary' : 'hover:text-on-surface' }}">
                            <span class="material-symbols-outlined text-[24px]">{{ $item['icon'] }}</span>
                            <span class="font-body-md text-body-md">{{ $item['label'] }}</span>
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>
</aside>
