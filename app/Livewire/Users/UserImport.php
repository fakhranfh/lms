<?php

namespace App\Livewire\Users;

use App\Enums\RoleName;
use App\Repositories\User\UserImportRepositoryInterface;
use App\Support\CurrentSchool;
use Illuminate\Http\UploadedFile;
use Livewire\Component;
use Livewire\WithFileUploads;

class UserImport extends Component
{
    use WithFileUploads;

    public string $role;

    public ?UploadedFile $spreadsheet = null;

    public ?string $columnError = null;

    public ?int $createdCount = null;

    /** @var array<int, string> */
    public array $importErrors = [];

    public function mount(string $role): void
    {
        abort_unless(in_array($role, ['teacher', 'student'], true), 404);

        $this->role = $role;
    }

    public function targetRole(): RoleName
    {
        return $this->role === 'teacher' ? RoleName::Teacher : RoleName::Student;
    }

    protected function rules(): array
    {
        return [
            'spreadsheet' => ['required', 'file', 'mimes:xlsx,csv,txt'],
        ];
    }

    /**
     * Validate the structure and create users in one step, only when the
     * admin clicks Import — the spreadsheet isn't parsed just for selecting it.
     */
    public function import(UserImportRepositoryInterface $userImportRepository): void
    {
        abort_unless(auth()->user()->can('users.import'), 403);

        $this->columnError = null;
        $this->createdCount = null;
        $this->importErrors = [];

        $this->validate();

        if ($columnError = $userImportRepository->validateColumns($this->spreadsheet)) {
            $this->columnError = $columnError;

            return;
        }

        $parsed = $userImportRepository->parseRows($this->spreadsheet);

        $schoolId = app(CurrentSchool::class)->getSchoolId() ?? auth()->user()->school_id;
        $result = $userImportRepository->createUsers($parsed['rows'], $this->targetRole(), $schoolId);

        $this->createdCount = $result['created'];
        $this->importErrors = [...$parsed['errors'], ...$result['errors']];

        $this->reset(['spreadsheet']);
    }

    public function render()
    {
        $isAdminUser = auth()->user()->hasRole(RoleName::Admin);
        $title = $this->role === 'teacher' ? 'Import Teachers' : 'Import Students';

        return view('livewire.users.user-import', [
            'templateUrl' => asset("templates/users-import-template-{$this->role}.xlsx"),
        ])
            ->extends($isAdminUser ? 'layouts.admin' : 'layouts.app', ['topbarTitle' => $title])
            ->section($isAdminUser ? 'admin-content' : 'app-content');
    }
}
