<?php

namespace App\Services;

use App\Models\GenerusRegistration;
use App\Models\GenerusRegistrationInvite;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class GenerusRegistrationService
{
    public function register(GenerusRegistrationInvite $invite, array $data, Request $request): array
    {
        $publicId = (string) Str::uuid();
        $downloadToken = Str::random(48);
        $parentSignature = $this->decodeSignature($data['parent_signature'], 'Tanda tangan orang tua');
        $studentSignature = $this->decodeSignature($data['student_signature'], 'Tanda tangan Generus');
        $signatureDirectory = "generus-registrations/{$publicId}";
        $parentSignaturePath = "{$signatureDirectory}/orang-tua.png";
        $studentSignaturePath = "{$signatureDirectory}/generus.png";

        Storage::disk('local')->put($parentSignaturePath, $parentSignature);
        Storage::disk('local')->put($studentSignaturePath, $studentSignature);

        try {
            $registration = DB::transaction(function () use (
                $invite,
                $data,
                $request,
                $publicId,
                $downloadToken,
                $parentSignaturePath,
                $studentSignaturePath
            ) {
                $lockedInvite = GenerusRegistrationInvite::query()->lockForUpdate()->findOrFail($invite->id);

                if (! $lockedInvite->isAvailable()) {
                    throw ValidationException::withMessages([
                        'invitation' => 'Tautan pendaftaran sudah tidak berlaku atau kuotanya habis.',
                    ]);
                }

                $registration = GenerusRegistration::query()->create([
                    'public_id' => $publicId,
                    'invite_id' => $lockedInvite->id,
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
                ]);

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
                    'target_grade_override' => $data['school_grade'],
                    'profile_assignment_confirmed_at' => now(),
                    'status' => 'active',
                    'nama_wali' => trim($data['parent_name']),
                    'phone_wali' => $this->normalizePhone($data['parent_phone']),
                    'ortu_username' => $nis,
                    'ortu_password' => Hash::make($nis),
                    'metadata' => [
                        'registration_public_id' => $publicId,
                        'registration_source' => 'private_parent_form',
                    ],
                    'is_active' => true,
                ]);

                $registration->update(['siswa_id' => $siswa->id]);
                $lockedInvite->increment('used_count');

                return $registration->fresh('siswa');
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->deleteDirectory($signatureDirectory);
            throw $exception;
        }

        return [$registration, $downloadToken];
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
