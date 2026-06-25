<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CalendarMonthExport
{
    public function __construct(
        protected Collection $rows,
        protected string $periodLabel
    ) {}

    public function download(string $filename = 'kalender-bulanan.xlsx'): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Kalender');

        $sheet->fromArray([
            ['Kalender Aktivitas PKG'],
            ['Periode', $this->periodLabel],
            [],
            $this->headings(),
        ], null, 'A1');

        $rowNumber = 5;
        foreach ($this->rows as $index => $row) {
            $sheet->fromArray([$this->map($row, $index + 1)], null, "A{$rowNumber}");
            $rowNumber++;
        }

        $lastRow = max(4, $rowNumber - 1);
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle('A4:H4')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0F766E'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle("A4:H{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->freezePane('A5');
        $sheet->setAutoFilter('A4:H4');

        $writer = new Xlsx($spreadsheet);

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    protected function headings(): array
    {
        return [
            'No',
            'Tanggal',
            'Waktu',
            'Jenis',
            'Judul',
            'Detail',
            'Target/Pengajar',
            'Lokasi/Link',
        ];
    }

    protected function map(array $row, int $number): array
    {
        return [
            $number,
            $row['date_label'] ?? '-',
            $row['time_label'] ?? '-',
            $row['type_label'] ?? '-',
            $row['title'] ?? '-',
            $row['detail'] ?? '-',
            $row['target_label'] ?? '-',
            $row['location_label'] ?? '-',
        ];
    }
}
