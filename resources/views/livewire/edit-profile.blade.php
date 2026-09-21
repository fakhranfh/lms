@section('title', 'Edit Profile')

@php
    $userInitial = strtoupper(substr($name ?: 'U', 0, 1));
    $defaultAvatar = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22%3E%3Crect fill=%22%231E3A8A%22 width=%22100%22 height=%22100%22/%3E%3Ctext x=%2250%22 y=%2250%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22white%22 font-size=%2250%22 font-weight=%22bold%22%3E' . $userInitial . '%3C/text%3E%3C/svg%3E';
    $savedPhotoSrc = $photoPath ?: $defaultAvatar;
@endphp

<div x-data="{
        savedPhotoSrc: @js($savedPhotoSrc),
        defaultAvatarSrc: @js($defaultAvatar),
        previewPhoto(event) {
            const file = event.target.files[0];
            if (! file) { return }

            const reader = new FileReader();
            reader.onload = (e) => { this.$refs.photoPreview.src = e.target.result };
            reader.readAsDataURL(file);
        },
        cancelPhotoPreview() {
            this.$refs.photoPreview.src = this.savedPhotoSrc;
        },
        removePhotoPreview() {
            this.$refs.photoPreview.src = this.defaultAvatarSrc;
        },
    }">
    @if ($successMessage)
        <div class="mb-space-lg px-gutter py-space-md bg-success/10 border border-success/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-success text-[20px]" data-weight="fill">check_circle</span>
            <p class="font-body-md text-body-md text-success">{{ $successMessage }}</p>
        </div>
    @endif

    @if ($pendingEmailSent)
        <div class="mb-space-lg px-gutter py-space-md bg-warning/10 border border-warning/20 rounded-lg flex items-start gap-space-md">
            <span class="material-symbols-outlined text-warning text-[20px] mt-0.5" data-weight="fill">mark_email_unread</span>
            <p class="font-body-md text-body-md text-on-surface">
                A verification email has been sent to <strong>{{ $pendingEmailSent }}</strong>. Please check your inbox and click the link to confirm the email change.
            </p>
        </div>
    @endif

    <div class="flex items-center justify-center py-space-xl px-gutter">
        <div class="bg-surface w-full max-w-2xl rounded-xl border border-outline-variant p-space-lg md:p-space-xl shadow-[0_2px_8px_rgba(0,0,0,0.06)] hover:shadow-[0_4px_12px_rgba(0,0,0,0.1)] transition-shadow duration-200 profile-card">
            <div class="absolute inset-0 rounded-xl overflow-hidden pointer-events-none profile-card-backdrop"></div>
            <div class="relative z-10">
                <h2 class="font-headline-lg text-headline-lg text-on-surface mb-space-xs">Edit Profile</h2>
                <p class="font-body-md text-body-md text-secondary mb-space-xl">Update your personal information and profile settings.</p>

                <!-- Profile Photo Section -->
                <div class="flex flex-col md:flex-row items-center md:items-start gap-space-lg mb-space-xl pb-space-lg border-b border-outline-variant">
                    <label for="photo" class="relative group/avatar cursor-pointer flex-shrink-0">
                        <div class="w-28 h-28 rounded-full overflow-hidden border-4 border-surface-container-low shadow-sm relative hover:shadow-lg transition-shadow">
                            <img x-ref="photoPreview" src="{{ $savedPhotoSrc }}" alt="Profile" class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-on-surface/40 flex items-center justify-center opacity-0 group-hover/avatar:opacity-100 transition-opacity duration-200">
                                <span class="material-symbols-outlined text-surface text-[32px]">photo_camera</span>
                            </div>
                        </div>
                    </label>

                    <div class="text-center md:text-left flex flex-col justify-center flex-1">
                        <h3 class="font-headline-sm text-headline-sm text-on-surface">Profile Picture</h3>
                        <p class="font-body-sm text-body-sm text-secondary mt-space-xs">JPG, GIF or PNG. Max size of 5MB. Square image works best.</p>
                        <div class="mt-space-md flex gap-space-md justify-center md:justify-start">
                            <label for="photo" class="font-label-md text-label-md text-primary hover:text-primary-fixed-variant transition-colors cursor-pointer">Change Photo</label>
                            @if ($photo)
                                <button type="button" x-on:click="cancelPhotoPreview()" wire:click="cancelPhoto" class="font-label-md text-label-md text-secondary hover:text-on-surface transition-colors">Cancel</button>
                            @elseif ($photoPath)
                                <button type="button" x-on:click="removePhotoPreview()" wire:click="removePhotoNow" class="font-label-md text-label-md text-error hover:text-error transition-colors">Remove</button>
                            @endif
                        </div>
                        @error('photo')
                            <p class="text-error text-body-sm font-body-sm mt-space-xs">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Form Fields -->
                <form wire:submit="save" class="space-y-space-lg">
                    <input type="file" id="photo" wire:model="photo" x-on:change="previewPhoto($event)" accept="image/jpeg,image/png,image/gif" class="hidden" />

                    <div class="grid grid-cols-1 gap-space-lg">
                        <!-- Email Address -->
                        <div class="space-y-space-xs">
                            <div class="flex items-center gap-space-xs">
                                <label class="font-label-md text-label-md text-on-surface" for="email">Email Address</label>
                                @if ($currentPendingEmail)
                                    <span class="inline-flex items-center gap-space-xs px-space-xs py-space-xxs rounded-full bg-warning/10 border border-warning/20">
                                        <span class="material-symbols-outlined text-[12px] text-warning" data-weight="fill">schedule</span>
                                        <span class="font-label-sm text-label-sm text-warning">Pending Verification</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-space-xs px-space-xs py-space-xxs rounded-full bg-success/10 border border-success/20">
                                        <span class="material-symbols-outlined text-[12px] text-success" data-weight="fill">check_circle</span>
                                        <span class="font-label-sm text-label-sm text-success">Verified</span>
                                    </span>
                                @endif
                            </div>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute inset-y-0 left-3 flex items-center text-secondary/60 text-[20px]">mail</span>
                                <input class="w-full bg-surface-container-lowest border border-outline-variant text-on-surface font-body-md text-body-md rounded-lg py-space-sm pl-10 pr-space-md focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all @error('email') border-error @enderror" id="email" wire:model="email" type="email" required />
                            </div>
                            @if ($currentPendingEmail)
                                <p class="font-body-sm text-body-sm text-secondary mt-space-xs">
                                    Pending change to <strong>{{ $currentPendingEmail }}</strong>. Check your inbox to verify the new email.
                                </p>
                            @endif
                            @error('email')
                                <p class="text-error text-body-sm font-body-sm mt-space-xs">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Full Name -->
                        <div class="space-y-space-xs">
                            <div class="flex items-center">
                                <label class="font-label-md text-label-md text-on-surface" for="name">Full Name</label>
                            </div>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute inset-y-0 left-3 flex items-center text-secondary/60 text-[20px]">person</span>
                                <input class="w-full bg-surface-container-lowest border border-outline-variant text-on-surface font-body-md text-body-md rounded-lg py-space-sm pl-10 pr-space-md focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all @error('name') border-error @enderror" id="name" wire:model="name" type="text" required />
                            </div>
                            @error('name')
                                <p class="text-error text-body-sm font-body-sm mt-space-xs">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Timezone -->
                        <div class="space-y-space-xs">
                            <div class="flex items-center">
                                <label class="font-label-md text-label-md text-on-surface" for="timezone">Timezone</label>
                            </div>
                            <x-searchable-select
                                model="timezone"
                                placeholder="Select timezone"
                                :options="collect(timezone_identifiers_list())->map(fn ($tz) => ['id' => $tz, 'label' => $tz])"
                                :selectedLabel="$timezone"
                            />
                            @error('timezone')
                                <p class="text-error text-body-sm font-body-sm mt-space-xs">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="pt-space-lg mt-space-lg border-t border-outline-variant flex flex-col sm:flex-row justify-between gap-space-md">
                        <a href="{{ route('change-password') }}" class="px-space-lg py-space-sm rounded-lg border border-outline-variant bg-surface text-on-surface font-label-md text-label-md hover:bg-surface-container-low transition-colors flex items-center justify-center sm:justify-start gap-space-sm">
                            <span class="material-symbols-outlined text-[18px]">lock</span>
                            Change Password
                        </a>
                        <div class="flex gap-space-md">
                            <a href="{{ route('dashboard') }}" class="px-space-lg py-space-sm rounded-lg border border-outline-variant bg-surface text-on-surface font-label-md text-label-md hover:bg-surface-container-low transition-colors inline-block">
                                Cancel
                            </a>
                            <button id="save-changes-btn" wire:loading.attr="disabled" wire:target="save" class="px-space-lg py-space-sm rounded-lg bg-primary text-on-primary font-label-md text-label-md hover:bg-on-primary-fixed-variant transition-colors flex items-center gap-space-sm shadow-sm disabled:opacity-60 disabled:cursor-not-allowed" type="submit">
                                <span wire:loading.remove wire:target="save" class="material-symbols-outlined text-[18px]">save</span>
                                <span wire:loading wire:target="save" class="w-[18px] h-[18px] border-2 border-on-primary/30 border-t-on-primary rounded-full animate-spin"></span>
                                <span wire:loading.remove wire:target="save">Save Changes</span>
                                <span wire:loading wire:target="save">Saving...</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('styles')
    <style>
        .profile-card {
            position: relative;
        }

        .profile-card-backdrop::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, #004ac6, #2563eb);
            border-radius: 50%;
            opacity: 0.05;
            pointer-events: none;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        .animate-spin {
            animation: spin 1s linear infinite;
        }
    </style>
@endpush
