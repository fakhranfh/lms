<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = auth()->id();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'profile_photo' => ['nullable', 'image', 'max:5120', 'mimes:jpg,jpeg,png,gif'],
            'remove_photo' => ['nullable', 'boolean'],
            'timezone' => ['nullable', 'string', Rule::in(timezone_identifiers_list())],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Full name is required',
            'name.max' => 'Full name must not exceed 255 characters',
            'email.required' => 'Email address is required',
            'email.email' => 'Please enter a valid email address',
            'email.unique' => 'This email address is already in use',
            'profile_photo.image' => 'The profile photo must be an image',
            'profile_photo.max' => 'The profile photo must not exceed 5MB',
            'profile_photo.mimes' => 'The profile photo must be JPG, JPEG, PNG, or GIF',
            'timezone.in' => 'Please select a valid timezone',
        ];
    }
}
