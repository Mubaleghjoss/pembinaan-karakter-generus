<?php

namespace App\Services;

use App\Models\AttendanceSchedule;
use App\Models\FaceProfile;
use App\Models\Siswa;
use App\Models\User;
use App\Support\FaceAttendanceConfig;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class FaceAttendanceService
{
    public function config(): array
    {
        return FaceAttendanceConfig::all();
    }

    public function activeProfileFor(object $subject): ?FaceProfile
    {
        $subjectType = FaceProfile::subjectTypeFor($subject);

        if (! $subjectType || ! isset($subject->id)) {
            return null;
        }

        return FaceProfile::query()
            ->active()
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subject->id)
            ->latest('id')
            ->first();
    }

    public function enrollmentEnabledFor(object $subject): bool
    {
        $config = $this->config();

        if ($subject instanceof Siswa) {
            return (bool) $config['enabled_siswa'];
        }

        if ($subject instanceof User) {
            return (bool) $config['enabled_pamong']
                && $subject->hasAnyRole(User::attendanceRoleNames());
        }

        return false;
    }

    public function enroll(object $subject, array $descriptor, string $imageData, ?int $enrolledByUserId, array $metadata = []): FaceProfile
    {
        $subjectType = FaceProfile::subjectTypeFor($subject);

        if (! $subjectType || ! isset($subject->id)) {
            throw new InvalidArgumentException('Akun tidak didukung untuk pendaftaran wajah.');
        }

        $this->validateDescriptor($descriptor);
        $photoPath = $this->storeDataUrlImage($imageData, 'face-profiles/'.now()->format('Y/m'));

        FaceProfile::query()
            ->active()
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subject->id)
            ->update([
                'status' => FaceProfile::STATUS_REPLACED,
                'updated_at' => now(),
            ]);

        $profile = new FaceProfile([
            'subject_type' => $subjectType,
            'subject_id' => $subject->id,
            'photo_path' => $photoPath,
            'status' => FaceProfile::STATUS_ACTIVE,
            'enrolled_by_user_id' => $enrolledByUserId,
            'metadata' => $metadata ?: null,
        ]);
        $profile->setDescriptor($descriptor);
        $profile->save();

        return $profile;
    }

    public function eligibleSubjectTypesForSchedule(AttendanceSchedule $schedule): array
    {
        $config = $this->config();
        $types = [];

        if ($schedule->targetsSiswa() && $config['enabled_siswa']) {
            $types[] = FaceProfile::SUBJECT_SISWA;
        }

        if ($schedule->targetsPamong() && $config['enabled_pamong']) {
            $types[] = FaceProfile::SUBJECT_USER;
        }

        return $types;
    }

    public function validateLocation(array $location): array
    {
        $config = $this->config();
        $lat = (float) ($location['lat'] ?? 0);
        $lng = (float) ($location['lng'] ?? 0);
        $accuracy = (float) ($location['accuracy_meters'] ?? 0);
        $radiusMeters = (float) $config['radius_meters'];
        $maxAccuracy = (float) $config['max_accuracy_meters'];

        if ($accuracy <= 0) {
            throw new InvalidArgumentException('Akurasi lokasi tidak terbaca. Aktifkan GPS lalu coba lagi.');
        }

        if ($accuracy > $maxAccuracy) {
            throw new InvalidArgumentException(
                'Lokasi belum cukup akurat. Akurasi perangkat sekitar '.round($accuracy).' meter, batas maksimal '.round($maxAccuracy).' meter.'
            );
        }

        $distance = $this->distanceMeters($lat, $lng, (float) $config['center_lat'], (float) $config['center_lng']);

        if ($distance > $radiusMeters) {
            throw new InvalidArgumentException(
                'Lokasi Anda di luar radius presensi. Jarak sekitar '.round($distance).' meter dari titik presensi, batas '.round($radiusMeters).' meter.'
            );
        }

        return [
            'lat' => $lat,
            'lng' => $lng,
            'accuracy_meters' => $accuracy,
            'distance_meters' => $distance,
            'radius_meters' => $radiusMeters,
            'center_lat' => (float) $config['center_lat'],
            'center_lng' => (float) $config['center_lng'],
        ];
    }

    public function matchProfile(array $descriptor, array $subjectTypes): ?array
    {
        $best = $this->findBestProfileMatch($descriptor, $subjectTypes);

        if (! $best || ! $best['accepted']) {
            return null;
        }

        return $best;
    }

    public function findBestProfileMatch(array $descriptor, array $subjectTypes): ?array
    {
        $this->validateDescriptor($descriptor);

        $config = $this->config();
        $threshold = max(min((float) $config['match_threshold'], 100), 1);
        $best = null;

        FaceProfile::query()
            ->active()
            ->whereIn('subject_type', $subjectTypes)
            ->orderBy('id')
            ->chunk(100, function ($profiles) use ($descriptor, $threshold, &$best) {
                foreach ($profiles as $profile) {
                    try {
                        $storedDescriptor = $profile->descriptor();
                    } catch (\Throwable $exception) {
                        report($exception);
                        continue;
                    }

                    if (count($storedDescriptor) !== count($descriptor)) {
                        continue;
                    }

                    $subject = $profile->subject();

                    if (! $this->subjectCanAttend($subject)) {
                        continue;
                    }

                    $distance = $this->descriptorDistance($descriptor, $storedDescriptor);
                    $similarityPercent = $this->descriptorSimilarityPercent($distance);

                    if ($best === null || $distance < $best['distance']) {
                        $best = [
                            'profile' => $profile,
                            'subject' => $subject,
                            'distance' => $distance,
                            'threshold' => $threshold,
                            'accepted' => $similarityPercent >= $threshold,
                            'similarity_percent' => $similarityPercent,
                        ];
                    }
                }
            });

        return $best;
    }

    public function storeProofImage(string $imageData): string
    {
        return $this->storeDataUrlImage($imageData, 'face-attendance/proofs/'.now()->format('Y/m'));
    }

    public function descriptorDistance(array $descriptor, array $storedDescriptor): float
    {
        $sum = 0.0;

        foreach ($descriptor as $index => $value) {
            $delta = (float) $value - (float) $storedDescriptor[$index];
            $sum += $delta * $delta;
        }

        return sqrt($sum);
    }

    public function descriptorSimilarityPercent(float $distance): float
    {
        $similarity = 1 - ($distance / FaceAttendanceConfig::MATCH_DISTANCE_NORMALIZER);

        return round(max(0, min(1, $similarity)) * 100, 1);
    }

    public function distanceMeters(float $latA, float $lngA, float $latB, float $lngB): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($latB - $latA);
        $dLng = deg2rad($lngB - $lngA);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($latA)) * cos(deg2rad($latB)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function validateDescriptor(array $descriptor): void
    {
        if (count($descriptor) < 32 || count($descriptor) > 4096) {
            throw new InvalidArgumentException('Data wajah tidak lengkap. Posisikan wajah di tengah kamera lalu coba lagi.');
        }

        foreach ($descriptor as $value) {
            if (! is_numeric($value)) {
                throw new InvalidArgumentException('Data wajah tidak valid.');
            }
        }
    }

    private function subjectCanAttend(?object $subject): bool
    {
        if ($subject instanceof Siswa) {
            return (bool) $subject->is_active && $subject->status === 'active';
        }

        if ($subject instanceof User) {
            return $subject->status === 'active' && $subject->hasAnyRole(User::attendanceRoleNames());
        }

        return false;
    }

    private function storeDataUrlImage(string $imageData, string $directory): string
    {
        if (! preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,(.+)$/', $imageData, $matches)) {
            throw new InvalidArgumentException('Format foto tidak valid.');
        }

        $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
        $binary = base64_decode($matches[2], true);

        if ($binary === false) {
            throw new InvalidArgumentException('Foto tidak bisa diproses.');
        }

        if (strlen($binary) > 3 * 1024 * 1024) {
            throw new InvalidArgumentException('Ukuran foto terlalu besar. Coba ulangi dengan kamera resolusi lebih rendah.');
        }

        $path = trim($directory, '/').'/'.Str::uuid().'.'.$extension;
        Storage::disk('public')->put($path, $binary);

        return $path;
    }
}
