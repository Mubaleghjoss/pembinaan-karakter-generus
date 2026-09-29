<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class FaceProfile extends Model
{
    public const SUBJECT_SISWA = 'siswa';
    public const SUBJECT_USER = 'user';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_DISABLED = 'disabled';
    public const STATUS_REPLACED = 'replaced';

    protected $fillable = [
        'subject_type',
        'subject_id',
        'descriptor_payload',
        'photo_path',
        'status',
        'enrolled_by_user_id',
        'last_used_at',
        'metadata',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function enrolledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enrolled_by_user_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function subject(): Siswa|User|null
    {
        return match ($this->subject_type) {
            self::SUBJECT_SISWA => Siswa::query()->find($this->subject_id),
            self::SUBJECT_USER => User::query()->with('role')->find($this->subject_id),
            default => null,
        };
    }

    public function setDescriptor(array $descriptor): void
    {
        $this->descriptor_payload = Crypt::encryptString(json_encode(array_values($descriptor), JSON_THROW_ON_ERROR));
    }

    public function descriptor(): array
    {
        $decoded = json_decode(Crypt::decryptString($this->descriptor_payload), true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? array_map('floatval', $decoded) : [];
    }

    public function usesFaceModelContract(): bool
    {
        $model = data_get($this->metadata, 'face_model');

        return is_array($model)
            && data_get($model, 'name') === 'MobileFaceNet'
            && data_get($model, 'version') === 'qualcomm-mobilefacenet-v0.62.2'
            && data_get($model, 'input') === '112x112-rgb'
            && (int) data_get($model, 'dimensions') === 128
            && data_get($model, 'normalization') === 'l2';
    }

    public static function faceModelContract(): array
    {
        return [
            'name' => 'MobileFaceNet',
            'version' => 'qualcomm-mobilefacenet-v0.62.2',
            'input' => '112x112-rgb',
            'dimensions' => 128,
            'normalization' => 'l2',
        ];
    }

    public static function subjectTypeFor(object $subject): ?string
    {
        return match (true) {
            $subject instanceof Siswa => self::SUBJECT_SISWA,
            $subject instanceof User => self::SUBJECT_USER,
            default => null,
        };
    }
}
