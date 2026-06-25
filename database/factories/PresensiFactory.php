<?php

namespace Database\Factories;

use App\Models\Presensi;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Presensi>
 */
class PresensiFactory extends Factory
{
    protected $model = Presensi::class;

    private static int $counter = 0;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        self::$counter++;
        $statuses = ['hadir', 'terlambat', 'izin', 'sakit', 'alpha'];

        return [
            'siswa_id' => Siswa::factory(),
            'tanggal' => Carbon::today()->subDays(self::$counter % 30),
            'jam_masuk' => '07:30:00',
            'jam_keluar' => '15:00:00',
            'status' => $statuses[self::$counter % 5],
            'is_verified' => false,
            'verified_by' => null,
            'verified_at' => null,
            'keterangan' => 'Keterangan '.self::$counter,
        ];
    }

    /**
     * Indicate that the presensi is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
            'verified_at' => Carbon::now(),
        ]);
    }

    /**
     * Indicate that the presensi is for today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'tanggal' => Carbon::today(),
        ]);
    }

    /**
     * Indicate that the presensi has status hadir.
     */
    public function hadir(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'hadir',
        ]);
    }
}
