<?php

namespace Database\Factories;

use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Siswa>
 */
class SiswaFactory extends Factory
{
    protected $model = Siswa::class;

    private static int $counter = 0;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        self::$counter++;
        $genders = ['L', 'P'];

        return [
            'nis' => str_pad(self::$counter, 10, '0', STR_PAD_LEFT),
            'nama' => 'Siswa Test '.self::$counter,
            'jenis_kelamin' => $genders[self::$counter % 2],
            'tanggal_lahir' => now()->subYears(12)->format('Y-m-d'),
            'alamat' => 'Alamat Test '.self::$counter,
            'kelas_id' => Kelas::factory(),
            'school_grade' => \App\Support\TargetGrade::SMP_7,
            'foto_path' => null,
            'status' => 'active',
            'qr_secret_salt' => Str::random(64),
            'qr_token' => null,
            'qr_token_expires_at' => null,
            'nama_wali' => 'Wali '.self::$counter,
            'phone_wali' => '08123456'.str_pad(self::$counter, 4, '0', STR_PAD_LEFT),
            'email_wali' => 'wali'.self::$counter.'@test.com',
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the siswa is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
            'is_active' => false,
        ]);
    }
}
