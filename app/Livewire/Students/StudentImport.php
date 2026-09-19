<?php

namespace App\Livewire\Students;

use App\Enums\RoleName;
use App\Livewire\Concerns\ManagesUserImport;
use Livewire\Component;
use Livewire\WithFileUploads;

class StudentImport extends Component
{
    use ManagesUserImport, WithFileUploads;

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

        return view('livewire.students.student-import', [
            'templateUrl' => asset('templates/users-import-template-student.xlsx'),
        ])
            ->extends($isAdminUser ? 'layouts.admin' : 'layouts.app', ['topbarTitle' => 'Import Students'])
            ->section($isAdminUser ? 'admin-content' : 'app-content');
    }
}
