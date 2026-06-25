<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MateriRppJournalExport
{
    public function __construct(
        protected Collection $schedules,
        protected array $filters
    ) {}

    public function download(string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Jurnal RPP');

        $sheet->fromArray([
            ['Jurnal RPP Materi'],
            ['Periode', $this->filters['period'] ?? '-'],
            ['Materi', $this->filters['materi'] ?? 'Semua Materi'],
            ['Status Jurnal', $this->filters['workflow_status'] ?? 'Semua Status'],
            [],
            $this->headings(),
        ], null, 'A1');

        $rowNumber = 7;
        foreach ($this->schedules as $index => $schedule) {
            $sheet->fromArray([$this->map($schedule, $index + 1)], null, "A{$rowNumber}");
            $rowNumber++;
        }

        $lastRow = max(6, $rowNumber - 1);
        $lastColumn = 'S';

        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle("A6:{$lastColumn}6")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0F766E'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle("A6:{$lastColumn}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_TOP,
                'wrapText' => true,
            ],
        ]);

        foreach (range('A', $lastColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        foreach (['M', 'N', 'O', 'P'] as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(false);
            $sheet->getColumnDimension($column)->setWidth(32);
        }

        $sheet->freezePane('A7');
        $sheet->setAutoFilter("A6:{$lastColumn}6");

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
            'Materi',
            'Pertemuan',
            'Target Halaman',
            'Pengajar',
            'Petugas Jurnal',
            'Jenis Petugas',
            'Status Jurnal',
            'Status Realisasi',
            'Realisasi Halaman',
            'Catatan',
            'Kendala',
            'Tindak Lanjut',
            'Catatan Peninjau',
            'Pengisi',
            'Dikirim Pada',
            'Ditinjau Oleh',
        ];
    }

    protected function map($schedule, int $number): array
    {
        $journal = $schedule->rppJournal;
        $workflowLabel = $journal?->workflow_label ?? 'Belum Diisi';
        $assigneeTypes = $schedule->relationLoaded('journalAssignees') && $schedule->journalAssignees->isNotEmpty()
            ? $schedule->journalAssignees->pluck('type_label')->unique()->implode(', ')
            : match ($schedule->journal_assignee_type) {
                'user' => 'Admin/Pamong',
                'siswa' => 'Siswa',
                default => 'Belum Ditugaskan',
            };
        $submitter = $journal?->submittedBySiswa?->nama
            ?? $journal?->creator?->display_name
            ?? '-';

        return [
            $number,
            $schedule->start_date?->format('Y-m-d') ?? '-',
            $this->timeRange($schedule),
            $schedule->sourceMateri?->judul ?? $schedule->title,
            data_get($schedule->source_payload, 'number', '-'),
            data_get($schedule->source_payload, 'page_range', '-'),
            data_get($schedule->source_payload, 'teacher_name', '-'),
            $schedule->journal_assignee_label,
            $assigneeTypes,
            $workflowLabel,
            $journal?->status_label ?? '-',
            $journal?->actual_page_range ?? '-',
            $journal?->notes ?? '-',
            $journal?->obstacles ?? '-',
            $journal?->follow_up ?? '-',
            $journal?->review_note ?? '-',
            $submitter,
            $journal?->submitted_at?->format('Y-m-d H:i') ?? '-',
            $journal?->reviewer?->display_name ?? '-',
        ];
    }

    protected function timeRange($schedule): string
    {
        $start = $schedule->getAttributes()['start_time'] ?? null;
        $end = $schedule->getAttributes()['end_time'] ?? null;

        if (! $start) {
            return 'Sepanjang hari';
        }

        return substr($start, 0, 5) . ($end ? ' - ' . substr($end, 0, 5) : '');
    }
}
