<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GenerusRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'invite_id',
        'siswa_id',
        'download_token_hash',
        'parent_name',
        'parent_phone',
        'student_name',
        'student_phone',
        'kelompok',
        'birth_place',
        'birth_date',
        'school_grade',
        'parent_signature_path',
        'student_signature_path',
        'statement_version',
        'statement_accepted_at',
        'submitted_at',
        'source_ip',
        'user_agent',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'statement_accepted_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function invite(): BelongsTo
    {
        return $this->belongsTo(GenerusRegistrationInvite::class, 'invite_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }
}
