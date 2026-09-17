<?php

namespace App\Livewire\Students;

use App\Enums\RoleName;
use App\Services\UserImportService;
use App\Support\CurrentSchool;
use Illuminate\Http\UploadedFile;
use Livewire\Component;
use Livewire\WithFileUploads;

class StudentImport extends Component
{
    use WithFileUploads;

    public ?UploadedFile $spreadsheet = null;

    public int $loginLinkTtlDays = 2;

    public ?string $columnError = null;

    public ?int $createdCount = null;

    /** @var array<int, string> */
    public array $importErrors = [];

    /** @var array<int, array{name: string, email: string, loginUrl: string}> */
    public array $createdStudents = [];

    public function mount(): void
    {
        $this->loginLinkTtlDays = (int) ceil(config('students.login_link_ttl_minutes', 2880) / 1440);
    }

    protected function rules(): array
    {
        return [
            'spreadsheet' => ['required', 'file', 'mimes:xlsx,csv,txt'],
            'loginLinkTtlDays' => ['required', 'integer', 'min:1', 'max:365'],
        ];
    }

    public function import(UserImportService $userImportService): void
    {
        abort_unless(auth()->user()->can('students.import'), 403);

        $this->columnError = null;
        $this->createdCount = null;
        $this->importErrors = [];
        $this->createdStudents = [];

        $this->validate();

        if ($columnError = $userImportService->validateColumns($this->spreadsheet)) {
            $this->columnError = $columnError;

            return;
        }

        $parsed = $userImportService->parseRows($this->spreadsheet);

        $schoolId = app(CurrentSchool::class)->getSchoolId() ?? auth()->user()->school_id;
        $result = $userImportService->createUsers(
            $parsed['rows'],
            RoleName::Student,
            $schoolId,
            generateLoginLinks: true,
            loginLinkTtlMinutes: $this->loginLinkTtlDays * 1440,
        );

        $this->createdCount = $result['created'];
        $this->importErrors = [...$parsed['errors'], ...$result['errors']];

        $createdLoginUrls = $result['createdLoginUrls'];
        foreach ($result['createdUsers'] as $student) {
            $this->createdStudents[] = [
                'name' => $student->name,
                'email' => $student->email,
                'loginUrl' => $createdLoginUrls[$student->id] ?? '',
            ];
        }

        $this->reset(['spreadsheet']);
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
