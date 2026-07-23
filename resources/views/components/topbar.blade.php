<!-- Top Navigation Bar -->
<nav class="bg-surface border-b border-outline-variant sticky top-0 z-30 shadow-[0_1px_3px_rgba(0,0,0,0.08)]">
    <div class="px-gutter h-16 flex items-center justify-between">
        <div class="flex items-center gap-space-md">
            <button id="sidebar-toggle" class="p-2 text-secondary hover:bg-surface-container-low rounded-full transition-colors duration-200 flex items-center justify-center" title="Toggle Sidebar">
                <span class="material-symbols-outlined">menu</span>
            </button>
            @if($showBackButton ?? false)
                <a href="{{ route('dashboard') }}" class="p-2 text-secondary hover:bg-surface-container-low rounded-full transition-colors duration-200 flex items-center justify-center" title="Back to Dashboard">
                    <span class="material-symbols-outlined">arrow_back</span>
                </a>
            @endif
            <h1 class="font-headline-sm text-headline-sm text-on-surface truncate">{{ $title }}</h1>
        </div>

        <div class="relative group" id="user-menu-container">
            <button id="user-menu-toggle" dusk="user-menu-toggle" onclick="(function(){var d=document.getElementById('user-dropdown');var open=d.getAttribute('data-open')==='true';d.setAttribute('data-open',open?'false':'true');if(open){d.classList.add('opacity-0','invisible');d.classList.remove('opacity-100','visible')}else{d.classList.remove('opacity-0','invisible');d.classList.add('opacity-100','visible')}})();return false;" class="flex items-center gap-space-md px-space-md py-space-xs rounded-full hover:bg-surface-container-low transition-colors duration-200 focus:outline-none">
                <div x-data="{ photoUrl: @js(auth()->user()->profile_photo_path) }" x-on:profile-photo-updated.window="photoUrl = $event.detail.photoUrl">
                    <template x-if="photoUrl">
                        <img :src="photoUrl" alt="Profile" class="w-10 h-10 rounded-full object-cover border border-outline-variant">
                    </template>
                    <template x-if="!photoUrl">
                        <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-on-primary font-headline-sm text-headline-sm">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                    </template>
                </div>
                <div class="text-left hidden sm:block">
                    <div class="flex items-center gap-space-xs">
                        <p class="font-label-md text-label-md text-on-surface">{{ auth()->user()->name }}</p>
                        @foreach (auth()->user()->roles as $role)
                            <span class="px-space-xs py-[1px] rounded-full bg-primary/10 text-primary font-label-sm text-label-sm uppercase">{{ $role->name }}</span>
                        @endforeach
                    </div>
                    <p class="font-body-sm text-body-sm text-secondary">{{ auth()->user()->email }}</p>
                </div>
                <span class="material-symbols-outlined text-secondary hidden sm:block">expand_more</span>
            </button>

            <!-- Dropdown Menu -->
            <div id="user-dropdown" dusk="user-dropdown" data-open="false" class="absolute right-0 mt-0 w-56 bg-surface rounded-xl border border-outline-variant shadow-[0_8px_24px_rgba(0,0,0,0.12)] opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-10 overflow-hidden">
                <div class="px-space-lg py-space-md border-b border-outline-variant sm:hidden">
                    <div class="flex items-center gap-space-xs">
                        <p class="font-label-md text-label-md text-on-surface">{{ auth()->user()->name }}</p>
                        @foreach (auth()->user()->roles as $role)
                            <span class="px-space-xs py-[1px] rounded-full bg-primary/10 text-primary font-label-sm text-label-sm uppercase">{{ $role->name }}</span>
                        @endforeach
                    </div>
                    <p class="font-body-sm text-body-sm text-secondary mt-space-xs">{{ auth()->user()->email }}</p>
                </div>
                <a href="{{ route('edit-profile') }}" class="flex items-center gap-space-md px-space-lg py-space-md text-secondary hover:bg-surface-container-low hover:text-on-surface transition-colors duration-150 border-b border-outline-variant">
                    <span class="material-symbols-outlined text-[20px]">person</span>
                    <span class="font-body-md text-body-md">Edit Profile</span>
                </a>
                <a href="{{ route('change-password') }}" class="flex items-center gap-space-md px-space-lg py-space-md text-secondary hover:bg-surface-container-low hover:text-on-surface transition-colors duration-150 border-b border-outline-variant">
                    <span class="material-symbols-outlined text-[20px]">lock</span>
                    <span class="font-body-md text-body-md">Change Password</span>
                </a>
                <form method="POST" action="{{ route('logout') }}" class="m-0">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-space-md px-space-lg py-space-md text-error hover:bg-error/5 transition-colors duration-150 font-body-md text-body-md">
                        <span class="material-symbols-outlined text-[20px]">logout</span>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>
