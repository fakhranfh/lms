<?php

namespace App\Livewire\Teachers;

use App\Enums\RoleName;
use App\Services\UserImportService;
use App\Support\CurrentSchool;
use Illuminate\Http\UploadedFile;
use Livewire\Component;
use Livewire\WithFileUploads;

class TeacherImport extends Component
{
    use WithFileUploads;

    public ?UploadedFile $spreadsheet = null;

    public int $loginLinkTtlDays = 2;

    public ?string $columnError = null;

    public ?int $createdCount = null;

    /** @var array<int, string> */
    public array $importErrors = [];

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

    public function import(UserImportService $userImportService): mixed
    {
        abort_unless(auth()->user()->can('teachers.import'), 403);

        $this->columnError = null;
        $this->createdCount = null;
        $this->importErrors = [];

        $this->validate();

        if ($columnError = $userImportService->validateColumns($this->spreadsheet)) {
            $this->columnError = $columnError;

            return null;
        }

        $parsed = $userImportService->parseRows($this->spreadsheet);

        $schoolId = app(CurrentSchool::class)->getSchoolId() ?? auth()->user()->school_id;
        $result = $userImportService->createUsers(
            $parsed['rows'],
            RoleName::Teacher,
            $schoolId,
            generateLoginLinks: true,
            loginLinkTtlMinutes: $this->loginLinkTtlDays * 1440,
        );

        $createdCount = $result['created'];
        $importErrors = [...$parsed['errors'], ...$result['errors']];

        $this->reset(['spreadsheet']);

        if ($importErrors !== []) {
            $this->createdCount = $createdCount;
            $this->importErrors = $importErrors;

            return null;
        }

        session()->flash('success', trans_choice('1 teacher imported successfully.|:count teachers imported successfully.', $createdCount, ['count' => $createdCount]));

        return $this->redirect(route('teachers.index'), navigate: true);
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
