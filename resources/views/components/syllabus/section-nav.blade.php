@props(['sections', 'idPrefix' => 'section-'])

<nav
    x-data="{
        active: '{{ array_key_first($sections) }}',
        goTo(key) {
            this.active = key;
            const target = document.getElementById('{{ $idPrefix }}' + key);
            const scrollContainer = this.$el.closest('main');
            if (! target || ! scrollContainer) { return; }
            const navBottom = this.$el.getBoundingClientRect().bottom;
            const targetTop = target.getBoundingClientRect().top + scrollContainer.scrollTop - navBottom - 16;
            scrollContainer.scrollTo({ top: targetTop, behavior: 'smooth' });
        },
    }"
    class="relative sticky top-0 z-10 bg-background flex flex-wrap gap-space-sm pt-space-xxs pb-space-xs mb-space-lg border-b border-outline-variant before:content-[''] before:absolute before:left-0 before:right-0 before:-top-space-lg before:h-space-lg before:bg-background before:-z-10"
>
    @foreach ($sections as $sectionKey => $sectionLabel)
        <a
            href="#{{ $idPrefix }}{{ $sectionKey }}"
            @click.prevent="goTo('{{ $sectionKey }}')"
            class="px-space-md py-space-xs rounded-lg font-label-sm text-label-sm whitespace-nowrap transition-colors"
            :class="active === '{{ $sectionKey }}' ? 'bg-primary text-on-primary' : 'bg-surface-container text-on-surface-variant hover:text-on-surface'"
        >
            {{ $sectionLabel }}
        </a>
    @endforeach
</nav>
