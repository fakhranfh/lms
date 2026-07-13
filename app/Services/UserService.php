<?php

namespace App\Services;

use App\Mail\PendingEmailVerificationMail;
use App\Models\User;
use App\Repositories\User\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserService
{
    public function __construct(private UserRepositoryInterface $userRepository) {}

    public function updateProfile(User $user, array $data): User
    {
        return $this->userRepository->update($user, $data);
    }

    public function updateProfilePhoto(User $user, UploadedFile $photo): void
    {
        $photoUrl = $this->userRepository->updateProfilePhoto($user, $photo);
        $user->update(['profile_photo_path' => $photoUrl]);
    }

    public function removeProfilePhoto(User $user): void
    {
        $this->userRepository->removeProfilePhoto($user);
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

    public function find(int $id): ?User
    {
        return $this->userRepository->find($id);
    }

    public function syncRoles(User $user, array $roleIds): void
    {
        if ($user->hasRole('admin') && ! in_array($this->adminRoleId(), $roleIds)) {
            $this->guardLastAdmin($user);
        }

        $this->userRepository->syncRoles($user, $roleIds);
    }

    private function guardLastAdmin(User $user): void
    {
        $otherAdmins = User::role('admin')->where('id', '!=', $user->id)->exists();

        if (! $otherAdmins) {
            throw ValidationException::withMessages([
                'roles' => __('At least one user must keep the admin role.'),
            ]);
        }
    }

    private function adminRoleId(): ?int
    {
        return Role::where('name', 'admin')->value('id');
    }
}
