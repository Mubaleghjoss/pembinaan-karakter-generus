<?php

namespace App\Imports;

use App\Models\TracerKarakter;
use App\Models\Siswa;
use App\Models\Karakter;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Http\UploadedFile;

class TracerKarakterImport
{
    protected $results = [
        'success' => 0,
        'failed' => 0,
        'errors' => [],
    ];

    public function import(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);
        
        // Get headers from first row
        $headers = array_shift($rows);
        $headers = array_map('strtolower', array_map('trim', $headers));
        
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }
            
            // Map row to associative array
            $data = array_combine($headers, $row);
            
            try {
                // Find siswa by NIS
                $nis = trim($data['nis'] ?? '');
                if (empty($nis)) {
                    $this->results['failed']++;
                    $this->results['errors'][] = "Baris {$rowNumber}: NIS tidak boleh kosong.";
                    continue;
                }
                
                $siswa = Siswa::where('nis', $nis)->first();
                
                if (!$siswa) {
                    $this->results['failed']++;
                    $this->results['errors'][] = "Baris {$rowNumber}: Siswa dengan NIS {$nis} tidak ditemukan.";
                    continue;
                }

                // Find karakter by name
                $karakterNama = trim($data['karakter'] ?? '');
                if (empty($karakterNama)) {
                    $this->results['failed']++;
                    $this->results['errors'][] = "Baris {$rowNumber}: Nama karakter tidak boleh kosong.";
                    continue;
                }
                
                $karakter = Karakter::where('nama', $karakterNama)->first();
                
                if (!$karakter) {
                    $this->results['failed']++;
                    $this->results['errors'][] = "Baris {$rowNumber}: Karakter '{$karakterNama}' tidak ditemukan.";
                    continue;
                }

                // Parse date
                $checkedAt = $this->parseDate($data['tanggal'] ?? null);
                if (!$checkedAt) {
                    $checkedAt = now();
                }

                // Check if record exists for same siswa, karakter, and date
                $existing = TracerKarakter::where('siswa_id', $siswa->id)
                    ->where('karakter_id', $karakter->id)
                    ->whereDate('checked_at', $checkedAt)
                    ->first();

                if ($existing) {
                    $existing->update([
                        'catatan' => $data['catatan'] ?? $existing->catatan,
                    ]);
                } else {
                    TracerKarakter::create([
                        'siswa_id' => $siswa->id,
                        'karakter_id' => $karakter->id,
                        'pamong_id' => auth()->id(),
                        'checked_at' => $checkedAt,
                        'catatan' => $data['catatan'] ?? null,
                    ]);
                }

                $this->results['success']++;
            } catch (\Exception $e) {
                $this->results['failed']++;
                $this->results['errors'][] = "Baris {$rowNumber}: " . $e->getMessage();
            }
        }
        
        return $this->results;
    }

    protected function parseDate($value)
    {
        if (empty($value)) return null;
        
        try {
            if (is_numeric($value)) {
                return Carbon::createFromFormat('Y-m-d', gmdate('Y-m-d', ($value - 25569) * 86400));
            }
            
            $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y'];
            foreach ($formats as $format) {
                try {
                    return Carbon::createFromFormat($format, trim($value));
                } catch (\Exception $e) {
                    continue;
                }
            }
            
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getResults(): array
    {
        return $this->results;
    }
}
