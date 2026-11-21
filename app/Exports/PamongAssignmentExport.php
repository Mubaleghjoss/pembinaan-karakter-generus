<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PamongAssignmentExport
{
    public function __construct(protected Collection $assignments, protected string $pamongName) {}

    public function download(string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Binaan Pamong');
        $sheet->fromArray([
            ['Daftar Generus Binaan Pamong'],
            ['Pamong', $this->pamongName],
            [],
            ['Nama Generus', 'NIS', 'Wilayah', 'Jenjang/Kelas'],
        ], null, 'A1');

        $row = 5;
        foreach ($this->assignments as $assignment) {
            $student = $assignment->siswa;
            $sheet->fromArray([[$student->nama, null, $student->kelompok_label ?? $student->kelompok ?? '-', $student->school_grade_label ?? $student->kelas?->nama ?? '-']], null, "A{$row}");
            $sheet->setCellValueExplicit("B{$row}", (string) $student->nis, DataType::TYPE_STRING);
            $row++;
        }

        $lastRow = max(4, $row - 1);
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A4:D4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F766E']],
        ]);
        $sheet->getStyle("A4:D{$lastRow}")->getAlignment()->setWrapText(true);
        foreach (range('A', 'D') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $sheet->freezePane('A5');
        $sheet->setAutoFilter("A4:D{$lastRow}");

        $writer = new Xlsx($spreadsheet);
        return new StreamedResponse(fn () => $writer->save('php://output'), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
