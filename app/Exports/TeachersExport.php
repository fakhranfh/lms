<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TeachersExport implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles
{
    /**
     * @param  Collection<int, array{name: string, email: string, login_url: string}>  $rows
     */
    public function __construct(
        protected Collection $rows,
    ) {}

    public function collection(): Collection
    {
        return $this->rows->map(fn (array $row) => [
            $row['name'],
            $row['email'],
            $row['login_url'],
        ]);
    }

    public function headings(): array
    {
        return ['Name', 'Email', 'Login Link'];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $this->rows->count() + 1;

        $sheet->getStyle("A1:C{$lastRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
