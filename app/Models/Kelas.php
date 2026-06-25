<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kelas extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'kelas';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nama',
        'tingkat',
        'kode_kelas',
        'pamong_id',
        'kapasitas',
        'deskripsi',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the pamong assigned to this kelas (many-to-many).
     */
    public function pamong(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'kelas_pamong', 'kelas_id', 'pamong_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Get kelas_pamong records.
     */
    public function kelasPamong(): HasMany
    {
        return $this->hasMany(KelasPamong::class);
    }

    /**
     * Get the siswa for the kelas.
     */
    public function siswa(): HasMany
    {
        return $this->hasMany(Siswa::class);
    }

    /**
     * Get active students count
     */
    public function getActiveStudentsCountAttribute(): int
    {
        return $this->siswa()->active()->count();
    }

    /**
     * Check if class is full
     */
    public function isFull(): bool
    {
        return $this->active_students_count >= $this->kapasitas;
    }

    /**
     * Get available capacity
     */
    public function getAvailableCapacityAttribute(): int
    {
        return max(0, $this->kapasitas - $this->active_students_count);
    }

    /**
     * Scope for active classes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for classes by level
     */
    public function scopeByTingkat($query, $tingkat)
    {
        return $query->where('tingkat', $tingkat);
    }
}
