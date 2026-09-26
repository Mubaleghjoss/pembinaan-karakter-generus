<?php

namespace Tests\Feature;

use App\Models\PointTransaction;
use App\Models\RpgMap;
use App\Models\RpgNpc;
use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InkWaveRpgFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
        Cache::flush();

        // The production migration extends MySQL's enum; SQLite keeps the legacy CHECK constraint.
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA ignore_check_constraints = ON');
        }
    }

    public function test_public_inkwave_cta_sends_guests_to_login_and_preserves_the_map_picker(): void
    {
        $this->get(route('public.rpg.index'))
            ->assertOk()
            ->assertSee('InkWave')
            ->assertSee(route('siswa.rpg.index'), false)
            ->assertSee('Login siswa untuk mulai');

        $this->get(route('siswa.rpg.index'))
            ->assertRedirect(route('siswa.login'))
            ->assertSessionHas('url.intended', route('siswa.rpg.index'));
    }

    public function test_active_map_index_provides_a_valid_inkwave_entry(): void
    {
        $siswa = Siswa::factory()->create();
        $activeMap = $this->map();
        $this->map(['is_active' => false, 'nama' => 'Peta Nonaktif']);

        $this->actingAs($siswa, 'siswa')
            ->get(route('siswa.rpg.index'))
            ->assertOk()
            ->assertSee(route('siswa.rpg.inkwave.play', $activeMap), false)
            ->assertDontSee('Peta Nonaktif');
    }

    public function test_inkwave_routes_require_siswa_auth_and_reject_inactive_or_missing_maps(): void
    {
        $map = $this->map(['is_active' => false]);

        $this->postJson(route('siswa.rpg.inkwave.question', $map))
            ->assertUnauthorized();
        $this->actingAs(Siswa::factory()->create(), 'siswa')
            ->postJson(route('siswa.rpg.inkwave.question', $map))
            ->assertNotFound();
        $this->actingAs(Siswa::factory()->create(), 'siswa')
            ->postJson('/siswa/rpg/999999/inkwave/question')
            ->assertNotFound();
    }

    public function test_question_hides_answer_key_and_allows_only_one_pending_question_per_student_and_map(): void
    {
        $siswa = Siswa::factory()->create();
        $map = $this->map();
        $npc = $this->npc($map, ['jawaban_benar' => 2]);

        $response = $this->actingAs($siswa, 'siswa')
            ->postJson(route('siswa.rpg.inkwave.question', $map))
            ->assertOk()
            ->assertJsonPath('question.id', $npc->id)
            ->assertJsonMissingPath('question.jawaban_benar')
            ->assertJsonMissingPath('question.correct_answer');

        $this->assertArrayNotHasKey('jawaban_benar', $response->json('question'));
        $this->actingAs($siswa, 'siswa')
            ->postJson(route('siswa.rpg.inkwave.question', $map))
            ->assertStatus(409);
    }

    public function test_answer_requires_the_matching_pending_question_and_cannot_be_replayed(): void
    {
        $siswa = Siswa::factory()->create();
        $map = $this->map();
        $npc = $this->npc($map, ['jawaban_benar' => 1, 'poin' => 17]);

        $this->actingAs($siswa, 'siswa')
            ->postJson(route('siswa.rpg.inkwave.answer', $map), ['question_id' => $npc->id, 'answer_id' => 1])
            ->assertUnprocessable();

        $this->actingAs($siswa, 'siswa')->postJson(route('siswa.rpg.inkwave.question', $map))->assertOk();
        $this->actingAs($siswa, 'siswa')
            ->postJson(route('siswa.rpg.inkwave.answer', $map), ['question_id' => $npc->id + 1, 'answer_id' => 1])
            ->assertUnprocessable();
        $this->assertDatabaseCount('point_transactions', 0);

        // A mismatched id must leave the legitimate pending question available.
        $this->actingAs($siswa, 'siswa')
            ->postJson(route('siswa.rpg.inkwave.answer', $map), ['question_id' => $npc->id, 'answer_id' => 1])
            ->assertOk()
            ->assertJsonPath('correct', true)
            ->assertJsonPath('points', 17);
        $this->assertDatabaseCount('point_transactions', 1);

        $this->actingAs($siswa, 'siswa')
            ->postJson(route('siswa.rpg.inkwave.answer', $map), ['question_id' => $npc->id, 'answer_id' => 1])
            ->assertUnprocessable();
        $this->assertDatabaseCount('point_transactions', 1);
    }

    public function test_wrong_answer_awards_nothing_and_correct_answer_uses_server_configured_points(): void
    {
        $siswa = Siswa::factory()->create();
        $map = $this->map();
        $npc = $this->npc($map, ['jawaban_benar' => 2, 'poin' => 23]);

        $this->actingAs($siswa, 'siswa')->postJson(route('siswa.rpg.inkwave.question', $map))->assertOk();
        $this->actingAs($siswa, 'siswa')
            ->postJson(route('siswa.rpg.inkwave.answer', $map), ['question_id' => $npc->id, 'answer_id' => 0])
            ->assertOk()
            ->assertJsonPath('correct', false)
            ->assertJsonPath('points', 0);
        $this->assertDatabaseCount('point_transactions', 0);

        $this->actingAs($siswa, 'siswa')->postJson(route('siswa.rpg.inkwave.question', $map))->assertOk();
        $this->actingAs($siswa, 'siswa')
            ->postJson(route('siswa.rpg.inkwave.answer', $map), ['question_id' => $npc->id, 'answer_id' => 2])
            ->assertOk()
            ->assertJsonPath('correct', true)
            ->assertJsonPath('points', 23);

        $this->assertDatabaseHas('point_transactions', [
            'siswa_id' => $siswa->id,
            'source' => 'game',
            'points' => 23,
            'reference_type' => RpgNpc::class,
            'reference_id' => $npc->id,
        ]);
        $this->assertSame(23, PointTransaction::query()->where('siswa_id', $siswa->id)->sum('points'));
    }

    public function test_reset_clears_inkwave_pending_question_and_summary_for_the_map_session(): void
    {
        $siswa = Siswa::factory()->create();
        $map = $this->map();
        $this->npc($map);

        $this->actingAs($siswa, 'siswa')->get(route('siswa.rpg.inkwave.play', $map))->assertOk();
        $this->actingAs($siswa, 'siswa')->postJson(route('siswa.rpg.inkwave.question', $map))->assertOk();
        Cache::put("inkwave:summary:{$siswa->id}:{$map->id}", ['points' => 99], now()->addHour());

        $this->actingAs($siswa, 'siswa')->postJson(route('siswa.rpg.reset', $map))->assertOk();

        $this->assertNull(Cache::get("inkwave:pending:{$siswa->id}:{$map->id}"));
        $this->assertNull(Cache::get("inkwave:summary:{$siswa->id}:{$map->id}"));
    }

    public function test_adventure_route_remains_the_non_inkwave_mode(): void
    {
        $map = $this->map();

        $this->actingAs(Siswa::factory()->create(), 'siswa')
            ->get(route('siswa.rpg.play', $map))
            ->assertOk()
            ->assertViewIs('siswa.rpg.play')
            ->assertViewHas('inkwaveMode', false);
    }

    private function map(array $attributes = []): RpgMap
    {
        return RpgMap::query()->create(array_merge([
            'nama' => 'Peta InkWave',
            'grid_size' => 10,
            'background_theme' => 'grass',
            'is_active' => true,
        ], $attributes));
    }

    private function npc(RpgMap $map, array $attributes = []): RpgNpc
    {
        return RpgNpc::query()->create(array_merge([
            'rpg_map_id' => $map->id,
            'nama' => 'Penjaga Karakter',
            'avatar' => 'npc_guardian',
            'pos_x' => 2,
            'pos_y' => 2,
            'pertanyaan' => 'Pilih jawaban yang tepat.',
            'pilihan_jawaban' => ['Satu', 'Dua', 'Tiga', 'Empat'],
            'jawaban_benar' => 0,
            'poin' => 10,
            'is_active' => true,
        ], $attributes));
    }
}
