<?php

namespace App\Imports;

use App\Models\PamongSiswa;
use App\Models\Siswa;
use App\Models\User;
use App\Support\TargetGrade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SiswaImport
{
    protected array $errors = [];
    protected int $successCount = 0;
    protected int $failedCount = 0;

    /**
     * Import siswa dari file Excel
     */
    public function import(string $filePath, ?string $fotoZipPath = null): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        // Skip header row
        $header = array_shift($rows);
        
        // Validate header
        $headerLower = array_map('strtolower', array_map('trim', $header));
        
        // Extract photos from zip if provided
        $photoMap = [];
        if ($fotoZipPath && file_exists($fotoZipPath)) {
            $photoMap = $this->extractPhotos($fotoZipPath);
        }

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 because of 0-index and header row
            
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            try {
                $data = $this->mapRowToData($row, $headerLower);
                $this->validateAndCreate($data, $rowNumber, $photoMap);
            } catch (\Exception $e) {
                $this->errors[] = "Baris {$rowNumber}: " . $e->getMessage();
                $this->failedCount++;
            }
        }

        return [
            'success' => $this->successCount,
            'failed' => $this->failedCount,
            'errors' => $this->errors,
        ];
    }

    /**
     * Map row data to associative array
     */
    protected function mapRowToData(array $row, array $headers): array
    {
        // Map header names to expected field names (handle spaces and variations)
        $headerMapping = [
            'nis' => 'nis',
            'password' => 'password',
            'nama' => 'nama',
            'jenis kelamin' => 'jenis_kelamin',
            'jenis_kelamin' => 'jenis_kelamin',
            'jeniskelamin' => 'jenis_kelamin',
            'kelas' => 'school_grade',
            'nama kelas' => 'school_grade',
            'nama_kelas' => 'school_grade',
            'kelas sekolah' => 'school_grade',
            'kelas_sekolah' => 'school_grade',
            'pamong' => 'pamong_usernames',
            'username pamong' => 'pamong_usernames',
            'pamong username' => 'pamong_usernames',
            'pamong_usernames' => 'pamong_usernames',
            'tanggal lahir' => 'tanggal_lahir',
            'tanggal_lahir' => 'tanggal_lahir',
            'tgl lahir' => 'tanggal_lahir',
            'tgl_lahir' => 'tanggal_lahir',
            'kelompok' => 'kelompok',
            'alamat' => 'kelompok',
            'phone' => 'phone',
            'hp' => 'phone',
            'telepon' => 'phone',
            'no hp' => 'phone',
            'no_hp' => 'phone',
            'nama wali' => 'nama_wali',
            'nama_wali' => 'nama_wali',
            'namawali' => 'nama_wali',
            'phone wali' => 'phone_wali',
            'phone_wali' => 'phone_wali',
            'phonewali' => 'phone_wali',
            'telepon wali' => 'phone_wali',
            'hp wali' => 'phone_wali',
            'foto' => 'foto',
        ];
        
        $data = [];
        foreach ($headers as $index => $header) {
            $normalizedHeader = strtolower(trim($header));
            $fieldName = $headerMapping[$normalizedHeader] ?? $normalizedHeader;
            $data[$fieldName] = isset($row[$index]) ? trim((string)$row[$index]) : null;
        }
        return $data;
    }

    /**
     * Validate and create siswa
     */
    protected function validateAndCreate(array $data, int $rowNumber, array $photoMap): void
    {
        // Normalize jenis_kelamin before validation
        if (!empty($data['jenis_kelamin'])) {
            $jk = strtoupper(trim($data['jenis_kelamin']));
            // Handle various inputs
            if (in_array($jk, ['LAKI-LAKI', 'LAKI', 'MALE', 'M', 'L'])) {
                $data['jenis_kelamin'] = 'L';
            } elseif (in_array($jk, ['PEREMPUAN', 'WANITA', 'FEMALE', 'F', 'P'])) {
                $data['jenis_kelamin'] = 'P';
            } else {
                $data['jenis_kelamin'] = null; // Invalid value, set to null
            }
        }
        
        $data['school_grade'] = TargetGrade::normalizeSchoolClassInput($data['school_grade'] ?? null);

        $validator = Validator::make($data, [
            'nis' => 'required|string|max:20',
            'nama' => 'required|string|max:100',
            'password' => 'nullable|string|max:100',
            'jenis_kelamin' => 'nullable|in:L,P',
            'school_grade' => 'required|in:' . implode(',', TargetGrade::values()),
            'pamong_usernames' => 'nullable|string|max:500',
            'tanggal_lahir' => 'nullable|date',
            'kelompok' => 'nullable|in:' . implode(',', array_keys(\App\Models\Siswa::kelompokOptions())),
            'phone' => 'nullable|string|max:20',
            'nama_wali' => 'nullable|string|max:100',
            'phone_wali' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            throw new \Exception(implode(', ', $validator->errors()->all()));
        }

        // Check if NIS already exists
        if (Siswa::where('nis', $data['nis'])->exists()) {
            throw new \Exception("NIS {$data['nis']} sudah terdaftar");
        }

        $pamongUsernames = collect(preg_split('/[,;\n]+/', (string) ($data['pamong_usernames'] ?? '')))
            ->map(fn ($username) => trim($username))->filter()->unique()->values();
        $pamong = collect();
        if ($pamongUsernames->isNotEmpty()) {
            $pamong = User::query()
                ->whereIn('username', $pamongUsernames)
                ->where('status', 'active')
                ->whereHas('role', fn ($query) => $query->where('name', User::ROLE_TEACHER))
                ->get(['id', 'username']);
            $missing = $pamongUsernames->diff($pamong->pluck('username'));
            if ($missing->isNotEmpty()) {
                throw new \Exception('Username Pamong tidak ditemukan/aktif: ' . $missing->implode(', '));
            }
        }

        // Handle photo
        $fotoPath = null;
        $fotoFilename = $data['foto'] ?? null;
        if ($fotoFilename && isset($photoMap[$fotoFilename])) {
            $fotoPath = $photoMap[$fotoFilename];
        }

        // Determine password (use provided or default to NIS)
        $password = !empty($data['password']) ? $data['password'] : trim($data['nis']);

        // Create siswa
        $siswa = Siswa::create([
            'nis' => trim($data['nis']),
            'password' => $password,
            'nama' => trim($data['nama']),
            'jenis_kelamin' => $data['jenis_kelamin'] ?? null,
            'kelas_id' => null,
            'school_grade' => $data['school_grade'],
            'tanggal_lahir' => !empty($data['tanggal_lahir']) ? date('Y-m-d', strtotime($data['tanggal_lahir'])) : null,
            'kelompok' => \App\Models\Siswa::normalizeKelompok($data['kelompok'] ?? null),
            'phone' => $data['phone'] ?? null,
            'nama_wali' => $data['nama_wali'] ?? null,
            'phone_wali' => $data['phone_wali'] ?? null,
            'foto_path' => $fotoPath,
            'is_active' => true,
            'status' => 'active',
            'qr_secret_salt' => Str::random(64),
        ]);

        if ($pamong->isNotEmpty()) {
            foreach ($pamong as $user) {
                PamongSiswa::updateOrCreate(
                    ['pamong_id' => $user->id, 'siswa_id' => $siswa->id],
                    ['ended_at' => null, 'ended_by' => null]
                );
            }
        }

        $this->successCount++;
    }

    /**
     * Extract photos from zip file
     */
    protected function extractPhotos(string $zipPath): array
    {
        $photoMap = [];
        $extractPath = storage_path('app/temp/photos_' . time());
        
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) === true) {
            $zip->extractTo($extractPath);
            $zip->close();

            // Move photos to storage
            $files = glob($extractPath . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    $filename = basename($file);
                    $extension = pathinfo($filename, PATHINFO_EXTENSION);
                    
                    if (in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif'])) {
                        $newFilename = 'siswa/' . Str::uuid() . '.' . $extension;
                        Storage::disk('public')->put($newFilename, file_get_contents($file));
                        $photoMap[$filename] = $newFilename;
                    }
                }
            }

            // Cleanup
            $this->deleteDirectory($extractPath);
        }

        return $photoMap;
    }

    /**
     * Delete directory recursively
     */
    protected function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
