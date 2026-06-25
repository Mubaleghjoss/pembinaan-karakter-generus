<?php

namespace App\Imports;

use App\Models\PamongPresensi;
use App\Models\PointPeriod;
use App\Models\PointTransaction;
use App\Models\Presensi;
use App\Models\Siswa;
use App\Models\User;
use App\Services\GamificationService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PresensiImport
{
    protected ?PointPeriod $period = null;

    protected array $results = [
        'success' => 0,
        'failed' => 0,
        'created' => 0,
        'updated' => 0,
        'siswa_created' => 0,
        'siswa_updated' => 0,
        'pamong_created' => 0,
        'pamong_updated' => 0,
        'points_awarded' => 0,
        'date_min' => null,
        'date_max' => null,
        'errors' => [],
    ];

    public function __construct(
        protected array $options = []
    ) {
        if (! empty($this->options['period_id'])) {
            $this->period = PointPeriod::find($this->options['period_id']);
        }
    }

    public function import(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getPathname());

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $rows = $sheet->toArray(null, true, true, true);

            if (empty($rows)) {
                continue;
            }

            $headers = $this->normalizeHeaders(array_shift($rows));
            if (empty(array_filter($headers))) {
                continue;
            }

            if (! $this->sheetLooksImportable($headers)) {
                continue;
            }

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;

                if (empty(array_filter($row, fn ($value) => $value !== null && $value !== ''))) {
                    continue;
                }

                $data = $this->combineRow($headers, $row);
                $type = $this->detectType($data, $sheet->getTitle());

                try {
                    if ($type === 'pamong') {
                        $this->importPamongRow($data, $rowNumber, $sheet->getTitle());
                    } else {
                        $this->importSiswaRow($data, $rowNumber, $sheet->getTitle());
                    }
                } catch (\Exception $e) {
                    $this->results['failed']++;
                    $this->results['errors'][] = "Sheet {$sheet->getTitle()} baris {$rowNumber}: " . $e->getMessage();
                }
            }
        }

        return $this->results;
    }

    protected function importSiswaRow(array $data, int $rowNumber, string $sheetName): void
    {
        $nis = trim((string) ($data['nis'] ?? ''));
        if ($nis === '') {
            $this->fail($sheetName, $rowNumber, 'NIS tidak boleh kosong.');
            return;
        }

        $siswa = Siswa::where('nis', $nis)->first();
        if (! $siswa) {
            $this->fail($sheetName, $rowNumber, "siswa dengan NIS {$nis} tidak ditemukan.");
            return;
        }

        $tanggal = $this->parseDate($data['tanggal'] ?? null);
        if (! $tanggal) {
            $this->fail($sheetName, $rowNumber, 'format tanggal tidak valid.');
            return;
        }

        $status = $this->normalizeStatus($data['status'] ?? 'alpha');
        $existing = Presensi::where('siswa_id', $siswa->id)
            ->whereDate('tanggal', $tanggal)
            ->first();

        $payload = [
            'status' => $status,
            'jam_masuk' => $this->parseTime($data['jam_masuk'] ?? null),
            'jam_keluar' => $this->parseTime($data['jam_keluar'] ?? null),
            'keterangan' => $data['keterangan'] ?? null,
            'is_verified' => $this->markVerified(),
            'verified_by' => $this->markVerified() ? ($this->options['imported_by'] ?? auth()->id()) : null,
            'verified_at' => $this->markVerified() ? now() : null,
            'metadata' => $this->buildMetadata($existing?->metadata ?? [], 'siswa'),
        ];

        if ($existing) {
            $existing->update($payload);
            $presensi = $existing->fresh();
            $this->markUpdated('siswa');
        } else {
            $presensi = Presensi::create([
                'siswa_id' => $siswa->id,
                'tanggal' => $tanggal,
            ] + $payload);
            $this->markCreated('siswa');
        }

        $this->trackImportedDate($tanggal);

        if ($this->shouldAwardPoints($presensi, $status)) {
            $transaction = app(GamificationService::class)->awardAttendancePointsForPeriod(
                $siswa,
                $status,
                $presensi,
                $this->period,
                false
            );

            if ($transaction) {
                $this->results['points_awarded'] += $transaction->points;
            }
        }

        $this->results['success']++;
    }

    protected function importPamongRow(array $data, int $rowNumber, string $sheetName): void
    {
        $user = $this->resolvePamong($data);
        if (! $user) {
            $this->fail($sheetName, $rowNumber, 'pamong tidak ditemukan dari username/email.');
            return;
        }

        $tanggal = $this->parseDate($data['tanggal'] ?? null);
        if (! $tanggal) {
            $this->fail($sheetName, $rowNumber, 'format tanggal tidak valid.');
            return;
        }

        $status = $this->normalizeStatus($data['status'] ?? 'alpha');
        $existing = PamongPresensi::where('user_id', $user->id)
            ->whereDate('tanggal', $tanggal)
            ->first();

        $payload = [
            'status' => $status,
            'jam_masuk' => $this->parseTime($data['jam_masuk'] ?? null),
            'jam_keluar' => $this->parseTime($data['jam_keluar'] ?? null),
            'keterangan' => $data['keterangan'] ?? null,
            'is_verified' => $this->markVerified(),
            'verified_by' => $this->markVerified() ? ($this->options['imported_by'] ?? auth()->id()) : null,
            'verified_at' => $this->markVerified() ? now() : null,
            'metadata' => $this->buildMetadata($existing?->metadata ?? [], 'pamong'),
        ];

        if ($existing) {
            $existing->update($payload);
            $this->markUpdated('pamong');
        } else {
            PamongPresensi::create([
                'user_id' => $user->id,
                'tanggal' => $tanggal,
            ] + $payload);
            $this->markCreated('pamong');
        }

        $this->trackImportedDate($tanggal);

        $this->results['success']++;
    }

    protected function normalizeHeaders(array $headers): array
    {
        return array_map(function ($header) {
            return strtolower(trim((string) $header));
        }, array_values($headers));
    }

    protected function combineRow(array $headers, array $row): array
    {
        $values = array_values($row);
        $data = [];

        foreach ($headers as $index => $header) {
            if ($header === '') {
                continue;
            }

            $data[$header] = $values[$index] ?? null;
        }

        return $data;
    }

    protected function sheetLooksImportable(array $headers): bool
    {
        $headers = array_filter($headers);

        if (! in_array('tanggal', $headers, true)) {
            return false;
        }

        if (in_array('nis', $headers, true)) {
            return true;
        }

        if (in_array('username', $headers, true) || in_array('email', $headers, true)) {
            return true;
        }

        return in_array('tipe', $headers, true) && in_array('status', $headers, true);
    }

    protected function detectType(array $data, string $sheetName): string
    {
        $explicitType = strtolower(trim((string) ($data['tipe'] ?? $data['jenis'] ?? '')));
        if (in_array($explicitType, ['pamong', 'pengurus', 'user'], true)) {
            return 'pamong';
        }

        if (in_array($explicitType, ['siswa', 'santri'], true)) {
            return 'siswa';
        }

        $sheet = strtolower($sheetName);
        if (str_contains($sheet, 'pamong') || str_contains($sheet, 'pengurus')) {
            return 'pamong';
        }

        if (! empty($data['username']) || ! empty($data['email'])) {
            return 'pamong';
        }

        return 'siswa';
    }

    protected function resolvePamong(array $data): ?User
    {
        $username = trim((string) ($data['username'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));

        $query = User::query()
            ->whereHas('role', fn ($q) => $q->whereIn('name', User::attendanceRoleNames()));

        if ($username !== '') {
            $query->where('username', $username);
        } elseif ($email !== '') {
            $query->where('email', $email);
        } else {
            return null;
        }

        return $query->first();
    }

    protected function fail(string $sheetName, int $rowNumber, string $message): void
    {
        $this->results['failed']++;
        $this->results['errors'][] = "Sheet {$sheetName} baris {$rowNumber}: {$message}";
    }

    protected function markCreated(string $type): void
    {
        $this->results['created']++;
        $this->results[$type . '_created']++;
    }

    protected function markUpdated(string $type): void
    {
        $this->results['updated']++;
        $this->results[$type . '_updated']++;
    }

    protected function trackImportedDate(Carbon $tanggal): void
    {
        $date = $tanggal->toDateString();

        if ($this->results['date_min'] === null || $date < $this->results['date_min']) {
            $this->results['date_min'] = $date;
        }

        if ($this->results['date_max'] === null || $date > $this->results['date_max']) {
            $this->results['date_max'] = $date;
        }
    }

    protected function markVerified(): bool
    {
        return (bool) ($this->options['mark_verified'] ?? true);
    }

    protected function shouldAwardPoints(Presensi $presensi, string $status): bool
    {
        if (! ($this->options['award_points'] ?? false)) {
            return false;
        }

        if ($status === 'alpha') {
            return false;
        }

        return ! PointTransaction::query()
            ->where('reference_type', Presensi::class)
            ->where('reference_id', $presensi->id)
            ->where('source', 'attendance')
            ->exists();
    }

    protected function buildMetadata(array $existingMetadata = [], string $type = 'siswa'): array
    {
        $metadata = array_merge($existingMetadata, [
            'historical' => true,
            'attendance_subject_type' => $type,
            'import_source' => trim((string) ($this->options['source_label'] ?? 'Import historis presensi')),
            'imported_at' => now()->toIso8601String(),
            'imported_by' => $this->options['imported_by'] ?? auth()->id(),
        ]);

        if ($this->period && $type === 'siswa') {
            $metadata['point_period_id'] = $this->period->id;
            $metadata['point_period_name'] = $this->period->name;
        }

        return $metadata;
    }

    protected function normalizeStatus($value): string
    {
        $status = strtolower(trim((string) $value));

        return match ($status) {
            'hadir' => 'hadir',
            'terlambat', 'telat' => 'terlambat',
            'izin', 'ijin' => 'izin',
            'sakit' => 'sakit',
            'tidak_hadir', 'tidak hadir', 'alpa', 'absen', 'alpha' => 'alpha',
            default => 'alpha',
        };
    }

    protected function parseDate($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Carbon::createFromFormat('Y-m-d', gmdate('Y-m-d', ($value - 25569) * 86400));
            }

            foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y'] as $format) {
                try {
                    return Carbon::createFromFormat($format, trim((string) $value));
                } catch (\Exception $e) {
                }
            }

            return Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function parseTime($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            if (is_numeric($value) && $value < 1) {
                $hours = floor($value * 24);
                $minutes = floor(($value * 24 - $hours) * 60);
                return sprintf('%02d:%02d', $hours, $minutes);
            }

            return trim((string) $value);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getResults(): array
    {
        return $this->results;
    }
}
