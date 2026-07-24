<?php

namespace App\Livewire;

use App\Models\School;
use App\Services\SchoolService;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class SchoolRegister extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $domainType = 'subdomain';

    public string $subdomain = '';

    public string $customDomain = '';

    public $logo = null;

    public ?string $registeredUrl = null;

    public ?string $registeredDomain = null;

    public function updatedSubdomain(): void
    {
        $this->subdomain = Str::lower(trim($this->subdomain));
        $this->validateOnly('subdomain');
    }

    public function updatedCustomDomain(): void
    {
        $this->customDomain = Str::lower(trim($this->customDomain));
        $this->validateOnly('customDomain');
    }

    public function updatedDomainType(): void
    {
        $this->resetErrorBag(['subdomain', 'customDomain']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:5120', 'mimes:jpg,jpeg,png,gif,webp'],
        ];

        if ($this->domainType === 'subdomain') {
            $rules['subdomain'] = [
                'required',
                'string',
                'max:63',
                'regex:/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/i',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (School::where('domain', $this->computeSubdomain())->exists()) {
                        $fail('This subdomain is already taken.');
                    }
                },
            ];

            return $rules;
        }

        $rules['customDomain'] = [
            'required',
            'string',
            'max:255',
            'regex:/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)+$/i',
            'unique:schools,domain',
        ];

        return $rules;
    }

    protected function computeSubdomain(): string
    {
        return $this->subdomain.'.'.config('app.domain');
    }

    public function save(SchoolService $schoolService): void
    {
        $this->validate();

        $domain = $this->domainType === 'subdomain'
            ? $this->computeSubdomain()
            : $this->customDomain;

        $data = ['name' => $this->name, 'domain' => $domain];

        if ($this->logo) {
            $data['logo'] = $this->logo;
        }

        $school = $schoolService->create($data);

        $this->registeredUrl = $schoolService->buildRegisterUrl(
            $school,
            request()->getScheme(),
            request()->getPort()
        );
        $this->registeredDomain = $school->domain;
    }

    public function render()
    {
        return view('livewire.school-register')
            ->extends('master', ['body_class' => 'bg-background text-on-background min-h-screen flex items-center justify-center p-gutter font-body-md'])
            ->section('content');
    }
}
