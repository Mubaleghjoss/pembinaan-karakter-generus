<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
            'face_proof' => $this->faceProof(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function faceProof(): ?array
    {
        $face = data_get($this->metadata, 'face');

        if (! is_array($face) || data_get($face, 'method') !== 'face') {
            return null;
        }

        $proofPath = data_get($face, 'proof_path');

        return [
            'proof_url' => $proofPath ? Storage::disk('public')->url($proofPath) : data_get($face, 'proof_url'),
            'similarity_percent' => data_get($face, 'similarity_percent'),
            'match_distance' => data_get($face, 'match_distance'),
            'distance_meters' => data_get($face, 'location.distance_meters'),
            'radius_meters' => data_get($face, 'location.radius_meters'),
            'accuracy_meters' => data_get($face, 'location.accuracy_meters'),
        ];
    }
}
