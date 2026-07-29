<!-- Sidebar Navigation -->
@php
    $isAdminUser = auth()->user() && auth()->user()->hasRole(\App\Enums\RoleName::Admin);
    $sidebarConfig = $isAdminUser ? config('admin-sidebar') : config('sidebar');
@endphp

<aside id="sidebar" class="fixed left-0 top-16 h-[calc(100vh-64px)] bg-surface border-r border-outline-variant z-20 flex flex-col w-64 shadow-[1px_0_3px_rgba(0,0,0,0.08)] max-sm:hidden transition-all duration-300 overflow-hidden flex-shrink-0" style="width: 256px;">
    <!-- Navigation Menu -->
    <nav class="flex-1 overflow-y-auto py-space-md px-space-md">
        <ul class="space-y-space-xs">
            @if($isAdminUser)
                @forelse ($sidebarConfig as $item)
                    @continue(($item['requires_permission'] ?? null) && ! auth()->user()->can($item['requires_permission']))
                    @if(isset($item['children']))
                    <li>
                        <div class="flex items-center gap-space-md px-space-md py-space-sm rounded-lg text-black {{ request()->routeIs($item['active_pattern']) ? 'bg-primary/20 text-primary' : '' }}">
                            <span class="material-symbols-outlined text-[24px]">{{ $item['icon'] }}</span>
                            <span class="font-body-md text-body-md">{{ $item['label'] }}</span>
                        </div>
                        <ul class="ml-space-lg space-y-space-xs">
                            @foreach ($item['children'] as $child)
                                @continue(($child['requires_permission'] ?? null) && ! auth()->user()->can($child['requires_permission']))
                                <li>
                                    <a href="{{ $child['url'] ?? route($child['route']) }}" class="flex items-center gap-space-md px-space-md py-space-sm rounded-lg text-black hover:bg-primary/10 transition-colors duration-150 {{ request()->routeIs($child['active_pattern']) ? 'bg-primary/20 text-primary' : 'hover:text-on-surface' }}">
                                        <span class="font-body-md text-body-md">{{ $child['label'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                    @else
                    <li>
                        <a href="{{ $item['url'] ?? route($item['route']) }}" class="flex items-center gap-space-md px-space-md py-space-sm rounded-lg text-black hover:bg-primary/10 transition-colors duration-150 {{ request()->routeIs($item['active_pattern']) ? 'bg-primary/20 text-primary' : 'hover:text-on-surface' }}">
                            <span class="material-symbols-outlined text-[24px]">{{ $item['icon'] }}</span>
                            <span class="font-body-md text-body-md">{{ $item['label'] }}</span>
                        </a>
                    </li>
                    @endif
                @empty
                    <li class="text-body-md text-secondary px-space-md py-space-sm">No menu items</li>
                @endforelse
            @else
                @forelse ($sidebarConfig as $item)
                    @php
                        $hasPermission = !($item['requires_permission'] ?? null) || auth()->user()->can($item['requires_permission']);
                        $hasRole = !($item['requires_role'] ?? null) || auth()->user()->hasRole($item['requires_role']);
                        $hasSchool = !($item['requires_school'] ?? null) || app(\App\Support\CurrentSchool::class)->getSchoolId() !== null;
                        $notExcludedRole = !($item['exclude_role'] ?? null) || !auth()->user()->hasRole($item['exclude_role']);
                        $canAccess = $hasPermission && $hasRole && $hasSchool && $notExcludedRole;
                    @endphp
                    @if($canAccess)
                    <li>
                        <a href="{{ route($item['route']) }}" class="flex items-center gap-space-md px-space-md py-space-sm rounded-lg text-black hover:bg-primary/10 transition-colors duration-150 {{ request()->routeIs($item['active_pattern']) ? 'bg-primary/20 text-primary' : 'hover:text-on-surface' }}">
                            <span class="material-symbols-outlined text-[24px]">{{ $item['icon'] }}</span>
                            <span class="font-body-md text-body-md">{{ $item['label'] }}</span>
                        </a>
                    </li>
                    @endif
                @empty
                    <li class="text-body-md text-secondary px-space-md py-space-sm">No menu items</li>
                @endforelse
            @endif
        </ul>
    </nav>
</aside>

<!-- Sidebar Spacer for Main Content (hides on mobile) -->
<div id="sidebar-spacer" class="hidden sm:block transition-all duration-300 overflow-hidden flex-shrink-0" style="width: 256px;"></div>
