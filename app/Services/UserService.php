<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Mail\PendingEmailVerificationMail;
use App\Models\User;
use App\Repositories\Role\RoleRepositoryInterface;
use App\Repositories\User\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RoleRepositoryInterface $roleRepository,
        private R2StorageService $r2Storage,
    ) {}

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
