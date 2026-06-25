<?php

namespace App\Exports;

use App\Models\User;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PamongPresensiTemplateExport
{
    public function __construct(
        protected bool $withSampleData = true,
        protected ?string $tanggal = null
    ) {
        $this->tanggal = $this->tanggal ?? now()->format('Y-m-d');
    }

    public function download(string $filename = 'template-presensi-pamong.xlsx'): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Pamong');

        $headers = ['username', 'email', 'nama_pamong', 'tanggal', 'status', 'jam_masuk', 'jam_keluar', 'keterangan'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0F766E'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ]);

        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setWidth(20);
        }

        if ($this->withSampleData) {
            $users = User::query()
                ->select('username', 'email', 'name')
                ->whereHas('role', fn ($q) => $q->whereIn('name', User::attendanceRoleNames()))
                ->orderBy('name')
                ->limit(50)
                ->get();

            $row = 2;
            foreach ($users as $user) {
                $sheet->setCellValue('A' . $row, $user->username);
                $sheet->setCellValue('B' . $row, $user->email);
                $sheet->setCellValue('C' . $row, $user->name);
                $sheet->setCellValue('D' . $row, $this->tanggal);
                $sheet->setCellValue('E' . $row, 'hadir');
                $sheet->setCellValue('F' . $row, '07:00');
                $sheet->setCellValue('G' . $row, '14:00');
                $sheet->setCellValue('H' . $row, '');
                $row++;
            }
        }

        $infoSheet = $spreadsheet->createSheet();
        $infoSheet->setTitle('Petunjuk');
        $infoSheet->setCellValue('A1', 'PETUNJUK PENGISIAN');
        $infoSheet->setCellValue('A3', 'Kolom:');
        $infoSheet->setCellValue('A4', 'username - Username pamong/admin (utamakan kolom ini)');
        $infoSheet->setCellValue('A5', 'email - Email pamong/admin (opsional jika username diisi)');
        $infoSheet->setCellValue('A6', 'nama_pamong - Hanya referensi, tidak diproses');
        $infoSheet->setCellValue('A7', 'tanggal - Format: YYYY-MM-DD');
        $infoSheet->setCellValue('A8', 'status - hadir, terlambat, izin, sakit, alpha');
        $infoSheet->setCellValue('A9', 'jam_masuk - Format: HH:MM');
        $infoSheet->setCellValue('A10', 'jam_keluar - Format: HH:MM');
        $infoSheet->setCellValue('A11', 'keterangan - Catatan tambahan');
        $infoSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $infoSheet->getColumnDimension('A')->setWidth(75);

        $spreadsheet->setActiveSheetIndex(0);
        $writer = new Xlsx($spreadsheet);

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
