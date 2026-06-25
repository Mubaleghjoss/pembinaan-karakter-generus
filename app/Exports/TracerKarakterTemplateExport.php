<?php

namespace App\Exports;

use App\Models\Siswa;
use App\Models\Karakter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TracerKarakterTemplateExport
{
    protected $withSampleData;

    public function __construct(bool $withSampleData = true)
    {
        $this->withSampleData = $withSampleData;
    }

    public function download(string $filename = 'template-tracer-karakter.xlsx'): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Karakter');

        // Headers
        $headers = ['nis', 'karakter', 'tanggal', 'catatan'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        // Header style
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '10B981'],
            ],
        ]);

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(30);

        // Sample data
        if ($this->withSampleData) {
            $siswaList = Siswa::where('is_active', true)->limit(3)->get();
            $karakterList = Karakter::where('is_active', true)->limit(3)->get();
            $today = now()->format('Y-m-d');
            
            $row = 2;
            if ($siswaList->count() > 0 && $karakterList->count() > 0) {
                foreach ($siswaList as $siswa) {
                    foreach ($karakterList as $karakter) {
                        $sheet->setCellValue('A' . $row, $siswa->nis);
                        $sheet->setCellValue('B' . $row, $karakter->nama);
                        $sheet->setCellValue('C' . $row, $today);
                        $sheet->setCellValue('D' . $row, '');
                        $row++;
                    }
                }
            } else {
                // Example data
                $examples = [
                    ['12345', 'Jujur', $today, 'Sangat baik'],
                    ['12345', 'Disiplin', $today, ''],
                    ['12346', 'Jujur', $today, 'Perlu bimbingan'],
                    ['12346', 'Tanggung Jawab', $today, ''],
                ];
                foreach ($examples as $example) {
                    $sheet->setCellValue('A' . $row, $example[0]);
                    $sheet->setCellValue('B' . $row, $example[1]);
                    $sheet->setCellValue('C' . $row, $example[2]);
                    $sheet->setCellValue('D' . $row, $example[3]);
                    $row++;
                }
            }
        }

        // Add karakter list in column F
        $karakterList = Karakter::where('is_active', true)->pluck('nama')->toArray();
        if (!empty($karakterList)) {
            $sheet->setCellValue('F1', 'Daftar Karakter:');
            $sheet->getStyle('F1')->getFont()->setBold(true);
            foreach ($karakterList as $index => $nama) {
                $sheet->setCellValue('F' . ($index + 2), $nama);
            }
            $sheet->getColumnDimension('F')->setWidth(25);
        }

        // Add info sheet
        $infoSheet = $spreadsheet->createSheet();
        $infoSheet->setTitle('Petunjuk');
        $infoSheet->setCellValue('A1', 'PETUNJUK PENGISIAN');
        $infoSheet->setCellValue('A3', 'Kolom:');
        $infoSheet->setCellValue('A4', 'nis - NIS siswa (wajib)');
        $infoSheet->setCellValue('A5', 'karakter - Nama karakter sesuai daftar (wajib)');
        $infoSheet->setCellValue('A6', 'tanggal - Format: YYYY-MM-DD atau DD/MM/YYYY (opsional, default hari ini)');
        $infoSheet->setCellValue('A7', 'catatan - Catatan tambahan (opsional)');
        $infoSheet->setCellValue('A9', 'Daftar Karakter yang tersedia dapat dilihat di kolom F sheet pertama');
        $infoSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $infoSheet->getColumnDimension('A')->setWidth(70);

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        
        return new StreamedResponse(function() use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
