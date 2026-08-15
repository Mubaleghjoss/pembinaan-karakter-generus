<?php

namespace App\Services;

use App\Models\GenerusRegistration;
use App\Models\GenerusRegistrationInvite;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class GenerusRegistrationService
{
    public function register(
        GenerusRegistrationInvite $invite,
        array $data,
        Request $request,
        ?Siswa $existingSiswa = null
    ): array {
        $documentDirectory = 'generus-registrations/'.Str::uuid();
        $downloadToken = Str::random(48);
        $parentSignaturePath = "{$documentDirectory}/orang-tua.png";
        $studentSignaturePath = "{$documentDirectory}/generus.png";
        $oldSignaturePaths = [];

        Storage::disk('local')->put(
            $parentSignaturePath,
            $this->decodeSignature($data['parent_signature'], 'Tanda tangan orang tua')
        );
        Storage::disk('local')->put(
            $studentSignaturePath,
            $this->decodeSignature($data['student_signature'], 'Tanda tangan Generus')
        );

        try {
            [$registration, $accountCreated, $oldSignaturePaths] = DB::transaction(function () use (
                $invite,
                $data,
                $request,
                $existingSiswa,
                $downloadToken,
                $parentSignaturePath,
                $studentSignaturePath
            ) {
                $lockedInvite = GenerusRegistrationInvite::query()->lockForUpdate()->findOrFail($invite->id);

                if (! $lockedInvite->isAvailable()) {
                    throw ValidationException::withMessages([
                        'invitation' => 'Kode akses sudah tidak berlaku atau kuotanya habis.',
                    ]);
                }

                if ($existingSiswa) {
                    return $this->updateExistingRegistration(
                        $lockedInvite,
                        $existingSiswa,
                        $data,
                        $request,
                        $downloadToken,
                        $parentSignaturePath,
                        $studentSignaturePath
                    );
                }

                return $this->createRegistration(
                    $lockedInvite,
                    $data,
                    $request,
                    $downloadToken,
                    $parentSignaturePath,
                    $studentSignaturePath
                );
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->deleteDirectory($documentDirectory);
            throw $exception;
        }

        foreach (array_filter($oldSignaturePaths) as $oldPath) {
            if (! in_array($oldPath, [$parentSignaturePath, $studentSignaturePath], true)) {
                Storage::disk('local')->delete($oldPath);
            }
        }

        return [$registration, $downloadToken, $accountCreated];
    }

    private function createRegistration(
        GenerusRegistrationInvite $invite,
        array $data,
        Request $request,
        string $downloadToken,
        string $parentSignaturePath,
        string $studentSignaturePath
    ): array {
        $publicId = (string) Str::uuid();
        $registration = GenerusRegistration::query()->create($this->registrationPayload(
            $invite,
            $data,
            $request,
            $publicId,
            $downloadToken,
            $parentSignaturePath,
            $studentSignaturePath
        ));

        $nis = $this->generateNis($registration->id);
        $siswa = Siswa::query()->create([
            'nis' => $nis,
            'password' => $nis,
            'nama' => trim($data['student_name']),
            'tempat_lahir' => trim($data['birth_place']),
            'tanggal_lahir' => $data['birth_date'],
            'kelompok' => $data['kelompok'],
            'alamat' => $data['kelompok'],
            'phone' => $this->normalizePhone($data['student_phone']),
            'school_grade' => $data['school_grade'],
            'target_grade_override' => null,
            'profile_assignment_confirmed_at' => now(),
            'status' => 'active',
            'nama_wali' => trim($data['parent_name']),
            'phone_wali' => $this->normalizePhone($data['parent_phone']),
            'ortu_username' => $nis,
            'ortu_password' => $nis,
            'metadata' => [
                'registration_public_id' => $publicId,
                'registration_source' => 'private_parent_form',
            ],
            'is_active' => true,
        ]);

        $registration->update(['siswa_id' => $siswa->id]);
        $invite->increment('used_count');

        return [$registration->fresh('siswa'), true, []];
    }

    private function updateExistingRegistration(
        GenerusRegistrationInvite $invite,
        Siswa $existingSiswa,
        array $data,
        Request $request,
        string $downloadToken,
        string $parentSignaturePath,
        string $studentSignaturePath
    ): array {
        $siswa = Siswa::query()->lockForUpdate()->findOrFail($existingSiswa->id);
        $registration = GenerusRegistration::query()
            ->where('siswa_id', $siswa->id)
            ->lockForUpdate()
            ->first();
        $publicId = $registration?->public_id ?: (string) Str::uuid();
        $oldSignaturePaths = $registration
            ? [$registration->parent_signature_path, $registration->student_signature_path]
            : [];

        $siswaData = [
            'nama' => trim($data['student_name']),
            'tempat_lahir' => trim($data['birth_place']),
            'tanggal_lahir' => $data['birth_date'],
            'kelompok' => $data['kelompok'],
            'phone' => $this->normalizePhone($data['student_phone']),
            'school_grade' => $data['school_grade'],
            'target_grade_override' => null,
            'profile_assignment_confirmed_at' => now(),
            'nama_wali' => trim($data['parent_name']),
            'phone_wali' => $this->normalizePhone($data['parent_phone']),
        ];

        if (blank($siswa->alamat)) {
            $siswaData['alamat'] = $data['kelompok'];
        }
        if (blank($siswa->password)) {
            $siswaData['password'] = $siswa->nis;
        }
        if (blank($siswa->ortu_username)) {
            $siswaData['ortu_username'] = $siswa->nis;
        }
        if (blank($siswa->ortu_password)) {
            $siswaData['ortu_password'] = $siswa->nis;
        }

        $metadata = is_array($siswa->metadata) ? $siswa->metadata : [];
        $siswaData['metadata'] = array_merge($metadata, [
            'registration_public_id' => $publicId,
            'registration_source' => 'existing_account_completion',
        ]);
        $siswa->update($siswaData);

        $payload = $this->registrationPayload(
            $invite,
            $data,
            $request,
            $publicId,
            $downloadToken,
            $parentSignaturePath,
            $studentSignaturePath
        );
        $payload['siswa_id'] = $siswa->id;

        if ($registration) {
            $registration->update($payload);
        } else {
            $registration = GenerusRegistration::query()->create($payload);
            $invite->increment('used_count');
        }

        return [$registration->fresh('siswa'), false, $oldSignaturePaths];
    }

    private function registrationPayload(
        GenerusRegistrationInvite $invite,
        array $data,
        Request $request,
        string $publicId,
        string $downloadToken,
        string $parentSignaturePath,
        string $studentSignaturePath
    ): array {
        return [
            'public_id' => $publicId,
            'invite_id' => $invite->id,
            'download_token_hash' => hash('sha256', $downloadToken),
            'parent_name' => trim($data['parent_name']),
            'parent_phone' => $this->normalizePhone($data['parent_phone']),
            'student_name' => trim($data['student_name']),
            'student_phone' => $this->normalizePhone($data['student_phone']),
            'kelompok' => $data['kelompok'],
            'birth_place' => trim($data['birth_place']),
            'birth_date' => $data['birth_date'],
            'school_grade' => $data['school_grade'],
            'parent_signature_path' => $parentSignaturePath,
            'student_signature_path' => $studentSignaturePath,
            'statement_version' => 'v1',
            'statement_accepted_at' => now(),
            'submitted_at' => now(),
            'source_ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
        ];
    }

    private function generateNis(int $registrationId): string
    {
        return 'PKG'.now()->format('ymd').str_pad((string) $registrationId, 5, '0', STR_PAD_LEFT);
    }

    private function normalizePhone(string $phone): string
    {
        $normalized = preg_replace('/[^0-9+]/', '', trim($phone)) ?: '';

        if (str_starts_with($normalized, '+62')) {
            return '0'.substr($normalized, 3);
        }
        if (str_starts_with($normalized, '62')) {
            return '0'.substr($normalized, 2);
        }

        return $normalized;
    }

    private function decodeSignature(string $dataUrl, string $label): string
    {
        if (! preg_match('/^data:image\/png;base64,([A-Za-z0-9+\/=]+)$/', $dataUrl, $matches)) {
            throw ValidationException::withMessages([
                'signature' => "{$label} tidak valid. Silakan hapus lalu tanda tangani kembali.",
            ]);
        }

        $binary = base64_decode($matches[1], true);
        $imageInfo = $binary !== false ? @getimagesizefromstring($binary) : false;

        if ($binary === false || strlen($binary) > 1000000 || ! $imageInfo || ($imageInfo['mime'] ?? null) !== 'image/png') {
            throw ValidationException::withMessages([
                'signature' => "{$label} tidak valid atau terlalu besar.",
            ]);
        }

        return $binary;
    }
}
