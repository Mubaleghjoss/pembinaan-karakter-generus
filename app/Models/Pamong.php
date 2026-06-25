<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pamong extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'nama',
        'phone',
        'email',
        'nip',
        'tanggal_lahir',
        'jenis_kelamin',
        'alamat',
        'foto_path',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tanggal_lahir' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the pamong.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the kelas for the pamong.
     */
    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class);
    }

    /**
     * Get active classes count
     */
    public function getActiveClassesCountAttribute(): int
    {
        return $this->kelas()->active()->count();
    }

    /**
     * Get total students under this pamong
     */
    public function getTotalStudentsAttribute(): int
    {
        return $this->kelas()
            ->active()
            ->withCount(['siswa' => function ($query) {
                $query->active();
            }])
            ->get()
            ->sum('siswa_count');
    }

    /**
     * Scope for active pamong
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if pamong is active
     */
    public function isActive(): bool
    {
        return $this->is_active && $this->user && $this->user->isActive();
    }
}
