<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource untuk transformasi data Siswa
 *
 * Resource ini mengubah model Siswa menjadi format JSON
 * yang konsisten untuk response API.
 * Field sensitif seperti password, qr_token, dan qr_secret_salt di-exclude.
 */
class SiswaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nis' => $this->nis,
            'nama' => $this->nama,
            'jenis_kelamin' => $this->jenis_kelamin,
            'tanggal_lahir' => $this->tanggal_lahir?->format('Y-m-d'),
            'kelompok' => $this->kelompok,
            'kelompok_label' => $this->kelompok_label,
            'foto_url' => $this->foto_url,
            'status' => $this->status,
            'is_active' => $this->is_active,
            'nama_wali' => $this->nama_wali,
            'phone_wali' => $this->phone_wali,
            'age' => $this->age,
            'full_identity' => $this->full_identity,
            'kelas' => new KelasResource($this->whenLoaded('kelas')),
            'presensi_count' => $this->whenCounted('presensi'),
            'valid_biometric_credentials_count' => $this->whenCounted('validBiometricCredentials'),
            'legacy_biometric_credentials_count' => $this->whenCounted('legacyBiometricCredentials'),
            'biometric_status' => $this->resolveBiometricStatus(),
            'last_login_at' => $this->last_login_at?->toISOString(),
            'is_biodata_complete' => $this->is_biodata_complete,
            'missing_biodata_fields' => $this->missing_biodata_fields,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function resolveBiometricStatus(): string
    {
        $validCount = (int) ($this->valid_biometric_credentials_count ?? 0);
        $legacyCount = (int) ($this->legacy_biometric_credentials_count ?? 0);

        if ($validCount > 0) {
            return 'active';
        }

        if ($legacyCount > 0) {
            return 'legacy';
        }

        return 'inactive';
    }
}
