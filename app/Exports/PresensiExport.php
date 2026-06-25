<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PresensiExport
{
    public function __construct(
        protected Collection $records,
        protected array $filters = []
    ) {}

    public function download(string $filename = 'data-presensi.xlsx'): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Presensi');

        $sheet->fromArray([
            ['Data Presensi Siswa'],
            ['Tanggal', $this->filters['tanggal'] ?? '-'],
            ['Kelas', $this->filters['kelas'] ?? 'Semua Kelas'],
            ['Status', $this->filters['status'] ?? 'Semua Status'],
            ['Verifikasi', $this->filters['verified'] ?? 'Semua'],
            [],
            $this->headings(),
        ], null, 'A1');

        $row = 8;
        foreach ($this->records as $index => $presensi) {
            $sheet->fromArray([$this->map($presensi, $index + 1)], null, "A{$row}");
            $row++;
        }

        $lastRow = max(7, $row - 1);
        $sheet->mergeCells('A1:K1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle('A7:K7')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E40AF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle("A7:K{$lastRow}")->applyFromArray([
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

        foreach (range('A', 'K') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->freezePane('A8');
        $sheet->setAutoFilter('A7:K7');

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
            'NIS',
            'Nama Siswa',
            'Kelas',
            'Status',
            'Jam Masuk',
            'Jam Keluar',
            'Keterangan',
            'Verifikasi',
            'Diverifikasi Oleh',
        ];
    }

    protected function map($presensi, int $number): array
    {
        return [
            $number,
            $presensi->tanggal?->format('Y-m-d') ?? '-',
            $presensi->siswa?->nis ?? '-',
            $presensi->siswa?->nama ?? '-',
            $presensi->siswa?->kelas?->nama ?? '-',
            $this->statusLabel($presensi->status),
            $presensi->jam_masuk?->format('H:i') ?? '-',
            $presensi->jam_keluar?->format('H:i') ?? '-',
            $presensi->keterangan ?? '-',
            $presensi->is_verified ? 'Terverifikasi' : 'Belum',
            $presensi->verifier?->name ?? '-',
        ];
    }

    protected function statusLabel(?string $status): string
    {
        return match ($status) {
            'hadir' => 'Hadir',
            'terlambat' => 'Terlambat',
            'izin' => 'Izin',
            'sakit' => 'Sakit',
            'alpha', 'tidak_hadir' => 'Tidak Hadir',
            default => '-',
        };
    }
}
