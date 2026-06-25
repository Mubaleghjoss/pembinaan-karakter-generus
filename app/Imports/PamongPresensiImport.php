<?php

namespace App\Imports;

use App\Models\PamongPresensi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PamongPresensiImport
{
    protected $results = [
        'success' => 0,
        'failed' => 0,
        'created' => 0,
        'updated' => 0,
        'errors' => [],
    ];

    public function __construct(
        protected array $options = []
    ) {}

    public function import(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $headers = array_shift($rows);
        $headers = array_map('strtolower', array_map('trim', $headers));

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            if (empty(array_filter($row))) {
                continue;
            }

            $data = array_combine($headers, $row);

            try {
                $user = $this->resolvePamong($data);
                if (!$user) {
                    $this->results['failed']++;
                    $this->results['errors'][] = "Baris {$rowNumber}: pamong tidak ditemukan dari username/email.";
                    continue;
                }

                $tanggal = $this->parseDate($data['tanggal'] ?? null);
                if (!$tanggal) {
                    $this->results['failed']++;
                    $this->results['errors'][] = "Baris {$rowNumber}: format tanggal tidak valid.";
                    continue;
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
                    'metadata' => array_merge($existing?->metadata ?? [], [
                        'historical' => true,
                        'import_source' => trim((string) ($this->options['source_label'] ?? 'Import historis presensi pamong')),
                        'imported_at' => now()->toIso8601String(),
                        'imported_by' => $this->options['imported_by'] ?? auth()->id(),
                    ]),
                ];

                if ($existing) {
                    $existing->update($payload);
                    $this->results['updated']++;
                } else {
                    PamongPresensi::create([
                        'user_id' => $user->id,
                        'tanggal' => $tanggal,
                    ] + $payload);
                    $this->results['created']++;
                }

                $this->results['success']++;
            } catch (\Exception $e) {
                $this->results['failed']++;
                $this->results['errors'][] = "Baris {$rowNumber}: " . $e->getMessage();
            }
        }

        return $this->results;
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

    protected function markVerified(): bool
    {
        return (bool) ($this->options['mark_verified'] ?? true);
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
        if (empty($value)) return null;

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
        if (empty($value)) return null;

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
}
