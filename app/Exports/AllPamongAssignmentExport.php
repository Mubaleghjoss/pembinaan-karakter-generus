<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AllPamongAssignmentExport
{
    public function __construct(protected Collection $pamongs) {}

    public function download(string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Binaan Pamong');
        $sheet->fromArray([['Nama Pamong', 'Nama Generus', 'NIS', 'Wilayah', 'Jenjang/Kelas']], null, 'A1');

        $row = 2;
        foreach ($this->pamongs as $pamong) {
            if ($pamong->assignedStudents->isEmpty()) {
                $sheet->fromArray([[$pamong->display_name, '-', '-', '-', '-']], null, "A{$row}");
                $row++;
                continue;
            }

            foreach ($pamong->assignedStudents as $assignment) {
                $student = $assignment->siswa;
                $sheet->fromArray([[
                    $pamong->display_name,
                    $student->nama,
                    null,
                    $student->kelompok_label ?? $student->kelompok ?? '-',
                    $student->school_grade_label ?? $student->kelas?->nama ?? '-',
                ]], null, "A{$row}");
                $sheet->setCellValueExplicit("C{$row}", (string) $student->nis, DataType::TYPE_STRING);
                $row++;
            }
        }

        $lastRow = max(1, $row - 1);
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F766E']],
        ]);
        $sheet->getStyle("A1:E{$lastRow}")->getAlignment()->setWrapText(true);
        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:E{$lastRow}");

        $writer = new Xlsx($spreadsheet);
        return new StreamedResponse(fn () => $writer->save('php://output'), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
