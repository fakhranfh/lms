<?php

namespace App\Livewire\MediaLibrary;

use App\Enums\MaterialType;
use App\Services\MediaLibraryService;
use App\Services\R2StorageService;
use App\Support\CurrentSchool;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class MediaLibraryIndex extends Component
{
    use WithPagination;

    /**
     * wire:target list of everything that reloads the media grid, so the
     * skeleton shows for search/filter/pagination/delete alike.
     */
    protected const REFRESH_TARGETS = 'search,typeFilter,perPage,deleteMedia,bulkDelete,gotoPage,previousPage,nextPage';

    public ?string $search = null;

    public ?string $typeFilter = null;

    public int $perPage = 12;

    public ?string $errorMessage = null;

    public ?string $successMessage = null;

    private ?string $schoolId = null;

    public function mount(CurrentSchool $currentSchool): void
    {
        abort_unless(auth()->user()->can('media.view'), 403);

        $this->schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    private function getSchoolId(): ?string
    {
        return $this->schoolId ?? auth()->user()->school_id;
    }

    /**
     * @return array{url?: string, key?: string, error?: string}
     */
    public function generateUploadUrl(string $filename, string $materialType, MediaLibraryService $mediaLibraryService): array
    {
        abort_unless(auth()->user()->can('media.create'), 403);

        try {
            $type = MaterialType::tryFrom($materialType);
            if (! $type) {
                return ['error' => 'Invalid material type'];
            }

            return $mediaLibraryService->generatePresignedUploadUrl($this->getSchoolId(), $filename, $materialType);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{error?: string}
     */
    public function finalizeUpload(array $data, MediaLibraryService $mediaLibraryService): array
    {
        abort_unless(auth()->user()->can('media.create'), 403);

        try {
            $mediaLibraryService->finalizeUpload($this->getSchoolId(), auth()->id(), $data);
            $this->errorMessage = null;
            $this->successMessage = __('Media uploaded successfully.');
            $this->resetPage();

            return [];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function updateTitle(string $mediaId, string $title, MediaLibraryService $mediaLibraryService): void
    {
        abort_unless(auth()->user()->can('media.create'), 403);

        try {
            if (trim($title) === '') {
                return;
            }

            $mediaLibraryService->updateMetadata($mediaId, ['title' => trim($title)]);
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    #[On('delete-confirmed')]
    public function deleteMedia(string $id, MediaLibraryService $mediaLibraryService): void
    {
        abort_unless(auth()->user()->can('media.delete'), 403);

        $this->successMessage = null;
        $this->errorMessage = null;

        $item = $mediaLibraryService->list($this->getSchoolId())->find($id);
        if (! $item) {
            return;
        }

        $mediaLibraryService->delete($id);
        $this->successMessage = __('Media deleted successfully.');
        $this->resetPage();
    }

    /**
     * Deletes the given media ids, scoped to the current school so a
     * tampered id list from the client can't delete another school's media.
     * Selection itself is tracked client-side in Alpine; this is only
     * called once the user confirms the bulk delete.
     *
     * @param  array<int, string>  $ids
     */
    public function bulkDelete(array $ids, MediaLibraryService $mediaLibraryService): void
    {
        abort_unless(auth()->user()->can('media.delete'), 403);

        $this->successMessage = null;
        $this->errorMessage = null;

        if (empty($ids)) {
            return;
        }

        $scopedIds = $mediaLibraryService
            ->list($this->getSchoolId())
            ->whereIn('id', $ids)
            ->pluck('id');

        foreach ($scopedIds as $id) {
            $mediaLibraryService->delete($id);
        }

        $this->successMessage = trans_choice(':count media item deleted successfully.|:count media items deleted successfully.', $scopedIds->count(), ['count' => $scopedIds->count()]);
        $this->resetPage();
    }

    public function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }

    /**
     * @return array{used: string, remaining: string, percentage: float, color: string, warning: string|null, limit_gb: int|null}
     */
    public function getQuotaInfo(?R2StorageService $r2Service = null): array
    {
        $r2Service ??= app(R2StorageService::class);

        $quota = $r2Service->checkSchoolQuota($this->getSchoolId());
        $percentage = $quota['percentage'];

        $color = match (true) {
            $percentage < 80 => 'text-green-600',
            $percentage < 90 => 'text-yellow-600',
            $percentage < 100 => 'text-orange-600',
            default => 'text-red-600',
        };

        $warning = match (true) {
            $percentage >= 100 => '❌ Quota full. Uploads blocked.',
            $percentage >= 90 => '⚠️ Quota at 90%. Upload may fail soon.',
            $percentage >= 80 => '⚠️ Quota at 80%. Consider freeing space.',
            default => null,
        };

        return [
            'used' => $this->formatBytes($quota['used']),
            'remaining' => $this->formatBytes($quota['remaining']),
            'percentage' => round($percentage, 1),
            'color' => $color,
            'warning' => $warning,
            'limit_gb' => $quota['limit_gb'] ?? null,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getExtensionToTypeMap(): array
    {
        $map = [];
        foreach (MaterialType::cases() as $type) {
            foreach ($type->allowedExtensions() as $extension) {
                $map[$extension] = $type->value;
            }
        }

        return $map;
    }

    /**
     * @return array<int, int>
     */
    public function getPerPageOptions(): array
    {
        return [12, 24, 48, 96];
    }

    public function render(MediaLibraryService $mediaLibraryService)
    {
        $perPage = in_array($this->perPage, $this->getPerPageOptions(), true) ? $this->perPage : 12;

        $items = $mediaLibraryService
            ->list($this->getSchoolId(), $this->typeFilter, $this->search)
            ->paginate($perPage);

        $extensionTypeMap = $this->getExtensionToTypeMap();

        return view('livewire.media-library.media-library-index', [
            'items' => $items,
            'materialTypes' => MaterialType::cases(),
            'extensionTypeMap' => $extensionTypeMap,
            'acceptedExtensions' => implode(',', array_map(fn (string $ext) => ".{$ext}", array_keys($extensionTypeMap))),
            'refreshTargets' => self::REFRESH_TARGETS,
        ])
            ->extends('layouts.app', ['topbarTitle' => 'Media Library'])
            ->section('app-content');
    }
}
