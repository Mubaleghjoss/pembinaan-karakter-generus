<?php

namespace App\Services;

use App\Models\PamongSiswa;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PamongAssignmentBoardService
{
    public function version(): string
    {
        return $this->versionFromRows($this->activeRows());
    }

    /**
     * @param  array<int, array{siswa_id: int, pamong_ids: array<int, int>}>  $studentAssignments
     * @return array{version: string, affected_students: int, added: int, ended: int}
     */
    public function update(array $studentAssignments, User $admin, string $expectedVersion): array
    {
        return DB::transaction(function () use ($studentAssignments, $admin, $expectedVersion): array {
            $lockedRows = PamongSiswa::query()
                ->whereNull('ended_at')
                ->orderBy('pamong_id')
                ->orderBy('siswa_id')
                ->lockForUpdate()
                ->get(['id', 'pamong_id', 'siswa_id']);

            $currentVersion = $this->versionFromRows($lockedRows);

            if (! hash_equals($currentVersion, $expectedVersion)) {
                throw new PamongAssignmentVersionConflict($currentVersion);
            }

            $studentIds = collect($studentAssignments)
                ->pluck('siswa_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();
            $requestedPamongIds = collect($studentAssignments)
                ->flatMap(fn (array $assignment) => $assignment['pamong_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            $validStudentIds = Siswa::query()
                ->active()
                ->whereKey($studentIds)
                ->pluck('id');

            if ($validStudentIds->count() !== $studentIds->count()) {
                throw ValidationException::withMessages([
                    'students' => 'Hanya Generus aktif yang dapat dimasukkan ke Binaan Pamong.',
                ]);
            }

            $validPamongIds = User::query()
                ->where('status', 'active')
                ->whereKey($requestedPamongIds)
                ->whereHas('role', fn ($query) => $query->where('name', User::ROLE_TEACHER))
                ->pluck('id');

            if ($validPamongIds->count() !== $requestedPamongIds->count()) {
                throw ValidationException::withMessages([
                    'students' => 'Tujuan Binaan harus menggunakan akun Pamong yang masih aktif.',
                ]);
            }

            $added = 0;
            $ended = 0;

            foreach ($studentAssignments as $assignment) {
                $studentId = (int) $assignment['siswa_id'];
                $desiredPamongIds = collect($assignment['pamong_ids'] ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();
                $currentPamongIds = PamongSiswa::query()
                    ->where('siswa_id', $studentId)
                    ->whereNull('ended_at')
                    ->pluck('pamong_id');
                $toEnd = $currentPamongIds->diff($desiredPamongIds)->values();
                $toAdd = $desiredPamongIds->diff($currentPamongIds)->values();

                if ($toEnd->isNotEmpty()) {
                    $ended += PamongSiswa::query()
                        ->where('siswa_id', $studentId)
                        ->whereIn('pamong_id', $toEnd)
                        ->whereNull('ended_at')
                        ->update([
                            'ended_at' => now(),
                            'ended_by' => $admin->id,
                        ]);
                }

                foreach ($toAdd as $pamongId) {
                    PamongSiswa::query()->updateOrCreate(
                        [
                            'pamong_id' => $pamongId,
                            'siswa_id' => $studentId,
                        ],
                        [
                            'ended_at' => null,
                            'ended_by' => null,
                        ]
                    );
                    $added++;
                }
            }

            return [
                'version' => $this->version(),
                'affected_students' => $studentIds->count(),
                'added' => $added,
                'ended' => $ended,
            ];
        }, 3);
    }

    private function activeRows(): Collection
    {
        return PamongSiswa::query()
            ->whereNull('ended_at')
            ->orderBy('pamong_id')
            ->orderBy('siswa_id')
            ->get(['pamong_id', 'siswa_id']);
    }

    private function versionFromRows(Collection $rows): string
    {
        return hash('sha256', $rows
            ->map(fn (PamongSiswa $assignment) => $assignment->pamong_id.':'.$assignment->siswa_id)
            ->implode('|'));
    }
}
