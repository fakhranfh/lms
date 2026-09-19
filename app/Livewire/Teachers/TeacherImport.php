<?php

namespace App\Livewire\Teachers;

use App\Enums\RoleName;
use App\Livewire\Concerns\ManagesUserImport;
use Livewire\Component;
use Livewire\WithFileUploads;

class TeacherImport extends Component
{
    use ManagesUserImport, WithFileUploads;

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

        return view('livewire.teachers.teacher-import', [
            'templateUrl' => asset('templates/users-import-template-teacher.xlsx'),
        ])
            ->extends($isAdminUser ? 'layouts.admin' : 'layouts.app', ['topbarTitle' => 'Import Teachers'])
            ->section($isAdminUser ? 'admin-content' : 'app-content');
    }
}
