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
        $user = $this->initializeUser($data, $roleIds);

        if ($photo) {
            $this->updateProfilePhoto($user, $photo);
        }

        return $user;
    }

    /**
     * Create a user with a photo that already lives in permanent storage
     * (e.g. promoted from a bulk-import temp upload), so no file upload
     * happens here — the URL is simply assigned.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $roleIds
     */
    public function createUserWithPhotoUrl(array $data, ?string $photoUrl, array $roleIds = []): User
    {
        $user = $this->initializeUser($data, $roleIds);

        if ($photoUrl) {
            $this->userRepository->update($user, ['profile_photo_path' => $photoUrl]);
        }

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $roleIds
     */
    private function initializeUser(array $data, array $roleIds): User
    {
        $user = $this->userRepository->create($data);
        $user->forceFill(['email_verified_at' => now()])->save();

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

    /**
     * Assign a photo that already lives in permanent storage (promoted from
     * a bulk-upload temp file), deleting the previous photo if any. No file
     * upload happens here — the URL is simply assigned.
     */
    public function updateProfilePhotoFromUrl(User $user, string $photoUrl): void
    {
        $this->deleteProfilePhotoFile($user);
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

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, string>
     */
    public function idsMatching(array $filters = []): array
    {
        return $this->userRepository->idsMatching($filters);
    }

    public function find(string $id): ?User
    {
        return $this->userRepository->find($id);
    }

    /**
     * Find a user by email regardless of school (emails are globally
     * unique) or soft-delete status — the database's unique constraint on
     * `email` isn't a partial index, so a soft-deleted user's email is
     * still physically taken and must be reported as such.
     */
    public function findByEmailAnySchool(string $email, ?string $ignoreUserId = null): ?User
    {
        return $this->userRepository->findByEmailAnySchool($email, $ignoreUserId);
    }

    /**
     * Describe why an email is unavailable, distinguishing "already in use
     * in another school" from a same-school conflict, or null if it's free.
     *
     * A soft-deleted user re-registering under the same school is not a
     * conflict — findTrashedInSchool() picks them up so they get restored
     * instead of blocked.
     */
    public function emailConflictMessage(string $email, ?string $currentSchoolId, ?string $ignoreUserId = null): ?string
    {
        $existing = $this->findByEmailAnySchool($email, $ignoreUserId);

        if ($existing === null) {
            return null;
        }

        $belongsToCurrentSchool = $currentSchoolId !== null
            && $this->userRepository->emailBelongsToSchool($email, $currentSchoolId, $ignoreUserId);

        if ($existing->trashed() && $belongsToCurrentSchool) {
            return null;
        }

        if ($currentSchoolId !== null && ! $belongsToCurrentSchool) {
            return 'This email is already in use in another school.';
        }

        return 'This email is already in use.';
    }

    /**
     * Find a soft-deleted user with the given email who belonged to the
     * given school, so they can be restored instead of re-created.
     */
    public function findTrashedInSchool(string $email, string $schoolId): ?User
    {
        return $this->userRepository->findTrashedInSchool($email, $schoolId);
    }

    /**
     * Restore a soft-deleted user rather than creating a duplicate, applying
     * the freshly submitted name/password/roles/photo as if provisioned anew.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $roleIds
     */
    public function restoreUser(User $user, array $data, ?UploadedFile $photo, array $roleIds = []): User
    {
        $this->userRepository->restore($user, [
            'name' => $data['name'],
            'password' => $data['password'],
        ]);

        if ($roleIds !== []) {
            $this->userRepository->syncRoles($user, $roleIds);
        }

        if ($photo) {
            $this->updateProfilePhoto($user, $photo);
        }

        return $user;
    }

    /**
     * Describe why a name is unavailable, or null if it's free.
     */
    public function nameConflictMessage(string $name, ?string $ignoreUserId = null): ?string
    {
        return $this->userRepository->existsByName($name, $ignoreUserId)
            ? 'This name is already taken.'
            : null;
    }

    public function syncRoles(User $user, array $roleIds): void
    {
        if ($user->hasRole(RoleName::Admin) && ! in_array($this->adminRoleId(), $roleIds)) {
            $this->guardLastAdmin($user, 'roles');
        }

        $this->userRepository->syncRoles($user, $roleIds);
    }

    /**
     * Soft delete a user, refusing to remove the last remaining Admin.
     */
    public function deleteUser(User $user): void
    {
        if ($user->hasRole(RoleName::Admin)) {
            $this->guardLastAdmin($user, 'user');
        }

        $this->userRepository->delete($user);
    }

    private function guardLastAdmin(User $user, string $errorKey): void
    {
        $otherAdmins = $this->roleRepository->otherUsersHaveRole(RoleName::Admin->value, $user->id);

        if (! $otherAdmins) {
            throw ValidationException::withMessages([
                $errorKey => __('At least one user must keep the admin role.'),
            ]);
        }
    }

    private function adminRoleId(): ?int
    {
        return $this->roleRepository->findIdByName(RoleName::Admin->value);
    }
}
