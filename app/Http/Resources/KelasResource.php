<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource untuk transformasi data Kelas
 *
 * Resource ini mengubah model Kelas menjadi format JSON
 * yang konsisten untuk response API.
 */
class KelasResource extends JsonResource
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
            'nama' => $this->nama,
            'kode' => $this->kode,
            'tingkat' => $this->tingkat,
            'kapasitas' => $this->kapasitas,
            'is_active' => $this->is_active,
            'pamong' => new UserResource($this->whenLoaded('pamong')),
            'siswa_count' => $this->whenCounted('siswa'),
            'siswa' => SiswaResource::collection($this->whenLoaded('siswa')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
