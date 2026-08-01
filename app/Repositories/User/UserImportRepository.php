<?php

namespace App\Repositories\User;

use App\Enums\RoleName;
use App\Models\Role;
use App\Services\UserService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;

class UserImportRepository implements UserImportRepositoryInterface
{
    /** @var array<int, string> */
    private const EXPECTED_COLUMNS = ['name', 'email'];

    public function __construct(
        private readonly UserService $userService,
    ) {}

    public function validateColumns(UploadedFile $file): ?string
    {
        $rows = Excel::toArray(new class implements WithHeadingRow {}, $file);
        $headers = array_keys($rows[0][0] ?? []);

        $missing = array_diff(self::EXPECTED_COLUMNS, $headers);
        $extra = array_diff($headers, self::EXPECTED_COLUMNS);

        if ($missing === [] && $extra === []) {
            return null;
        }

        $expectedLabels = implode(', ', array_map(fn (string $c) => Str::headline($c), self::EXPECTED_COLUMNS));
        $foundLabels = $headers === [] ? '(none)' : implode(', ', array_map(fn (string $c) => Str::headline($c), $headers));

        return "Spreadsheet columns must match the template exactly: {$expectedLabels}. Found: {$foundLabels}.";
    }

    public function parseRows(UploadedFile $file): array
    {
        $sheets = Excel::toArray(new class implements WithHeadingRow {}, $file);
        $rawRows = $sheets[0] ?? [];

        $rows = [];
        $errors = [];

        foreach ($rawRows as $index => $data) {
            $rowNumber = $index + 2; // +1 for 0-index, +1 for the header row
            $name = trim((string) ($data['name'] ?? ''));
            $email = trim((string) ($data['email'] ?? ''));

            if ($name === '' && $email === '') {
                continue;
            }

            if ($name === '' || $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Row {$rowNumber}: a name and a valid email are required.";

                continue;
            }

            $rows[] = ['row' => $rowNumber, 'name' => $name, 'email' => $email];
        }

        return ['rows' => $rows, 'errors' => $errors];
    }

    public function createUsers(array $rows, RoleName $role, string $schoolId): array
    {
        $roleId = Role::where('school_id', $schoolId)->where('name', $role->value)->value('id');

        $created = 0;
        $errors = [];

        foreach ($rows as $row) {
            if ($conflict = $this->userService->emailConflictMessage($row['email'], $schoolId)) {
                $errors[] = "Row {$row['row']}: \"{$row['email']}\" — {$conflict}";

                continue;
            }

            try {
                $this->userService->createUserWithPhotoUrl([
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'password' => Str::random(24),
                    'school_id' => $schoolId,
                ], $row['photoUrl'] ?? null, $roleId ? [$roleId] : []);

                $created++;
            } catch (UniqueConstraintViolationException) {
                // Pre-check raced with another request creating the same
                // email between the check above and this insert.
                $errors[] = "Row {$row['row']}: \"{$row['email']}\" — This email is already in use.";
            }
        }

        return ['created' => $created, 'errors' => $errors];
    }
}
