<?php

namespace App\Livewire;

use App\Http\Requests\User\UpdateProfileRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Support\Facades\URL;
use Livewire\Component;
use Livewire\WithFileUploads;

class EditProfile extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $email = '';

    public ?string $timezone = null;

    public $photo = null;

    public bool $removePhoto = false;

    public ?string $photoPath = null;

    public ?string $currentPendingEmail = null;

    public ?string $successMessage = null;

    public ?string $pendingEmailSent = null;

    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();

        $this->name = $user->name;
        $this->email = $user->email;
        $this->timezone = $user->timezone ?? 'UTC';
        $this->photoPath = $user->profile_photo_path;
        $this->currentPendingEmail = $user->pending_email;
        $this->successMessage = session('success');
    }

    protected function rules(): array
    {
        $rules = (new UpdateProfileRequest)->rules();

        $rules['photo'] = $rules['profile_photo'];
        unset($rules['profile_photo'], $rules['remove_photo']);

        return $rules;
    }

    protected function messages(): array
    {
        $messages = (new UpdateProfileRequest)->messages();

        foreach ($messages as $key => $message) {
            if (str_starts_with($key, 'profile_photo.')) {
                $messages['photo.'.substr($key, strlen('profile_photo.'))] = $message;
                unset($messages[$key]);
            }
        }

        return $messages;
    }

    public function removePhotoNow(): void
    {
        $this->photo = null;
        $this->removePhoto = true;
        $this->photoPath = null;
    }

    public function save(UserService $userService): void
    {
        $this->successMessage = null;
        $this->pendingEmailSent = null;

        $this->validate();

        /** @var User $user */
        $user = auth()->user();

        if ($this->photo) {
            $userService->updateProfilePhoto($user, $this->photo);
            $this->photoPath = $user->fresh()->profile_photo_path;
            $this->dispatch('profile-photo-updated', photoUrl: $this->photoPath);
        }

        if ($this->removePhoto) {
            $userService->removeProfilePhoto($user);
            $this->photoPath = null;
            $this->dispatch('profile-photo-updated', photoUrl: null);
        }

        $this->photo = null;
        $this->removePhoto = false;

        $data = ['name' => $this->name, 'timezone' => $this->timezone];

        if ($this->email !== $user->email && config('features.email_enabled')) {
            $pendingEmail = $this->email;

            $userService->setPendingEmail($user, $pendingEmail);
            $userService->updateProfile($user, $data);

            $verificationUrl = URL::temporarySignedRoute(
                'profile.verify-email-change',
                now()->addMinutes(60),
                ['user' => $user->id],
            );

            $userService->sendPendingEmailVerification($user, $verificationUrl);

            $this->email = $user->email;
            $this->currentPendingEmail = $pendingEmail;
            $this->pendingEmailSent = $pendingEmail;

            return;
        }

        $data['email'] = $this->email;
        $userService->updateProfile($user, $data);

        $this->currentPendingEmail = null;
        $this->successMessage = 'Profile updated successfully.';
    }

    public function render()
    {
        return view('livewire.edit-profile')
            ->extends('layouts.app', ['topbarTitle' => 'Edit Profile'])
            ->section('app-content');
    }
}
