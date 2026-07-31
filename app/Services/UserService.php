<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Mail\PendingEmailVerificationMail;
use App\Models\User;
use App\Repositories\Role\RoleRepositoryInterface;
use App\Repositories\User\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RoleRepositoryInterface $roleRepository,
        private R2StorageService $r2Storage,
    ) {}

    /**
     * Create a user (e.g. Teacher/Student) from the School Admin panel, optionally
     * with a profile photo and role assignment. The user is created already
     * email-verified since they were provisioned directly by an admin, not
     * through self-registration.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $roleIds
     */
    public function createUser(array $data, ?UploadedFile $photo, array $roleIds = []): User
    {
        $user = $this->userRepository->create($data);
        $user->forceFill(['email_verified_at' => now()])->save();

        if ($photo) {
            $this->updateProfilePhoto($user, $photo);
        }

        if ($roleIds !== []) {
            $this->userRepository->syncRoles($user, $roleIds);
        }

        return $user;
    }

    public function updateProfile(User $user, array $data): User
    {
        return $this->userRepository->update($user, $data);
    }

    public function updateProfilePhoto(User $user, UploadedFile $photo): void
    {
        $this->deleteProfilePhotoFile($user);

        $photoUrl = $this->r2Storage->uploadPublicFile($photo, 'profile-photos');
        $this->userRepository->update($user, ['profile_photo_path' => $photoUrl]);
    }

    public function removeProfilePhoto(User $user): void
    {
        $this->deleteProfilePhotoFile($user);
        $this->userRepository->update($user, ['profile_photo_path' => null]);
    }

    private function deleteProfilePhotoFile(User $user): void
    {
        if ($user->profile_photo_path) {
            $this->r2Storage->delete($user->profile_photo_path);
        }
    }

    public function changePassword(User $user, string $password): void
    {
        $this->userRepository->update($user, ['password' => $password]);
    }

    public function setPendingEmail(User $user, string $pendingEmail): void
    {
        $this->userRepository->setPendingEmail($user, $pendingEmail);
    }

    public function confirmPendingEmail(User $user): void
    {
        $this->userRepository->confirmPendingEmail($user);
    }

    public function sendPendingEmailVerification(User $user, string $verificationUrl): void
    {
        if (! config('features.email_enabled')) {
            return;
        }

        Mail::to($user->pending_email)->send(new PendingEmailVerificationMail($user, $verificationUrl));
    }

    public function getAllWithRoles(): Collection
    {
        return $this->userRepository->getAll(['roles']);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function paginate(array $filters = [], array $with = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->paginate($filters, $with, $perPage);
    }

    public function find(string $id): ?User
    {
        return $this->userRepository->find($id);
    }

    public function syncRoles(User $user, array $roleIds): void
    {
        if ($user->hasRole(RoleName::Admin) && ! in_array($this->adminRoleId(), $roleIds)) {
            $this->guardLastAdmin($user);
        }

        $this->userRepository->syncRoles($user, $roleIds);
    }

    private function guardLastAdmin(User $user): void
    {
        $otherAdmins = $this->roleRepository->otherUsersHaveRole(RoleName::Admin->value, $user->id);

        if (! $otherAdmins) {
            throw ValidationException::withMessages([
                'roles' => __('At least one user must keep the admin role.'),
            ]);
        }
    }

    private function adminRoleId(): ?int
    {
        return $this->roleRepository->findIdByName(RoleName::Admin->value);
    }
}
