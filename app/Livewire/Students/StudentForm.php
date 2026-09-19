<?php

namespace App\Livewire\Students;

use App\Enums\RoleName;
use App\Livewire\Concerns\ManagesUserForm;
use Livewire\Component;
use Livewire\WithFileUploads;

class StudentForm extends Component
{
    use ManagesUserForm, WithFileUploads;

    protected function managedRole(): RoleName
    {
        return RoleName::Student;
    }

    protected function permissionPrefix(): string
    {
        return 'students';
    }

    protected function configKey(): string
    {
        return 'students';
    }

    protected function entityLabel(): string
    {
        return 'student';
    }

    public function render()
    {
        $isAdminUser = auth()->user()->hasRole(RoleName::Admin);

        return view('livewire.students.student-form')
            ->extends($isAdminUser ? 'layouts.admin' : 'layouts.app', ['topbarTitle' => $this->isEditing() ? 'Edit Student' : 'New Student'])
            ->section($isAdminUser ? 'admin-content' : 'app-content');
    }
}
