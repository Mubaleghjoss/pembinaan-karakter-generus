<?php

namespace App\Exports;

use App\Models\PamongPresensi;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PamongPresensiExport
{
    public function __construct(
        protected string $startDate,
        protected string $endDate,
        protected ?int $userId = null
    ) {}

    public function download(string $filename = 'presensi-pamong.xlsx'): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($this->title());
        $sheet->fromArray([$this->headings()], null, 'A1');

        $row = 2;
        foreach ($this->collection() as $index => $presensi) {
            $sheet->fromArray([$this->map($presensi, $index + 1)], null, "A{$row}");
            $row++;
        }

        $lastRow = max(1, $row - 1);
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle("A1:K{$lastRow}")->applyFromArray([
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

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:K1');

        $writer = new Xlsx($spreadsheet);

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function collection(): Collection
    {
        $query = PamongPresensi::with(['user', 'verifier'])
            ->whereBetween('tanggal', [$this->startDate, $this->endDate]);

        if ($this->userId) {
            $query->where('user_id', $this->userId);
        }

        return $query->orderBy('tanggal', 'desc')
            ->orderBy('jam_masuk', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Pamong',
            'Username',
            'Tanggal',
            'Jam Masuk',
            'Jam Keluar',
            'Status',
            'Keterangan',
            'Keterlambatan',
            'Diverifikasi',
            'Diverifikasi Oleh',
        ];
    }

    public function map(PamongPresensi $presensi, int $number): array
    {
        return [
            $number,
            $presensi->user->name ?? '-',
            $presensi->user->username ?? '-',
            $presensi->tanggal?->format('d/m/Y') ?? '-',
            $presensi->jam_masuk?->format('H:i') ?? '-',
            $presensi->jam_keluar?->format('H:i') ?? '-',
            ucfirst((string) $presensi->status),
            $presensi->keterangan ?? '-',
            $presensi->late_duration_formatted ?? '-',
            $presensi->is_verified ? 'Ya' : 'Tidak',
            $presensi->verifier?->name ?? '-',
        ];
    }

    public function title(): string
    {
        return 'Presensi Pamong';
    }
}
