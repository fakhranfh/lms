<?php

namespace App\Livewire\Users;

use App\Enums\RoleName;
use App\Imports\UsersImport;
use App\Services\UserService;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class UserImport extends Component
{
    use WithFileUploads;

    public $spreadsheet = null;

    /** @var array<int, mixed> */
    public array $photos = [];

    public ?int $createdCount = null;

    /** @var array<int, string> */
    public array $importErrors = [];

    protected function rules(): array
    {
        return [
            'spreadsheet' => ['required', 'file', 'mimes:xlsx,csv,txt'],
            'photos' => ['array'],
            'photos.*' => ['image', 'max:5120'],
        ];
    }

    public function import(UserService $userService): void
    {
        abort_unless(auth()->user()->can('users.import'), 403);

        $this->createdCount = null;
        $this->importErrors = [];

        Validator::make(
            ['spreadsheet' => $this->spreadsheet, 'photos' => $this->photos],
            $this->rules()
        )->validate();

        $photosByFilename = collect($this->photos)
            ->mapWithKeys(fn ($photo) => [$photo->getClientOriginalName() => $photo])
            ->all();

        $import = new UsersImport($userService, auth()->user()->school_id, $photosByFilename);

        Excel::import($import, $this->spreadsheet);

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

        return view('livewire.users.user-import')
            ->extends($isAdminUser ? 'layouts.admin' : 'layouts.app', ['topbarTitle' => 'Import Users'])
            ->section($isAdminUser ? 'admin-content' : 'app-content');
    }
}
