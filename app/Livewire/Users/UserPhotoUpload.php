<?php

namespace App\Livewire\Users;

use App\Enums\RoleName;
use App\Repositories\Role\RoleRepositoryInterface;
use App\Services\R2StorageService;
use App\Services\UserService;
use App\Support\CurrentSchool;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * @property-read LengthAwarePaginator $users
 * @property-read Collection $availableRoles
 */
class UserPhotoUpload extends Component
{
    use WithFileUploads;

    public ?string $search = null;

    public ?string $filterRole = null;

    public int $perPage = 20;

    public ?string $successMessage = null;

    /**
     * Whether the photo grid has been loaded yet. Kept false through the
     * initial render (triggered via wire:init) so the page paints instantly
     * with a skeleton in place of the grid, instead of blocking on the query.
     */
    public bool $usersLoaded = false;

    /**
     * Newly selected files, keyed by user id. Cleared as each one is staged
     * to R2 (uploadPhotos.{id}), so this never holds more than the files
     * currently mid-upload.
     *
     * @var array<string, UploadedFile>
     */
    public array $uploads = [];

    /**
     * Temp R2 URLs for photos staged but not yet saved, keyed by user id.
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
        $this->usersLoaded = true;
    }

    public function updatedUploads(): void
    {
        abort_unless(auth()->user()->can('users.edit'), 403);

        Validator::make(
            ['uploads' => $this->uploads],
            ['uploads.*' => ['image', 'max:5120']]
        )->validate();

        $r2Storage = app(R2StorageService::class);

        foreach ($this->uploads as $userId => $photo) {
            if (isset($this->stagedPhotoUrls[$userId])) {
                $r2Storage->delete($this->stagedPhotoUrls[$userId]);
            }

            $this->stagedPhotoUrls[$userId] = $r2Storage->uploadPublicFile($photo, 'tmp-imports/photos');
        }

        $this->uploads = [];
    }

    public function save(UserService $userService, R2StorageService $r2Storage): void
    {
        abort_unless(auth()->user()->can('users.edit'), 403);

        if ($this->stagedPhotoUrls === []) {
            return;
        }

        $updated = 0;

        foreach ($this->stagedPhotoUrls as $userId => $tempUrl) {
            $user = $userService->find($userId);

            if ($user === null) {
                $r2Storage->delete($tempUrl);

                continue;
            }

            $roleSlug = $user->roles->pluck('slug')->first() ?? 'users';
            $finalUrl = $r2Storage->promoteTempPhoto($tempUrl, "photos/{$roleSlug}");

            $userService->updateProfilePhotoFromUrl($user, $finalUrl);
            $updated++;
        }

        $this->stagedPhotoUrls = [];

        if ($updated > 0) {
            $this->successMessage = trans_choice('1 photo saved successfully.|:count photos saved successfully.', $updated, ['count' => $updated]);
        }

        unset($this->users);
    }

    #[Computed]
    public function users(): LengthAwarePaginator
    {
        return resolve(UserService::class)->paginate(
            filters: [
                'name' => $this->search,
                'role_id' => $this->filterRole,
                'sort' => 'name',
                'direction' => 'asc',
            ],
            with: ['roles'],
            perPage: $this->perPage,
        );
    }

    #[Computed]
    public function availableRoles(): Collection
    {
        $schoolId = resolve(CurrentSchool::class)->getSchoolId() ?? auth()->user()->school_id;

        return resolve(RoleRepositoryInterface::class)->get(['school_id' => $schoolId]);
    }

    public function render()
    {
        $isAdminUser = auth()->user()->hasRole(RoleName::Admin);

        return view('livewire.users.user-photo-upload', [
            'users' => $this->usersLoaded ? $this->users : null,
        ])
            ->extends($isAdminUser ? 'layouts.admin' : 'layouts.app', ['topbarTitle' => 'Bulk Upload Photos'])
            ->section($isAdminUser ? 'admin-content' : 'app-content');
    }
}
