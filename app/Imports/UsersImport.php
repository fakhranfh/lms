<?php

namespace App\Imports;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Row;

class UsersImport implements OnEachRow, SkipsOnError, SkipsOnFailure, WithHeadingRow, WithValidation
{
    use SkipsErrors, SkipsFailures;

    public int $createdCount = 0;

    /** @var array<int, string> */
    public array $rowErrors = [];

    /**
     * @param  array<string, UploadedFile>  $photosByFilename  filename => uploaded photo
     */
    public function __construct(
        private readonly UserService $userService,
        private readonly string $schoolId,
        private readonly ?int $roleId,
        private readonly array $photosByFilename = [],
    ) {}

    public function onRow(Row $row): void
    {
        $rowNumber = $row->getIndex() + 1;
        $data = $row->toArray();

        $email = trim((string) $data['email']);

        if (User::withoutGlobalScopes()->where('email', $email)->exists()) {
            $this->rowErrors[] = "Row {$rowNumber}: email \"{$email}\" is already in use.";

            return;
        }

        $photoFilename = trim((string) ($data['photo_filename'] ?? ''));
        $photo = $photoFilename !== '' ? ($this->photosByFilename[$photoFilename] ?? null) : null;

        $this->userService->createUser([
            'name' => trim((string) $data['name']),
            'email' => $email,
            'password' => Str::random(24),
            'school_id' => $this->schoolId,
        ], $photo, $this->roleId ? [$this->roleId] : []);

        $this->createdCount++;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ];
    }
}
