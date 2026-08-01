<?php

namespace App\Repositories\User;

use App\Enums\RoleName;
use Illuminate\Http\UploadedFile;

interface UserImportRepositoryInterface
{
    /**
     * Validate that the uploaded spreadsheet has exactly the expected columns
     * (matching the downloadable template). Returns an error message
     * describing the mismatch, or null if valid.
     */
    public function validateColumns(UploadedFile $file): ?string;

    /**
     * Parse the spreadsheet into name/email rows, skipping blank rows and
     * collecting an error for any row missing a name or valid email.
     *
     * @return array{rows: array<int, array{row:int,name:string,email:string}>, errors: array<int,string>}
     */
    public function parseRows(UploadedFile $file): array;

    /**
     * Create a user for each row, skipping (and reporting) any row whose
     * email is already in use. Rows may include a 'photoUrl' pointing at an
     * already-uploaded (permanent) photo.
     *
     * @param  array<int, array{row:int,name:string,email:string,photoUrl?:string|null}>  $rows
     * @return array{created:int, errors: array<int,string>}
     */
    public function createUsers(array $rows, RoleName $role, string $schoolId): array;
}
