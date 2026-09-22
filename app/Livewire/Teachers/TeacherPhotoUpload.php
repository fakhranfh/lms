<?php

namespace App\Livewire\Teachers;

use App\Enums\RoleName;
use App\Repositories\Role\RoleRepositoryInterface;
use App\Services\R2StorageService;
use App\Services\UserService;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * @property-read LengthAwarePaginator $teachers
 */
class TeacherPhotoUpload extends Component
{
    use WithFileUploads;

    public ?string $search = null;

    public int $perPage = 20;

    public ?string $successMessage = null;

    /**
     * Whether the photo grid has been loaded yet. Kept false through the
     * initial render (triggered via wire:init) so the page paints instantly
     * with a skeleton in place of the grid, instead of blocking on the query.
     */
    public bool $teachersLoaded = false;

    /**
     * Newly selected files, keyed by teacher id. Cleared as each one is
     * staged to R2 (uploadPhotos.{id}), so this never holds more than the
     * files currently mid-upload.
     *
     * @var array<string, UploadedFile>
     */
    public array $uploads = [];

    /**
     * Temp R2 URLs for photos staged but not yet saved, keyed by teacher id.
     *
     * @var array<string, string>
     */
    public array $stagedPhotoUrls = [];

    public function mount(): void
    {
        $this->successMessage = session('success');
    }

    public function loadUsers(): void
    {
        $this->teachersLoaded = true;
    }

    public function updatedUploads(): void
    {
        abort_unless(auth()->user()->can('teachers.edit'), 403);

        Validator::make(
            ['uploads' => $this->uploads],
            ['uploads.*' => ['image', 'max:5120']]
        )->validate();

        $r2Storage = app(R2StorageService::class);

        foreach ($this->uploads as $teacherId => $photo) {
            if (isset($this->stagedPhotoUrls[$teacherId])) {
                $r2Storage->delete($this->stagedPhotoUrls[$teacherId]);
            }

            $this->stagedPhotoUrls[$teacherId] = $r2Storage->uploadPublicFile($photo, 'tmp-imports/photos');
        }

        $this->uploads = [];
    }

    public function save(UserService $userService, R2StorageService $r2Storage): void
    {
        abort_unless(auth()->user()->can('teachers.edit'), 403);

        if ($this->stagedPhotoUrls === []) {
            return;
        }

        $updated = 0;

        foreach ($this->stagedPhotoUrls as $teacherId => $tempUrl) {
            $teacher = $userService->find($teacherId);

            if ($teacher === null) {
                $r2Storage->delete($tempUrl);

                continue;
            }

            $roleSlug = $teacher->roles->pluck('slug')->first() ?? 'teachers';
            $finalUrl = $r2Storage->promoteTempPhoto($tempUrl, "photos/{$roleSlug}");

            $userService->updateProfilePhotoFromUrl($teacher, $finalUrl);
            $updated++;
        }

        $this->stagedPhotoUrls = [];

        if ($updated > 0) {
            $this->successMessage = trans_choice('1 photo saved successfully.|:count photos saved successfully.', $updated, ['count' => $updated]);
        }

        unset($this->teachers);
    }

    private function teacherRoleId(): ?int
    {
        $schoolId = auth()->user()->school_id;

        if ($schoolId === null) {
            return null;
        }

        return resolve(RoleRepositoryInterface::class)
            ->get(['school_id' => $schoolId, 'name' => RoleName::Teacher->value])
            ->first()?->id;
    }

    #[Computed]
    public function teachers(): LengthAwarePaginator
    {
        return resolve(UserService::class)->paginate(
            filters: [
                'search' => $this->search,
                'role_id' => $this->teacherRoleId(),
                'sort' => 'name',
                'direction' => 'asc',
            ],
            with: ['roles'],
            perPage: $this->perPage,
        );
    }

    public function render()
    {
        $isAdminUser = auth()->user()->hasRole(RoleName::Admin);

        return view('livewire.teachers.teacher-photo-upload', [
            'teachers' => $this->teachersLoaded ? $this->teachers : null,
        ])
            ->extends($isAdminUser ? 'layouts.admin' : 'layouts.app', ['topbarTitle' => 'Bulk Upload Photos'])
            ->section($isAdminUser ? 'admin-content' : 'app-content');
    }
}
