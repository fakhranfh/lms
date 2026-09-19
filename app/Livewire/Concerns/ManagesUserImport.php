<?php

namespace App\Livewire\Concerns;

use App\Enums\RoleName;
use App\Services\UserImportService;
use App\Support\CurrentSchool;
use Illuminate\Http\UploadedFile;

/**
 * Shared spreadsheet-import logic for user-management import pages that
 * only differ by role (e.g. students, teachers).
 */
trait ManagesUserImport
{
    public ?UploadedFile $spreadsheet = null;

    public int $loginLinkTtlDays = 2;

    public ?string $columnError = null;

    public ?int $createdCount = null;

    /** @var array<int, string> */
    public array $importErrors = [];

    /**
     * The role this import creates users as, e.g. RoleName::Student.
     */
    abstract protected function managedRole(): RoleName;

    /**
     * Permission/route name prefix for this role, e.g. "students".
     */
    abstract protected function permissionPrefix(): string;

    /**
     * Config key holding the login link TTL in minutes, e.g. "students".
     */
    abstract protected function configKey(): string;

    /**
     * Lowercase singular label used in messages, e.g. "student".
     */
    abstract protected function entityLabel(): string;

    public function mountManagesUserImport(): void
    {
        $this->loginLinkTtlDays = (int) ceil(config("{$this->configKey()}.login_link_ttl_minutes", 2880) / 1440);
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
        abort_unless(auth()->user()->can("{$this->permissionPrefix()}.import"), 403);

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
            $this->managedRole(),
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

        $label = $this->entityLabel();
        session()->flash('success', trans_choice(
            "1 {$label} imported successfully.|:count {$label}s imported successfully.",
            $createdCount,
            ['count' => $createdCount],
        ));

        return $this->redirect(route("{$this->permissionPrefix()}.index"), navigate: true);
    }
}
