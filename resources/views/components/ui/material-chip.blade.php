@props(['file'])

@php
    $badge = match ($file->type) {
        \App\Enums\MaterialType::Video => ['label' => 'VID', 'type' => 'video'],
        \App\Enums\MaterialType::PDF => ['label' => 'PDF', 'type' => 'pdf'],
        \App\Enums\MaterialType::Document => ['label' => 'DOC', 'type' => 'doc'],
        \App\Enums\MaterialType::Presentation => ['label' => 'PPT', 'type' => 'ppt'],
        default => ['label' => strtoupper(substr($file->type->value, 0, 4)), 'type' => 'generic'],
    };
@endphp

<a
    href="#"
    @if ($file->type === \App\Enums\MaterialType::Video)
        data-video-preview="{{ $file->file_url }}"
    @else
        data-file-preview="{{ $file->file_url }}"
        data-file-preview-name="{{ $file->title }}"
    @endif
    class="rte-file-chip"
>
    <span class="rte-file-chip-icon rte-file-chip-icon--{{ $badge['type'] }}">{{ $badge['label'] }}</span>
    <span class="rte-file-chip-info">
        <span class="rte-file-chip-name">{{ $file->title }}</span>
        <span class="rte-file-chip-size">{{ $file->type->label() }}</span>
    </span>
</a>
