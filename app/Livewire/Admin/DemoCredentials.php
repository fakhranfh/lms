<?php

namespace App\Livewire\Admin;

use App\Models\DemoLmsAccess;
use App\Models\School;
use App\Services\DemoLmsAccessService;
use Livewire\Component;

class DemoCredentials extends Component
{
    public ?string $selectedSchoolId = null;

    public ?DemoLmsAccess $demoAccess = null;

    public function mount()
    {
        $school = School::first();
        if ($school) {
            $this->selectedSchoolId = $school->id;
            $this->loadDemoAccess();
        }
    }

    public function updatedSelectedSchoolId()
    {
        $this->loadDemoAccess();
    }

    public function loadDemoAccess()
    {
        if (! $this->selectedSchoolId) {
            $this->demoAccess = null;

            return;
        }

        $this->demoAccess = DemoLmsAccess::where('school_id', $this->selectedSchoolId)
            ->where('expires_at', '>', now())
            ->latest('created_at')
            ->first();
    }

    public function generateCredentials()
    {
        if (! $this->selectedSchoolId) {
            return;
        }

        $school = School::find($this->selectedSchoolId);
        if (! $school) {
            return;
        }

        $demoService = app(DemoLmsAccessService::class);

        if ($this->demoAccess) {
            $this->demoAccess = $demoService->regenerateDemoAccess($school);
        } else {
            $this->demoAccess = $demoService->getOrCreateDemoAccess($school);
        }
    }

    public function render()
    {
        return view('livewire.admin.demo-credentials', [
            'schools' => School::orderBy('name')->get(),
            'selectedSchool' => $this->selectedSchoolId ? School::find($this->selectedSchoolId) : null,
        ]);
    }
}
