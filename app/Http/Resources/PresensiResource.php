<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource untuk transformasi data Presensi
 *
 * Resource ini mengubah model Presensi menjadi format JSON
 * yang konsisten untuk response API.
 */
class PresensiResource extends JsonResource
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
            'tanggal' => $this->tanggal?->format('Y-m-d'),
            'jam_masuk' => $this->jam_masuk?->format('H:i:s'),
            'jam_keluar' => $this->jam_keluar?->format('H:i:s'),
            'status' => $this->status,
            'keterangan' => $this->keterangan,
            'is_verified' => $this->is_verified,
            'verified_at' => $this->verified_at?->toISOString(),
            'duration_minutes' => $this->duration,
            'siswa' => new SiswaResource($this->whenLoaded('siswa')),
            'verifier' => new UserResource($this->whenLoaded('verifier')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
