<?php

namespace App\Repositories\User;

use App\Enums\RoleName;
use App\Imports\UsersImport;
use App\Models\Role;
use App\Services\UserService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;

class UserImportRepository implements UserImportRepositoryInterface
{
    /** @var array<int, string> */
    private const EXPECTED_COLUMNS = ['name', 'email', 'photo_filename'];

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

        return "Spreadsheet columns must be exactly: {$expectedLabels}. Found: {$foundLabels}.";
    }

    public function import(UploadedFile $file, RoleName $role, string $schoolId, array $photosByFilename): UsersImport
    {
        $roleId = Role::where('school_id', $schoolId)->where('name', $role->value)->value('id');

        $import = new UsersImport($this->userService, $schoolId, $roleId, $photosByFilename);

        Excel::import($import, $file);

        return $import;
    }
}
