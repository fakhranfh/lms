<?php

namespace App\Livewire\Teachers;

use App\Enums\RoleName;
use App\Livewire\Concerns\ManagesUserForm;
use Livewire\Component;
use Livewire\WithFileUploads;

class TeacherForm extends Component
{
    use ManagesUserForm, WithFileUploads;

    protected function managedRole(): RoleName
    {
        return RoleName::Teacher;
    }

    protected function permissionPrefix(): string
    {
        return 'teachers';
    }

    protected function configKey(): string
    {
        return 'teachers';
    }

    protected function entityLabel(): string
    {
        return 'teacher';
    }

    public function render()
    {
        $isAdminUser = auth()->user()->hasRole(RoleName::Admin);

        return view('livewire.teachers.teacher-form')
            ->extends($isAdminUser ? 'layouts.admin' : 'layouts.app', ['topbarTitle' => $this->isEditing() ? 'Edit Teacher' : 'New Teacher'])
            ->section($isAdminUser ? 'admin-content' : 'app-content');
    }
}
