<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Counter for unique values.
     */
    private static int $counter = 0;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password = null;

    public function configure(): static
    {
        return $this->afterMaking(function (User $user): void {
            $roleId = (int) ($user->role_id ?? 0);

            if ($roleId < 1 || Role::query()->whereKey($roleId)->exists()) {
                return;
            }

            $defaults = [
                1 => ['name' => 'admin', 'display_name' => 'Administrator', 'permissions' => ['*']],
                2 => ['name' => 'teacher', 'display_name' => 'Pamong', 'permissions' => ['view_students']],
            ];

            $role = $defaults[$roleId] ?? [
                'name' => "role_{$roleId}",
                'display_name' => "Role {$roleId}",
                'permissions' => [],
            ];

            Role::query()->create([
                'id' => $roleId,
                'name' => $role['name'],
                'display_name' => $role['display_name'],
                'permissions' => $role['permissions'],
                'is_active' => true,
            ]);
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        self::$counter++;

        return [
            'username' => 'user_test_'.self::$counter,
            'email' => 'user'.self::$counter.'@test.com',
            'phone' => '08123456'.str_pad(self::$counter, 4, '0', STR_PAD_LEFT),
            'password' => static::$password ??= Hash::make('password'),
            'role_id' => 1,
            'status' => 'active',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the user is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => 1,
        ]);
    }
}
