<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\Presensi;
use App\Models\TracerKarakter;
use App\Models\Karakter;
use App\Models\Kelas;
use App\Models\PointPeriod;
use App\Models\PointTransaction;
use App\Models\SiswaPoint;
use App\Models\SiswaKarakterChecklist;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('pamong.permission:export,view')->only(['index']);
        $this->middleware('pamong.permission:export,presensi')->only(['presensi', 'rekapPresensi', 'periodCollection']);
        $this->middleware('pamong.permission:export,leaderboard')->only(['leaderboard']);
        $this->middleware('pamong.permission:export,siswa')->only(['siswa']);
    }

    protected function getPeriodTaskWindowStart(PointPeriod $period): ?Carbon
    {
        $lastReset = PointTransaction::query()
            ->where('metadata->event', 'period_reset')
            ->where('metadata->archived_period_id', $period->id)
            ->latest('id')
            ->first(['created_at']);

        return $lastReset?->created_at;
    }

    protected function getPeriodResetTransaction(PointPeriod $period): ?PointTransaction
    {
        return PointTransaction::query()
            ->where('metadata->event', 'period_reset')
            ->where('metadata->archived_period_id', $period->id)
            ->latest('id')
            ->first();
    }

    protected function getArchivedPendingTaskCountForReset(PointPeriod $period, ?PointTransaction $resetTransaction): int
    {
        if (! $resetTransaction?->created_at) {
            return 0;
        }

        $batchMarker = '[sync_at=' . $resetTransaction->created_at->format('Y-m-d H:i:s') . ']';

        return SiswaKarakterChecklist::onlyTrashed()
            ->where('deleted_reason', 'like', 'Reset otomatis saat sinkron periode ' . $period->name . '%')
            ->where('deleted_reason', 'like', '%' . $batchMarker . '%')
            ->count();
    }

    protected function getArchivedPendingTaskSummary(PointPeriod $period): array
    {
        $archivedBaseQuery = SiswaKarakterChecklist::onlyTrashed()
            ->whereNull('verified_at')
            ->where('deleted_reason', 'like', 'Reset otomatis saat sinkron periode ' . $period->name . '%')
            ->when($period->start_date, fn ($query) => $query->whereDate('checked_at', '>=', $period->start_date))
            ->when($period->end_date, fn ($query) => $query->whereDate('checked_at', '<=', $period->end_date));

        $summary = (clone $archivedBaseQuery)
            ->selectRaw('COUNT(*) as pending_task_count')
            ->selectRaw('COUNT(DISTINCT siswa_id) as pending_task_siswa_count')
            ->first();

        return [
            'pending_task_count' => (int) ($summary->pending_task_count ?? 0),
            'pending_task_siswa_count' => (int) ($summary->pending_task_siswa_count ?? 0),
        ];
    }

    protected function styleWorksheetHeader(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
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
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
        ]);
    }

    protected function styleWorksheetSectionTitle(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $range, string $fillColor = 'DBEAFE', string $textColor = '1E3A8A'): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => $textColor],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $fillColor],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
        ]);
    }

    protected function styleHighlightRow(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $range, string $fillColor, string $textColor = '111827'): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => $textColor],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $fillColor],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
        ]);
    }

    protected function styleWorksheetTable(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'E2E8F0'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    protected function autosizeWorksheetColumns(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $fromColumn, string $toColumn): void
    {
        foreach (range($fromColumn, $toColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    protected function configurePrintableSheet(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        string $printArea,
        string $headerTitle,
        string $orientation = PageSetup::ORIENTATION_PORTRAIT
    ): void {
        $sheet->getPageSetup()
            ->setOrientation($orientation)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setPrintArea($printArea);

        $sheet->getPageMargins()
            ->setTop(0.4)
            ->setRight(0.3)
            ->setLeft(0.3)
            ->setBottom(0.4)
            ->setHeader(0.2)
            ->setFooter(0.2);

        $sheet->setShowGridlines(false);
        $sheet->getHeaderFooter()
            ->setOddHeader('&C&"Arial,Bold"' . $headerTitle . "\n" . '&"Arial,Regular"Dokumen Internal PKG')
            ->setOddFooter('&L' . config('app.name') . '&CHalaman &P dari &N&R&D &T');
    }

    protected function makeLeaderboardSpreadsheet($leaderboard, ?PointPeriod $period): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator(config('app.name'))
            ->setTitle($period ? 'Laporan Periode Poin dan Verifikasi Tugas PKG' : 'Laporan Total Poin Siswa')
            ->setSubject('Ekspor leaderboard gamifikasi');

        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Ringkasan');

        $summaryRow = 1;
        if ($period) {
            $resetTransaction = $this->getPeriodResetTransaction($period);
            $archivedPendingTaskCount = $this->getArchivedPendingTaskCountForReset($period, $resetTransaction);
            $archivedPendingSummary = $this->getArchivedPendingTaskSummary($period);
            $periodTaskTotal = $leaderboard->sum('period_task_total');
            $periodTaskVerified = $leaderboard->sum('period_task_verified');
            $periodTaskPending = $leaderboard->sum('period_task_pending');
            $pendingTaskStudents = $leaderboard->filter(fn ($row) => ($row->period_task_pending ?? 0) > 0)->count();
            $documentNumber = 'LAP-' . strtoupper($period->slug ?: ('PERIODE-' . $period->id)) . '-' . now()->format('YmdHis');

            $summarySheet->setCellValue('A1', config('app.name'));
            $summarySheet->mergeCells('A1:B1');
            $summarySheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $summarySheet->setCellValue('A2', 'Laporan Periode Poin dan Verifikasi Tugas PKG');
            $summarySheet->mergeCells('A2:B2');
            $summarySheet->getStyle('A2')->getFont()->setBold(true)->setSize(13);
            $summarySheet->setCellValue('A4', 'Identitas Periode');
            $summarySheet->mergeCells('A4:B4');
            $this->styleWorksheetSectionTitle($summarySheet, 'A4:B4');
            $summarySheet->fromArray([
                ['Periode', $period->name],
                ['Rentang', trim(($period->start_date?->format('Y-m-d') ?? '-') . ' s/d ' . ($period->end_date?->format('Y-m-d') ?? 'berjalan'))],
                ['Dibuat pada', now()->format('Y-m-d H:i')],
                ['Nomor dokumen', $documentNumber],
            ], null, 'A5');

            $summarySheet->setCellValue('A9', 'Status Sinkron');
            $summarySheet->mergeCells('A9:B9');
            $this->styleWorksheetSectionTitle($summarySheet, 'A9:B9', 'DCFCE7', '166534');
            $summarySheet->fromArray([
                ['Periode sebelum reset', $resetTransaction ? data_get($resetTransaction->metadata, 'archived_period_name', $period->name) : '-'],
                ['Periode berjalan setelah reset', $resetTransaction ? $period->name . ' (mulai ' . optional($resetTransaction->created_at)->format('Y-m-d H:i') . ')' : $period->name],
                ['Sinkron terakhir', optional($resetTransaction?->created_at)->format('Y-m-d H:i') ?: '-'],
                ['Jumlah tugas pending yang diarsipkan', $archivedPendingTaskCount],
                ['Jumlah tugas pending historis periode', (int) ($archivedPendingSummary['pending_task_count'] ?? 0)],
            ], null, 'A10');

            $summarySheet->setCellValue('A15', 'Rekap Tugas');
            $summarySheet->mergeCells('A15:B15');
            $this->styleWorksheetSectionTitle($summarySheet, 'A15:B15', 'FEF3C7', '92400E');
            $summarySheet->fromArray([
                ['Total Tugas Periode', $periodTaskTotal],
                ['Tugas Diverifikasi', $periodTaskVerified],
                ['Tugas Menunggu Pamong Historis', $periodTaskPending],
                ['Siswa dengan Tugas Menunggu', $pendingTaskStudents],
            ], null, 'A16');

            $summarySheet->setCellValue('A21', 'Catatan');
            $summarySheet->mergeCells('A21:B21');
            $this->styleWorksheetSectionTitle($summarySheet, 'A21:B21', 'F3E8FF', '6B21A8');
            $summarySheet->setCellValue('A22', 'Laporan ini menggabungkan ringkasan periode, data siswa, rekap tugas, peringkat, dan transaksi poin dalam satu file Excel.');
            $summarySheet->mergeCells('A22:B22');
            $summarySheet->getStyle('A22')->getAlignment()->setWrapText(true);

            $summaryRow = 22;
        } else {
            $documentNumber = 'LAP-TOTAL-' . now()->format('YmdHis');
            $summarySheet->setCellValue('A1', config('app.name'));
            $summarySheet->mergeCells('A1:B1');
            $summarySheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $summarySheet->setCellValue('A2', 'Laporan Total Poin Siswa');
            $summarySheet->mergeCells('A2:B2');
            $summarySheet->getStyle('A2')->getFont()->setBold(true)->setSize(13);
            $summarySheet->setCellValue('A4', 'Ringkasan Total');
            $summarySheet->mergeCells('A4:B4');
            $this->styleWorksheetSectionTitle($summarySheet, 'A4:B4');
            $summarySheet->fromArray([
                ['Tanggal ekspor', now()->format('Y-m-d H:i')],
                ['Jumlah siswa', $leaderboard->count()],
                ['Total poin', $leaderboard->sum('total_points')],
                ['Nomor dokumen', $documentNumber],
            ], null, 'A5');
            $summaryRow = 8;
        }

        $summarySheet->getStyle("A1:B{$summaryRow}")->getAlignment()->setWrapText(true);
        $this->styleWorksheetTable($summarySheet, "A1:B{$summaryRow}");
        $this->autosizeWorksheetColumns($summarySheet, 'A', 'B');
        $this->configurePrintableSheet($summarySheet, 'A1:B' . $summaryRow, 'Sheet Ringkasan');

        $dataSheet = $spreadsheet->createSheet();
        $dataSheet->setTitle('Data Siswa');

        $headers = ['Rank', 'NIS', 'Nama', 'Kelas', 'Level', 'Total Poin', 'Poin Kehadiran', 'Poin Karakter', 'Poin Bonus', 'Streak Hadir', 'Streak Karakter'];
        if ($period) {
            $headers = array_merge($headers, ['Total Tugas Periode', 'Tugas Diverifikasi', 'Tugas Menunggu Pamong']);
        }

        $dataSheet->fromArray([$headers], null, 'A1');
        $this->styleWorksheetHeader($dataSheet, 'A1:' . $dataSheet->getHighestColumn() . '1');

        $row = 2;
        foreach ($leaderboard as $index => $sp) {
            $dataSheet->setCellValue("A{$row}", $index + 1);
            $dataSheet->setCellValueExplicit("B{$row}", $sp->siswa->nis ?? '-', DataType::TYPE_STRING);
            $dataSheet->setCellValue("C{$row}", $sp->siswa->nama ?? '-');
            $dataSheet->setCellValue("D{$row}", $sp->siswa->kelas->nama ?? '-');
            $dataSheet->setCellValue("E{$row}", $sp->currentLevel->nama ?? 'Level ' . $sp->level);
            $dataSheet->setCellValue("F{$row}", $sp->total_points);
            $dataSheet->setCellValue("G{$row}", $sp->attendance_points);
            $dataSheet->setCellValue("H{$row}", $sp->character_points);
            $dataSheet->setCellValue("I{$row}", $sp->bonus_points);
            $dataSheet->setCellValue("J{$row}", $sp->attendance_streak);
            $dataSheet->setCellValue("K{$row}", $sp->character_streak);

            if ($period) {
                $dataSheet->setCellValue("L{$row}", $sp->period_task_total ?? 0);
                $dataSheet->setCellValue("M{$row}", $sp->period_task_verified ?? 0);
                $dataSheet->setCellValue("N{$row}", $sp->period_task_pending ?? 0);
            }

            $row++;
        }

        if ($row > 2) {
            $this->styleWorksheetTable($dataSheet, 'A1:' . $dataSheet->getHighestColumn() . ($row - 1));
            $dataSheet->setAutoFilter('A1:' . $dataSheet->getHighestColumn() . ($row - 1));
        }
        $this->autosizeWorksheetColumns($dataSheet, 'A', $period ? 'N' : 'K');
        $dataSheet->freezePane('A2');
        $dataSheet->setCellValue('A' . $row, 'Total Siswa');
        $dataSheet->setCellValue('B' . $row, $leaderboard->count());
        $dataSheet->setCellValue('E' . $row, 'Total Poin');
        $dataSheet->setCellValue('F' . $row, $leaderboard->sum('total_points'));
        if ($period) {
            $dataSheet->setCellValue('L' . $row, 'Total Tugas');
            $dataSheet->setCellValue('M' . $row, $leaderboard->sum('period_task_total'));
        }
        $this->styleWorksheetSectionTitle($dataSheet, 'A' . $row . ':' . ($period ? 'N' : 'K') . $row, 'E0F2FE', '0C4A6E');

        if ($period) {
            $taskSheet = $spreadsheet->createSheet();
            $taskSheet->setTitle('Rekap Tugas');
            $taskSheet->fromArray([['Rank', 'NIS', 'Nama', 'Kelas', 'Total Tugas', 'Diverifikasi', 'Menunggu Pamong']], null, 'A1');
            $this->styleWorksheetHeader($taskSheet, 'A1:G1');

            $taskRow = 2;
            foreach ($leaderboard as $index => $sp) {
                $taskSheet->setCellValue("A{$taskRow}", $index + 1);
                $taskSheet->setCellValueExplicit("B{$taskRow}", $sp->siswa->nis ?? '-', DataType::TYPE_STRING);
                $taskSheet->setCellValue("C{$taskRow}", $sp->siswa->nama ?? '-');
                $taskSheet->setCellValue("D{$taskRow}", $sp->siswa->kelas->nama ?? '-');
                $taskSheet->setCellValue("E{$taskRow}", $sp->period_task_total ?? 0);
                $taskSheet->setCellValue("F{$taskRow}", $sp->period_task_verified ?? 0);
                $taskSheet->setCellValue("G{$taskRow}", $sp->period_task_pending ?? 0);
                $taskRow++;
            }

            if ($taskRow > 2) {
                $this->styleWorksheetTable($taskSheet, 'A1:G' . ($taskRow - 1));
                $taskSheet->setAutoFilter('A1:G' . ($taskRow - 1));
            }
            $this->autosizeWorksheetColumns($taskSheet, 'A', 'G');
            $taskSheet->freezePane('A2');
            $taskSheet->setCellValue('A' . $taskRow, 'Total');
            $taskSheet->setCellValue('E' . $taskRow, $leaderboard->sum('period_task_total'));
            $taskSheet->setCellValue('F' . $taskRow, $leaderboard->sum('period_task_verified'));
            $taskSheet->setCellValue('G' . $taskRow, $leaderboard->sum('period_task_pending'));
            $this->styleWorksheetSectionTitle($taskSheet, 'A' . $taskRow . ':G' . $taskRow, 'FEF3C7', '92400E');
            $this->configurePrintableSheet($taskSheet, 'A1:G' . $taskRow, 'Sheet Rekap Tugas');
        }

        $rankingSheet = $spreadsheet->createSheet();
        $rankingSheet->setTitle('Peringkat');
        $rankingSheet->setCellValue('A1', config('app.name'));
        $rankingSheet->mergeCells('A1:H1');
        $rankingSheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $rankingSheet->setCellValue('A2', $period ? 'Peringkat Siswa Periode' : 'Peringkat Total Poin Siswa');
        $rankingSheet->mergeCells('A2:H2');
        $rankingSheet->getStyle('A2')->getFont()->setBold(true)->setSize(13);
        $rankingSheet->setCellValue('A3', $period ? ($period->name . ' | ' . trim(($period->start_date?->format('Y-m-d') ?? '-') . ' s/d ' . ($period->end_date?->format('Y-m-d') ?? 'berjalan'))) : ('Tanggal ekspor: ' . now()->format('Y-m-d H:i')));
        $rankingSheet->mergeCells('A3:H3');
        $rankingSheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $rankingSheet->setCellValue('A4', 'Dokumen Internal PKG');
        $rankingSheet->mergeCells('A4:H4');
        $rankingSheet->getStyle('A4')->getFont()->setItalic(true)->getColor()->setRGB('6B7280');
        $rankingSheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $rankingSheet->fromArray([['Peringkat', 'NIS', 'Nama', 'Kelas', 'Level', 'Total Poin', 'Streak Hadir', 'Streak Karakter']], null, 'A6');
        $this->styleWorksheetHeader($rankingSheet, 'A6:H6');

        $rankingRow = 7;
        foreach ($leaderboard as $index => $sp) {
            $rankingSheet->setCellValue("A{$rankingRow}", $index + 1);
            $rankingSheet->setCellValueExplicit("B{$rankingRow}", $sp->siswa->nis ?? '-', DataType::TYPE_STRING);
            $rankingSheet->setCellValue("C{$rankingRow}", $sp->siswa->nama ?? '-');
            $rankingSheet->setCellValue("D{$rankingRow}", $sp->siswa->kelas->nama ?? '-');
            $rankingSheet->setCellValue("E{$rankingRow}", $sp->currentLevel->nama ?? 'Level ' . $sp->level);
            $rankingSheet->setCellValue("F{$rankingRow}", $sp->total_points);
            $rankingSheet->setCellValue("G{$rankingRow}", $sp->attendance_streak);
            $rankingSheet->setCellValue("H{$rankingRow}", $sp->character_streak);
            if ($index === 0) {
                $this->styleHighlightRow($rankingSheet, "A{$rankingRow}:H{$rankingRow}", 'FEF08A');
            } elseif ($index === 1) {
                $this->styleHighlightRow($rankingSheet, "A{$rankingRow}:H{$rankingRow}", 'E5E7EB');
            } elseif ($index === 2) {
                $this->styleHighlightRow($rankingSheet, "A{$rankingRow}:H{$rankingRow}", 'FED7AA');
            }
            $rankingRow++;
        }

        if ($rankingRow > 7) {
            $this->styleWorksheetTable($rankingSheet, 'A6:H' . ($rankingRow - 1));
            $rankingSheet->setAutoFilter('A6:H' . ($rankingRow - 1));
        }
        $rankingSheet->setCellValue('A' . $rankingRow, 'Jumlah Siswa');
        $rankingSheet->setCellValue('B' . $rankingRow, $leaderboard->count());
        $rankingSheet->setCellValue('E' . $rankingRow, 'Total Poin');
        $rankingSheet->setCellValue('F' . $rankingRow, $leaderboard->sum('total_points'));
        $this->styleWorksheetSectionTitle($rankingSheet, 'A' . $rankingRow . ':H' . $rankingRow, 'EDE9FE', '5B21B6');
        $this->autosizeWorksheetColumns($rankingSheet, 'A', 'H');
        $rankingSheet->freezePane('A7');
        $this->configurePrintableSheet($rankingSheet, 'A1:H' . $rankingRow, 'Sheet Peringkat Siswa', PageSetup::ORIENTATION_LANDSCAPE);

        $transactionSheet = $spreadsheet->createSheet();
        $transactionSheet->setTitle('Transaksi Poin');
        $transactionSheet->fromArray([[
            'Tanggal',
            'Waktu',
            'NIS',
            'Nama',
            'Kelas',
            'Tipe',
            'Sumber',
            'Poin',
            'Deskripsi',
            'Periode',
            'Referensi',
            'Event Metadata',
        ]], null, 'A1');
        $this->styleWorksheetHeader($transactionSheet, 'A1:L1');

        $leaderboardSiswaIds = collect($leaderboard)
            ->map(fn ($row) => $row->siswa->id ?? null)
            ->filter()
            ->unique()
            ->values();

        $transactionQuery = PointTransaction::query()
            ->with(['siswa.kelas'])
            ->whereIn('siswa_id', $leaderboardSiswaIds)
            ->when($period, function ($query) use ($period) {
                $query->where(function ($inner) use ($period) {
                    $inner->where('metadata->point_period_id', $period->id)
                        ->orWhere('metadata->period_id', $period->id);
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $transactionRow = 2;
        foreach ($transactionQuery->cursor() as $transaction) {
            $metadata = is_array($transaction->metadata) ? $transaction->metadata : [];
            $transactionSheet->setCellValue("A{$transactionRow}", optional($transaction->created_at)->format('Y-m-d') ?: '-');
            $transactionSheet->setCellValue("B{$transactionRow}", optional($transaction->created_at)->format('H:i:s') ?: '-');
            $transactionSheet->setCellValueExplicit("C{$transactionRow}", $transaction->siswa->nis ?? '-', DataType::TYPE_STRING);
            $transactionSheet->setCellValue("D{$transactionRow}", $transaction->siswa->nama ?? '-');
            $transactionSheet->setCellValue("E{$transactionRow}", $transaction->siswa->kelas->nama ?? '-');
            $transactionSheet->setCellValue("F{$transactionRow}", ucfirst((string) $transaction->type));
            $transactionSheet->setCellValue("G{$transactionRow}", $transaction->source_label ?? $transaction->source ?? '-');
            $transactionSheet->setCellValue("H{$transactionRow}", (int) $transaction->points);
            $transactionSheet->setCellValue("I{$transactionRow}", $transaction->description ?? '-');
            $transactionSheet->setCellValue("J{$transactionRow}", data_get($metadata, 'period_name', $period?->name ?? '-'));
            $transactionSheet->setCellValue("K{$transactionRow}", trim(($transaction->reference_type ?? '-') . ' #' . ($transaction->reference_id ?? '-')));
            $transactionSheet->setCellValue("L{$transactionRow}", data_get($metadata, 'event', '-'));
            $transactionRow++;
        }

        if ($transactionRow > 2) {
            $this->styleWorksheetTable($transactionSheet, 'A1:L' . ($transactionRow - 1));
            $transactionSheet->setAutoFilter('A1:L' . ($transactionRow - 1));
        }
        $this->autosizeWorksheetColumns($transactionSheet, 'A', 'L');
        $transactionSheet->freezePane('A2');
        $transactionSheet->setCellValue('A' . $transactionRow, 'Total Transaksi');
        $transactionSheet->setCellValue('B' . $transactionRow, max(0, $transactionRow - 2));
        $transactionSheet->setCellValue('G' . $transactionRow, 'Total Poin');
        $transactionSheet->setCellValue('H' . $transactionRow, (int) $transactionQuery->sum('points'));
        $this->styleWorksheetSectionTitle($transactionSheet, 'A' . $transactionRow . ':L' . $transactionRow, 'FCE7F3', '9D174D');
        $this->configurePrintableSheet($transactionSheet, 'A1:L' . $transactionRow, 'Sheet Transaksi Poin', PageSetup::ORIENTATION_LANDSCAPE);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * Export presensi to CSV
     */
    public function presensi(Request $request)
    {
        $user = Auth::user();
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $kelasId = $request->get('kelas_id');

        // Get siswa IDs based on role
        if ($user->isTeacher()) {
            $siswaIds = $user->getAssignedSiswaIds();
        } else {
            $siswaIds = Siswa::active()->pluck('id');
        }

        $filename = 'presensi-' . $startDate . '-to-' . $endDate . '.csv';

        $callback = function() use ($siswaIds, $startDate, $endDate, $kelasId) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel

            // Header
            fputcsv($file, ['No', 'Tanggal', 'NIS', 'Nama', 'Kelas', 'Status', 'Jam Masuk', 'Jam Keluar', 'Keterangan']);

            $no = 1;
            $query = Presensi::query()
                ->select([
                    'presensi.id',
                    'presensi.tanggal',
                    'presensi.status',
                    'presensi.jam_masuk',
                    'presensi.jam_keluar',
                    'presensi.keterangan',
                    'siswa.nis',
                    'siswa.nama',
                    'kelas.nama as kelas_nama',
                ])
                ->join('siswa', 'siswa.id', '=', 'presensi.siswa_id')
                ->leftJoin('kelas', 'kelas.id', '=', 'siswa.kelas_id')
                ->whereIn('presensi.siswa_id', $siswaIds)
                ->whereBetween('presensi.tanggal', [$startDate, $endDate])
                ->when($kelasId, fn ($inner, $filterKelasId) => $inner->where('siswa.kelas_id', $filterKelasId))
                ->orderBy('presensi.tanggal')
                ->orderBy('presensi.siswa_id')
                ->orderBy('presensi.id');

            foreach ($query->cursor() as $p) {
                fputcsv($file, [
                    $no++,
                    Carbon::parse($p->tanggal)->format('Y-m-d'),
                    $p->nis ?? '-',
                    $p->nama ?? '-',
                    $p->kelas_nama ?? '-',
                    ucfirst($p->status),
                    $p->jam_masuk ? Carbon::parse($p->jam_masuk)->format('H:i') : '-',
                    $p->jam_keluar ? Carbon::parse($p->jam_keluar)->format('H:i') : '-',
                    $p->keterangan ?? '-'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export rekap presensi per siswa
     */
    public function rekapPresensi(Request $request)
    {
        $user = Auth::user();
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $kelasId = $request->get('kelas_id');

        // Get siswa
        if ($user->isTeacher()) {
            $siswaQuery = Siswa::whereIn('id', $user->getAssignedSiswaIds());
        } else {
            $siswaQuery = Siswa::active();
        }

        if ($kelasId) {
            $siswaQuery->where('kelas_id', $kelasId);
        }

        $siswaList = $siswaQuery
            ->select(['id', 'nis', 'nama', 'kelas_id'])
            ->with('kelas:id,nama')
            ->orderBy('nama')
            ->get();

        $rekapPerSiswa = Presensi::whereIn('siswa_id', $siswaList->pluck('id'))
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->selectRaw('siswa_id')
            ->selectRaw("SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir")
            ->selectRaw("SUM(CASE WHEN status = 'terlambat' THEN 1 ELSE 0 END) as terlambat")
            ->selectRaw("SUM(CASE WHEN status = 'izin' THEN 1 ELSE 0 END) as izin")
            ->selectRaw("SUM(CASE WHEN status = 'sakit' THEN 1 ELSE 0 END) as sakit")
            ->selectRaw("SUM(CASE WHEN status = 'alpha' THEN 1 ELSE 0 END) as alpha")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('siswa_id')
            ->get()
            ->keyBy('siswa_id');

        $filename = 'rekap-presensi-' . $startDate . '-to-' . $endDate . '.csv';

        $callback = function() use ($siswaList, $rekapPerSiswa) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, ['No', 'NIS', 'Nama', 'Kelas', 'Hadir', 'Terlambat', 'Izin', 'Sakit', 'Alpha', 'Total Hari', 'Persentase']);

            $no = 1;
            foreach ($siswaList as $siswa) {
                $rekap = $rekapPerSiswa->get($siswa->id);
                $hadir = (int) ($rekap->hadir ?? 0);
                $terlambat = (int) ($rekap->terlambat ?? 0);
                $izin = (int) ($rekap->izin ?? 0);
                $sakit = (int) ($rekap->sakit ?? 0);
                $alpha = (int) ($rekap->alpha ?? 0);
                $total = (int) ($rekap->total ?? 0);
                $percentage = $total > 0 ? round((($hadir + $terlambat) / $total) * 100, 1) : 0;

                fputcsv($file, [
                    $no++,
                    $siswa->nis,
                    $siswa->nama,
                    $siswa->kelas->nama ?? '-',
                    $hadir,
                    $terlambat,
                    $izin,
                    $sakit,
                    $alpha,
                    $total,
                    $percentage . '%'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export gamification leaderboard
     */
    public function leaderboard(Request $request)
    {
        $user = Auth::user();
        $periodId = $request->integer('period_id') ?: null;
        $period = $periodId ? PointPeriod::findOrFail($periodId) : null;

        if ($user->isTeacher()) {
            $siswaIds = $user->getAssignedSiswaIds();
        } else {
            $siswaIds = Siswa::active()->pluck('id');
        }

        if ($period) {
            $taskWindowStart = $this->getPeriodTaskWindowStart($period);
            $archivedPendingSummary = $this->getArchivedPendingTaskSummary($period);

            $aggregates = PointTransaction::query()
                ->select('siswa_id')
                ->selectRaw('SUM(points) as total_points')
                ->selectRaw("SUM(CASE WHEN source = 'attendance' THEN points ELSE 0 END) as attendance_points")
                ->selectRaw("SUM(CASE WHEN source = 'character' THEN points ELSE 0 END) as character_points")
                ->selectRaw("SUM(CASE WHEN source NOT IN ('attendance', 'character') THEN points ELSE 0 END) as bonus_points")
                ->whereIn('siswa_id', $siswaIds)
                ->where(function ($query) use ($period) {
                    $query->where('metadata->point_period_id', $period->id)
                        ->orWhere('metadata->period_id', $period->id);
                })
                ->groupBy('siswa_id')
                ->orderByDesc('total_points')
                ->get();

            $taskAggregates = SiswaKarakterChecklist::query()
                ->select('siswa_id')
                ->selectRaw('COUNT(*) as total_tasks')
                ->selectRaw("SUM(CASE WHEN verified_at IS NOT NULL THEN 1 ELSE 0 END) as verified_tasks")
                ->selectRaw("SUM(CASE WHEN verified_at IS NULL THEN 1 ELSE 0 END) as pending_tasks")
                ->whereIn('siswa_id', $siswaIds)
                ->when($period->start_date, fn ($query) => $query->whereDate('checked_at', '>=', $period->start_date))
                ->when($period->end_date, fn ($query) => $query->whereDate('checked_at', '<=', $period->end_date))
                ->when($taskWindowStart, fn ($query) => $query->where('checked_at', '>', $taskWindowStart))
                ->groupBy('siswa_id')
                ->get()
                ->keyBy('siswa_id');

            $archivedTaskAggregates = SiswaKarakterChecklist::onlyTrashed()
                ->select('siswa_id')
                ->selectRaw('COUNT(*) as archived_pending_tasks')
                ->whereNull('verified_at')
                ->where('deleted_reason', 'like', 'Reset otomatis saat sinkron periode ' . $period->name . '%')
                ->when($period->start_date, fn ($query) => $query->whereDate('checked_at', '>=', $period->start_date))
                ->when($period->end_date, fn ($query) => $query->whereDate('checked_at', '<=', $period->end_date))
                ->groupBy('siswa_id')
                ->get()
                ->keyBy('siswa_id');

            $periodSiswaIds = $aggregates->pluck('siswa_id')
                ->merge($taskAggregates->keys())
                ->merge($archivedTaskAggregates->keys())
                ->unique()
                ->values();

            $siswaMap = Siswa::query()
                ->whereIn('id', $periodSiswaIds)
                ->with(['kelas', 'siswaPoint.currentLevel'])
                ->get()
                ->keyBy('id');

            $leaderboard = $periodSiswaIds
                ->map(function ($siswaId) use ($siswaMap, $taskAggregates, $archivedTaskAggregates, $aggregates) {
                    $pointAggregate = $aggregates->firstWhere('siswa_id', $siswaId);
                    $siswa = $siswaMap->get($siswaId);
                    if (!$siswa) {
                        return null;
                    }

                    $taskAggregate = $taskAggregates->get($siswaId);
                    $archivedTaskAggregate = $archivedTaskAggregates->get($siswaId);
                    $siswaPoint = $siswa->siswaPoint;
                    $archivedPendingTasks = (int) ($archivedTaskAggregate->archived_pending_tasks ?? 0);

                    return (object) [
                        'siswa' => $siswa,
                        'currentLevel' => $siswaPoint?->currentLevel,
                        'level' => $siswaPoint?->level ?? 1,
                        'total_points' => (int) ($pointAggregate->total_points ?? 0),
                        'attendance_points' => (int) ($pointAggregate->attendance_points ?? 0),
                        'character_points' => (int) ($pointAggregate->character_points ?? 0),
                        'bonus_points' => (int) ($pointAggregate->bonus_points ?? 0),
                        'attendance_streak' => (int) ($siswaPoint?->attendance_streak ?? 0),
                        'character_streak' => (int) ($siswaPoint?->character_streak ?? 0),
                        'period_task_total' => (int) ($taskAggregate->total_tasks ?? 0) + $archivedPendingTasks,
                        'period_task_verified' => (int) ($taskAggregate->verified_tasks ?? 0),
                        'period_task_pending' => (int) ($taskAggregate->pending_tasks ?? 0) + $archivedPendingTasks,
                        'period_task_archived_pending' => $archivedPendingTasks,
                    ];
                })
                ->filter()
                ->sortByDesc(fn ($row) => $row->total_points)
                ->values();
        } else {
            $leaderboard = SiswaPoint::whereIn('siswa_id', $siswaIds)
                ->with(['siswa.kelas', 'currentLevel'])
                ->orderBy('total_points', 'desc')
                ->get();
        }

        $filename = $period
            ? 'laporan-periode-poin-' . ($period->slug ?: 'periode') . '-' . now()->format('Y-m-d') . '.xlsx'
            : 'laporan-total-poin-' . now()->format('Y-m-d') . '.xlsx';

        $spreadsheet = $this->makeLeaderboardSpreadsheet($leaderboard, $period);
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function periodCollection()
    {
        $periods = PointPeriod::query()->orderByDesc('start_date')->orderByDesc('id')->get();
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator(config('app.name'))
            ->setTitle('Kumpulan Data Periode')
            ->setSubject('Rekap seluruh periode poin dan tugas PKG');

        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Ringkasan');
        $summarySheet->setCellValue('A1', config('app.name'));
        $summarySheet->mergeCells('A1:B1');
        $summarySheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $summarySheet->setCellValue('A2', 'Kumpulan Data Periode');
        $summarySheet->mergeCells('A2:B2');
        $summarySheet->getStyle('A2')->getFont()->setBold(true)->setSize(13);
        $summarySheet->fromArray([
            ['Tanggal ekspor', now()->format('Y-m-d H:i')],
            ['Jumlah periode', $periods->count()],
            ['Total poin seluruh periode', 0],
            ['Total tugas menunggu historis', 0],
        ], null, 'A4');

        $periodSheet = $spreadsheet->createSheet();
        $periodSheet->setTitle('Semua Periode');
        $periodSheet->fromArray([[
            'Nama Periode',
            'Status',
            'Mulai',
            'Selesai',
            'Total Poin',
            'Jumlah Siswa',
            'Total Tugas Historis',
            'Sudah Diverifikasi',
            'Menunggu Pamong Historis',
            'Diarsipkan Saat Sinkron',
            'Siswa Menunggu Historis',
            'Sinkron Terakhir',
        ]], null, 'A1');
        $this->styleWorksheetHeader($periodSheet, 'A1:L1');

        $totalPoints = 0;
        $totalHistoricalPendingTasks = 0;
        $periodRow = 2;

        foreach ($periods as $period) {
            $taskWindowStart = $this->getPeriodTaskWindowStart($period);
            $archivedPendingSummary = $this->getArchivedPendingTaskSummary($period);
            $resetTransaction = $this->getPeriodResetTransaction($period);

            $pointSummary = PointTransaction::query()
                ->selectRaw('COUNT(DISTINCT siswa_id) as siswa_count')
                ->selectRaw('COALESCE(SUM(points), 0) as total_points')
                ->where(function ($query) use ($period) {
                    $query->where('metadata->point_period_id', $period->id)
                        ->orWhere('metadata->period_id', $period->id);
                })
                ->first();

            $taskBaseQuery = SiswaKarakterChecklist::query()
                ->when($period->start_date, fn ($query) => $query->whereDate('checked_at', '>=', $period->start_date))
                ->when($period->end_date, fn ($query) => $query->whereDate('checked_at', '<=', $period->end_date))
                ->when($taskWindowStart, fn ($query) => $query->where('checked_at', '>', $taskWindowStart));

            $liveTaskSummary = (clone $taskBaseQuery)
                ->selectRaw('COUNT(*) as task_count')
                ->selectRaw("SUM(CASE WHEN verified_at IS NOT NULL THEN 1 ELSE 0 END) as verified_task_count")
                ->selectRaw("SUM(CASE WHEN verified_at IS NULL THEN 1 ELSE 0 END) as pending_task_count")
                ->first();

            $historicalPendingTaskCount = (int) ($liveTaskSummary->pending_task_count ?? 0) + (int) ($archivedPendingSummary['pending_task_count'] ?? 0);
            $historicalTaskCount = (int) ($liveTaskSummary->task_count ?? 0) + (int) ($archivedPendingSummary['pending_task_count'] ?? 0);
            $livePendingStudentIds = (clone $taskBaseQuery)->whereNull('verified_at')->distinct()->pluck('siswa_id');
            $archivedPendingStudentIds = SiswaKarakterChecklist::onlyTrashed()
                ->whereNull('verified_at')
                ->where('deleted_reason', 'like', 'Reset otomatis saat sinkron periode ' . $period->name . '%')
                ->when($period->start_date, fn ($query) => $query->whereDate('checked_at', '>=', $period->start_date))
                ->when($period->end_date, fn ($query) => $query->whereDate('checked_at', '<=', $period->end_date))
                ->distinct()
                ->pluck('siswa_id');

            $historicalPendingStudents = $livePendingStudentIds->merge($archivedPendingStudentIds)->unique()->count();
            $totalPoints += (int) ($pointSummary->total_points ?? 0);
            $totalHistoricalPendingTasks += $historicalPendingTaskCount;

            $periodSheet->fromArray([[
                $period->name,
                strtoupper($period->status),
                $period->start_date?->format('Y-m-d') ?? '-',
                $period->end_date?->format('Y-m-d') ?? 'Berjalan',
                (int) ($pointSummary->total_points ?? 0),
                (int) ($pointSummary->siswa_count ?? 0),
                $historicalTaskCount,
                (int) ($liveTaskSummary->verified_task_count ?? 0),
                $historicalPendingTaskCount,
                (int) ($archivedPendingSummary['pending_task_count'] ?? 0),
                $historicalPendingStudents,
                optional($resetTransaction?->created_at)->format('Y-m-d H:i') ?: '-',
            ]], null, 'A' . $periodRow);
            $periodRow++;
        }

        $summarySheet->setCellValue('B6', $totalPoints);
        $summarySheet->setCellValue('B7', $totalHistoricalPendingTasks);
        $summarySheet->setCellValue('A6', 'Total poin seluruh periode');
        $summarySheet->setCellValue('A7', 'Total tugas menunggu historis');
        $this->styleWorksheetTable($summarySheet, 'A1:B7');
        $this->autosizeWorksheetColumns($summarySheet, 'A', 'B');
        $this->configurePrintableSheet($summarySheet, 'A1:B7', 'Sheet Ringkasan Kumpulan Periode');

        if ($periodRow > 2) {
            $this->styleWorksheetTable($periodSheet, 'A1:L' . ($periodRow - 1));
            $periodSheet->setAutoFilter('A1:L' . ($periodRow - 1));
        }
        $periodSheet->setCellValue('A' . $periodRow, 'Total');
        $periodSheet->setCellValue('E' . $periodRow, $totalPoints);
        $periodSheet->setCellValue('I' . $periodRow, $totalHistoricalPendingTasks);
        $this->styleWorksheetSectionTitle($periodSheet, 'A' . $periodRow . ':L' . $periodRow, 'E0F2FE', '0C4A6E');
        $this->autosizeWorksheetColumns($periodSheet, 'A', 'L');
        $periodSheet->freezePane('A2');
        $this->configurePrintableSheet($periodSheet, 'A1:L' . $periodRow, 'Sheet Semua Periode', PageSetup::ORIENTATION_LANDSCAPE);

        $spreadsheet->setActiveSheetIndex(0);
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'kumpulan-data-periode-' . now()->format('Y-m-d') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Export siswa data
     */
    public function siswa(Request $request)
    {
        $user = Auth::user();
        $kelasId = $request->get('kelas_id');
        $assignedSiswaIds = $user->isTeacher() ? $user->getAssignedSiswaIds() : null;

        $filename = 'data-siswa-' . now()->format('Y-m-d') . '.csv';

        $callback = function() use ($kelasId, $assignedSiswaIds) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'No',
                'NIS',
                'Nama',
                'Kelas',
                'Kelompok',
                'Jenis Kelamin',
                'Tanggal Lahir',
                'Alamat',
                'No HP Siswa',
                'Nama Wali',
                'No HP Wali',
                'Email Wali',
                'Status',
            ]);

            $no = 1;
            $query = DB::table('siswa')
                ->leftJoin('kelas', 'kelas.id', '=', 'siswa.kelas_id')
                ->select([
                    'siswa.id',
                    'siswa.nis',
                    'siswa.nama',
                    'kelas.nama as kelas_nama',
                    'siswa.kelompok',
                    'siswa.jenis_kelamin',
                    'siswa.tanggal_lahir',
                    'siswa.alamat',
                    'siswa.phone',
                    'siswa.nama_wali',
                    'siswa.phone_wali',
                    'siswa.email_wali',
                    'siswa.status',
                ])
                ->where('siswa.is_active', true)
                ->when($kelasId, fn ($inner, $filterKelasId) => $inner->where('siswa.kelas_id', $filterKelasId))
                ->when($assignedSiswaIds !== null, fn ($inner) => $inner->whereIn('siswa.id', $assignedSiswaIds))
                ->orderBy('siswa.nama');

            foreach ($query->cursor() as $siswa) {
                fputcsv($file, [
                    $no++,
                    $siswa->nis,
                    $siswa->nama,
                    $siswa->kelas_nama ?? '-',
                    $siswa->kelompok ?? '-',
                    $siswa->jenis_kelamin ?? '-',
                    $siswa->tanggal_lahir ? Carbon::parse($siswa->tanggal_lahir)->format('Y-m-d') : '-',
                    $siswa->alamat ?? '-',
                    $siswa->phone ?? '-',
                    $siswa->nama_wali ?? '-',
                    $siswa->phone_wali ?? '-',
                    $siswa->email_wali ?? '-',
                    $siswa->status ?? '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export page with options
     */
    public function index()
    {
        $user = Auth::user();
        $kelas = Kelas::query()->orderBy('nama')->get(['id', 'nama']);
        $periods = PointPeriod::query()->orderByDesc('start_date')->orderByDesc('id')->get(['id', 'name', 'status', 'start_date', 'end_date']);
        $activeSiswaQuery = Siswa::active();

        if ($user->isTeacher()) {
            $activeSiswaQuery->whereIn('id', $user->getAssignedSiswaIds());
        }

        $summary = [
            'active_siswa_count' => (clone $activeSiswaQuery)->count(),
            'kelas_count' => $kelas->count(),
            'period_count' => $periods->count(),
            'presensi_this_month_count' => Presensi::query()
                ->whereBetween('tanggal', [now()->startOfMonth()->toDateString(), now()->toDateString()])
                ->when($user->isTeacher(), fn ($query) => $query->whereIn('siswa_id', $user->getAssignedSiswaIds()))
                ->count(),
        ];

        return view('export.index', compact('kelas', 'periods', 'summary'));
    }
}
