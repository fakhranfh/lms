<?php

namespace App\Livewire\Users;

use App\Enums\RoleName;
use App\Repositories\User\UserImportRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\WithFileUploads;

class UserImport extends Component
{
    use WithFileUploads;

    public string $role;

    public ?UploadedFile $spreadsheet = null;

    /** @var array<int, UploadedFile> */
    public array $photos = [];

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
            'photos' => ['array'],
            'photos.*' => ['image', 'max:5120'],
        ];
    }

    public function import(UserImportRepositoryInterface $userImportRepository): void
    {
        abort_unless(auth()->user()->can('users.import'), 403);

        $this->createdCount = null;
        $this->importErrors = [];

        Validator::make(
            ['spreadsheet' => $this->spreadsheet, 'photos' => $this->photos],
            $this->rules()
        )->validate();

        if ($columnError = $userImportRepository->validateColumns($this->spreadsheet)) {
            $this->importErrors = [$columnError];

            return;
        }

        $photosByFilename = collect($this->photos)
            ->mapWithKeys(fn (UploadedFile $photo) => [$photo->getClientOriginalName() => $photo])
            ->all();

        $import = $userImportRepository->import(
            $this->spreadsheet,
            $this->targetRole(),
            auth()->user()->school_id,
            $photosByFilename,
        );

        $this->createdCount = $import->createdCount;

        $this->importErrors = [
            ...$import->rowErrors,
            ...collect($import->failures())->map(
                fn ($failure) => "Row {$failure->row()}: ".implode(' ', $failure->errors())
            )->all(),
            ...collect($import->errors())->map(fn ($error) => $error->getMessage())->all(),
        ];

        $this->spreadsheet = null;
        $this->photos = [];
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
