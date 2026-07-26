<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'permissions',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the users for the role.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function getDisplayNameAttribute($value): ?string
    {
        $roleName = $this->attributes['name'] ?? null;
        if (
            $roleName === User::ROLE_TEACHER
            || ($roleName !== User::ROLE_GURU && in_array($value, ['Guru', 'Guru/Pamong'], true))
        ) {
            return 'Pamong';
        }

        return $value;
    }

    /**
     * Check if role has permission
     */
    public function hasPermission(string $permission): bool
    {
        return $this->permissions &&
            (in_array('*', $this->permissions, true) ||
             in_array($permission, $this->permissions, true));
    }

    /**
     * Add permission to role
     */
    public function addPermission(string $permission): void
    {
        $permissions = $this->permissions ?? [];
        if (! in_array($permission, $permissions)) {
            $permissions[] = $permission;
            $this->update(['permissions' => $permissions]);
        }
    }

    /**
     * Remove permission from role
     */
    public function removePermission(string $permission): void
    {
        $permissions = $this->permissions ?? [];
        $permissions = array_filter($permissions, fn ($p) => $p !== $permission);
        $this->update(['permissions' => array_values($permissions)]);
    }
}
