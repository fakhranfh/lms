<?php

namespace App\Livewire\Admin;

use App\Enums\AdminFeeType;
use App\Services\SettingsService;
use Livewire\Component;

class BillingSettings extends Component
{
    public string $vat_rate = '0';

    public string $admin_fee_type = AdminFeeType::Percentage->value;

    public string $admin_fee_rate = '0';

    public string $admin_fee_nominal = '0';

    public ?string $successMessage = null;

    public function mount(SettingsService $settingsService): void
    {
        $this->vat_rate = (string) round($settingsService->getVatRate() * 100, 2);
        $this->admin_fee_type = $settingsService->getAdminFeeType()->value;
        $this->admin_fee_rate = (string) round($settingsService->getAdminFeeRate() * 100, 2);
        $this->admin_fee_nominal = (string) $settingsService->getAdminFeeNominal();
        $this->successMessage = session('success');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'vat_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'admin_fee_type' => ['required', 'in:'.implode(',', array_column(AdminFeeType::cases(), 'value'))],
            'admin_fee_rate' => ['required_if:admin_fee_type,'.AdminFeeType::Percentage->value, 'numeric', 'min:0', 'max:100'],
            'admin_fee_nominal' => ['required_if:admin_fee_type,'.AdminFeeType::Fixed->value, 'numeric', 'min:0'],
        ];
    }

    public function update(SettingsService $settingsService)
    {
        abort_unless(auth()->user()->can('settings.update'), 403);

        $data = $this->validate();

        $settingsService->updateBillingRates(
            (float) $data['vat_rate'] / 100,
            AdminFeeType::from($data['admin_fee_type']),
            (float) ($data['admin_fee_rate'] ?? 0) / 100,
            (float) ($data['admin_fee_nominal'] ?? 0),
        );

        session()->flash('success', 'Billing settings updated successfully.');

        return redirect()->route('admin.settings.index');
    }

    public function render()
    {
        return view('livewire.admin.billing-settings', [
            'adminFeeTypes' => AdminFeeType::cases(),
        ])
            ->extends('layouts.admin')
            ->section('admin-content');
    }
}
