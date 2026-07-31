<?php

namespace App\Livewire\Admin;

use App\Models\DemoLmsAccess;
use App\Models\School;
use App\Services\DemoLmsAccessService;
use Livewire\Component;

class DemoCredentials extends Component
{
    public ?string $selectedSchoolId = null;

    public ?DemoLmsAccess $schoolAdminAccess = null;

    public ?DemoLmsAccess $teacherAccess = null;

    public ?DemoLmsAccess $studentAccess = null;

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
            $this->schoolAdminAccess = null;
            $this->teacherAccess = null;
            $this->studentAccess = null;

            return;
        }

        $this->schoolAdminAccess = DemoLmsAccess::where('school_id', $this->selectedSchoolId)
            ->where('role', 'school-admin')
            ->where('expires_at', '>', now())
            ->latest('created_at')
            ->first();

        $this->teacherAccess = DemoLmsAccess::where('school_id', $this->selectedSchoolId)
            ->where('role', 'teacher')
            ->where('expires_at', '>', now())
            ->latest('created_at')
            ->first();

        $this->studentAccess = DemoLmsAccess::where('school_id', $this->selectedSchoolId)
            ->where('role', 'student')
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

        $this->schoolAdminAccess = $demoService->regenerateDemoAccess($school, 'school-admin');
        $this->teacherAccess = $demoService->regenerateDemoAccess($school, 'teacher');
        $this->studentAccess = $demoService->regenerateDemoAccess($school, 'student');
    }

    public function render()
    {
        return view('livewire.admin.demo-credentials', [
            'schools' => School::orderBy('name')->get(),
            'selectedSchool' => $this->selectedSchoolId ? School::find($this->selectedSchoolId) : null,
        ]);
    }
}
