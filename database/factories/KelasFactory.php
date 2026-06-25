<?php

namespace Database\Factories;

use App\Models\Kelas;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Kelas>
 */
class KelasFactory extends Factory
{
    protected $model = Kelas::class;

    private static int $counter = 0;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        self::$counter++;
        $letters = ['A', 'B', 'C'];
        $tingkats = ['1', '2', '3', '4', '5', '6'];

        return [
            'nama' => $letters[self::$counter % 3].'-'.((self::$counter % 6) + 1),
            'tingkat' => $tingkats[self::$counter % 6],
            'kode_kelas' => 'KLS-'.str_pad(self::$counter, 4, '0', STR_PAD_LEFT),
            'pamong_id' => null,
            'kapasitas' => 30,
            'deskripsi' => 'Deskripsi kelas '.self::$counter,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the kelas is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
