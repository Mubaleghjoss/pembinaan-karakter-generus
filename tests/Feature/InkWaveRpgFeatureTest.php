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
            ->assertJsonPath('points', 17)
            ->assertJsonPath('summary.gameplay_score', 17)
            ->assertJsonPath('summary.questions_answered', 1);
        $this->assertDatabaseCount('point_transactions', 1);
        $this->assertDatabaseHas('rpg_game_sessions', [
            'siswa_id' => $siswa->id,
            'rpg_map_id' => $map->id,
            'total_score' => 17,
        ]);

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

    public function test_inkwave_uses_configured_difficulty_order_repeat_policy_and_maximum(): void
    {
        $siswa = Siswa::factory()->create();
        $map = $this->map([
            'inkwave_randomize_questions' => false,
            'inkwave_question_difficulty' => 'hard',
            'inkwave_repeat_policy' => 'no_repeat',
            'inkwave_max_questions' => 1,
        ]);
        $easy = $this->npc($map, ['difficulty' => 'easy', 'nama' => 'Soal Mudah']);
        $hard = $this->npc($map, ['difficulty' => 'hard', 'nama' => 'Soal Sulit']);

        $this->actingAs($siswa, 'siswa')
            ->postJson(route('siswa.rpg.inkwave.question', $map))
            ->assertOk()
            ->assertJsonPath('question.id', $hard->id);
        $this->actingAs($siswa, 'siswa')
            ->postJson(route('siswa.rpg.inkwave.answer', $map), ['question_id' => $hard->id, 'answer_id' => 0])
            ->assertOk();

        $this->actingAs($siswa, 'siswa')
            ->postJson(route('siswa.rpg.inkwave.question', $map))
            ->assertUnprocessable();
        $this->assertNotSame($easy->id, $hard->id);
    }

    public function test_completed_inkwave_match_returns_an_authoritative_summary_after_reload(): void
    {
        $siswa = Siswa::factory()->create();
        $map = $this->map(['inkwave_max_questions' => 1]);
        $npc = $this->npc($map, ['poin' => 19]);

        $this->actingAs($siswa, 'siswa')->postJson(route('siswa.rpg.inkwave.question', $map))->assertOk();
        $this->actingAs($siswa, 'siswa')
            ->postJson(route('siswa.rpg.inkwave.answer', $map), ['question_id' => $npc->id, 'answer_id' => 0])
            ->assertOk()
            ->assertJsonPath('completed', true);

        $this->actingAs($siswa, 'siswa')
            ->postJson(route('siswa.rpg.inkwave.question', $map))
            ->assertUnprocessable()
            ->assertJsonPath('completed', true)
            ->assertJsonPath('summary.defeated', 1)
            ->assertJsonPath('summary.questions_answered', 1)
            ->assertJsonPath('summary.correct', 1)
            ->assertJsonPath('summary.points', 19)
            ->assertJsonPath('summary.gameplay_score', 19)
            ->assertJsonMissingPath('summary.question_ids');

        $this->actingAs($siswa, 'siswa')
            ->get(route('siswa.rpg.inkwave.play', $map))
            ->assertOk()
            ->assertSee('inkwaveQuestionSecondsLeft', false)
            ->assertSee('inkwaveMatchCompleted: true', false)
            ->assertSee('Ringkasan InkWave', false);
    }

    public function test_inkwave_rejects_an_answer_after_the_server_time_limit(): void
    {
        $siswa = Siswa::factory()->create();
        $map = $this->map(['inkwave_question_time_limit_seconds' => 5]);
        $npc = $this->npc($map);

        $this->actingAs($siswa, 'siswa')
            ->postJson(route('siswa.rpg.inkwave.question', $map))
            ->assertOk()
            ->assertJsonPath('question.time_limit_seconds', 5);

        $this->travel(6)->seconds();
        $this->actingAs($siswa, 'siswa')
            ->postJson(route('siswa.rpg.inkwave.answer', $map), ['question_id' => $npc->id, 'answer_id' => 0])
            ->assertUnprocessable();
        $this->assertDatabaseCount('point_transactions', 0);
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

    public function test_inkwave_play_locks_the_existing_3d_scene_to_low_graphics(): void
    {
        $map = $this->map();

        $this->actingAs(Siswa::factory()->create(), 'siswa')
            ->get(route('siswa.rpg.inkwave.play', $map))
            ->assertOk()
            ->assertViewIs('siswa.rpg.play')
            ->assertViewHas('inkwaveMode', true)
            ->assertSee("viewMode: '3d'", false)
            ->assertSee('data-rpg-3d-scene', false)
            ->assertSee(':data-rpg-3d-view-locked="inkwaveMode ? \'true\' : null"', false)
            ->assertSee(':data-rpg-3d-graphics="inkwaveMode ? \'low\' : null"', false)
            ->assertSee(':data-rpg-3d-inkwave="inkwaveMode ? \'true\' : null"', false);
    }

    public function test_adventure_route_remains_the_non_inkwave_mode(): void
    {
        $map = $this->map();

        $this->actingAs(Siswa::factory()->create(), 'siswa')
            ->get(route('siswa.rpg.play', $map))
            ->assertOk()
            ->assertViewIs('siswa.rpg.play')
            ->assertViewHas('inkwaveMode', false)
            ->assertSee('x-show="!inkwaveMode"', false);
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
