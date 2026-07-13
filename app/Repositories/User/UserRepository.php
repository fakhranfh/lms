<?php

namespace App\Repositories\User;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class UserRepository implements UserRepositoryInterface
{
    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user;
    }

    public function updateProfilePhoto(User $user, UploadedFile $photo): string
    {
        $this->removeProfilePhotoFile($user);

        $path = $photo->store('profile-photos', 'public');

        return Storage::url($path);
    }

    public function removeProfilePhoto(User $user): void
    {
        $this->removeProfilePhotoFile($user);
        $user->update(['profile_photo_path' => null]);
    }

    public function setPendingEmail(User $user, string $pendingEmail): void
    {
        $user->update(['pending_email' => $pendingEmail]);
    }

    public function confirmPendingEmail(User $user): void
    {
        $user->update([
            'email' => $user->pending_email,
            'pending_email' => null,
            'email_verified_at' => now(),
        ]);
    }

    public function getAll(array $with = []): Collection
    {
        return User::with($with)->get();
    }

    public function find(int $id): ?User
    {
        return User::find($id);
    }

    public function syncRoles(User $user, array $roleIds): void
    {
        $roles = Role::whereIn('id', $roleIds)->get();
        $user->syncRoles($roles);
    }

    private function removeProfilePhotoFile(User $user): void
    {
        if ($user->profile_photo_path) {
            $path = str_replace('/storage/', '', $user->profile_photo_path);
            Storage::disk('public')->delete($path);
        }
    }
}
