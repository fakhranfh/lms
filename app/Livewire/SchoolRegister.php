<?php

namespace App\Livewire;

use App\Models\PricingTier;
use App\Models\School;
use App\Services\PricingTierService;
use App\Services\SchoolService;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
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

    #[Url(as: 'tier')]
    public string $tierId = '';

    public function mount(PricingTierService $pricingTierService): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('get-started');
        }

        $validTierIds = $pricingTierService->get(['is_active' => true])->pluck('id');

        if (! $validTierIds->contains($this->tierId)) {
            $this->tierId = $pricingTierService->get(['slug' => 'basic'])->first()?->id ?? '';
        }
    }

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
            'tierId' => ['bail', 'required', 'integer', 'exists:pricing_tiers,id'],
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

        $tier = PricingTier::findOrFail($this->tierId);
        $domain = $this->domainType === 'subdomain'
            ? $this->computeSubdomain()
            : $this->customDomain;

        $data = ['name' => $this->name, 'domain' => $domain, 'tier_id' => $this->tierId];

        if ($this->logo) {
            $data['logo'] = $this->logo;
        }

        $school = $schoolService->create($data);
        $schoolService->attachAdmin($school, auth()->user());

        if ((float) $tier->price === 0.0) {
            $this->redirectRoute('manage.schools.index');
        } else {
            $this->redirectRoute('school.payment.index', ['school' => $school]);
        }
    }

    public function render(PricingTierService $pricingTierService)
    {
        return view('livewire.school-register', [
            'tiers' => $pricingTierService->get(['is_active' => true]),
        ])
            ->extends('master', ['body_class' => 'bg-background text-on-background min-h-screen flex flex-col font-body-md'])
            ->section('content');
    }
}
