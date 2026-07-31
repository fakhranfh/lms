<?php

namespace App\Repositories\User;

use App\Enums\RoleName;
use App\Imports\UsersImport;
use Illuminate\Http\UploadedFile;

interface UserImportRepositoryInterface
{
    /**
     * Validate that the uploaded spreadsheet has exactly the expected columns.
     * Returns an error message describing the mismatch, or null if valid.
     */
    public function validateColumns(UploadedFile $file): ?string;

    /**
     * Run the import for a fixed target role, returning the completed import
     * with its created count and row-level errors.
     *
     * @param  array<string, UploadedFile>  $photosByFilename  filename => uploaded photo
     */
    public function import(UploadedFile $file, RoleName $role, string $schoolId, array $photosByFilename): UsersImport;
}
