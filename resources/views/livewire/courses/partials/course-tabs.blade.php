<div class="border-b border-outline-variant mb-space-lg">
    <nav class="flex gap-space-lg overflow-x-auto">
        @foreach ($tabs as $tab)
            <a
                href="{{ $tab['href'] }}"
                wire:navigate
                onclick="this.closest('nav').querySelectorAll('a').forEach(el => el.classList.remove('border-primary', 'text-primary')); this.closest('nav').querySelectorAll('a').forEach(el => el.classList.add('border-transparent', 'text-on-surface-variant')); this.classList.remove('border-transparent', 'text-on-surface-variant'); this.classList.add('border-primary', 'text-primary');"
                class="flex items-center gap-space-xs px-space-sm py-space-md border-b-2 font-label-md text-label-md whitespace-nowrap transition-colors {{ $tab['active'] ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface' }}"
            >
                <span class="material-symbols-outlined text-[18px]">{{ $tab['icon'] }}</span>
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>
</div>
