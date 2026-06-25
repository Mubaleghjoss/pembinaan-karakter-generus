<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PRSubmission extends Model
{
    use HasFactory;

    protected $table = 'pr_submissions';

    protected $fillable = [
        'pr_id',
        'siswa_id',
        'proof_type',
        'proof_path',
        'submitted_at',
        'status',
        'verified_by',
        'verified_at',
        'catatan_verifikasi',
        'is_late',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
        'is_late' => 'boolean',
    ];

    public function pekerjaanRumah(): BelongsTo
    {
        return $this->belongsTo(PekerjaanRumah::class, 'pr_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    public function needsRevision(): bool
    {
        return $this->status === 'revision';
    }
}
