<?php

namespace Tests\Feature;

use App\Models\PointTransaction;
use App\Models\RpgMap;
use App\Models\RpgGameSession;
use App\Models\RpgNpc;
use App\Models\Siswa;
use App\Services\InkWaveTerritoryService;
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

    public function test_question_requires_an_encounter_token_hides_answer_key_and_allows_only_one_pending_question_per_student_and_map(): void
    {
        $siswa = Siswa::factory()->create();
        $map = $this->map();
        $npc = $this->npc($map, ['jawaban_benar' => 2]);

        $this->actingAs($siswa, 'siswa')
            ->postJson(route('siswa.rpg.inkwave.question', $map))
            ->assertUnprocessable();

        $response = $this->question($siswa, $map)
            ->assertOk()
            ->assertJsonPath('question.id', $npc->id)
            ->assertJsonMissingPath('question.jawaban_benar')
            ->assertJsonMissingPath('question.correct_answer');

        $this->assertArrayNotHasKey('jawaban_benar', $response->json('question'));
        $this->actingAs($siswa, 'siswa')
            ->postJson(route('siswa.rpg.inkwave.encounter', $map), ['target_id' => 0, 'x' => 3, 'y' => 3])
            ->assertStatus(409);
    }

    public function test_expired_or_replayed_encounter_token_cannot_issue_a_duplicate_question(): void
    {
        $siswa = Siswa::factory()->create();
        $map = $this->map();
        $this->npc($map);

        $token = $this->encounter($siswa, $map)->json('encounter_token');
        $this->travel(21)->seconds();
        $this->actingAs($siswa, 'siswa')
            ->postJson(route('siswa.rpg.inkwave.question', $map), ['encounter_token' => $token])
            ->assertUnprocessable();

        $token = $this->encounter($siswa, $map)->json('encounter_token');
        $this->actingAs($siswa, 'siswa')
            ->postJson(route('siswa.rpg.inkwave.question', $map), ['encounter_token' => $token])
            ->assertOk();
        $this->actingAs($siswa, 'siswa')
            ->postJson(route('siswa.rpg.inkwave.question', $map), ['encounter_token' => $token])
            ->assertUnprocessable();
    }

    public function test_answer_requires_the_matching_pending_question_and_cannot_be_replayed(): void
    {
        $siswa = Siswa::factory()->create();
        $map = $this->map();
        $npc = $this->npc($map, ['jawaban_benar' => 1, 'poin' => 17]);

        $this->actingAs($siswa, 'siswa')
            ->postJson(route('siswa.rpg.inkwave.answer', $map), ['question_id' => $npc->id, 'answer_id' => 1])
            ->assertUnprocessable();

        $this->question($siswa, $map)->assertOk();
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

        $this->question($siswa, $map)->assertOk();
        $this->actingAs($siswa, 'siswa')
            ->postJson(route('siswa.rpg.inkwave.answer', $map), ['question_id' => $npc->id, 'answer_id' => 0])
            ->assertOk()
            ->assertJsonPath('correct', false)
            ->assertJsonPath('points', 0);
        $this->assertDatabaseCount('point_transactions', 0);

        $this->question($siswa, $map)->assertOk();
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

        $this->question($siswa, $map)
            ->assertOk()
            ->assertJsonPath('question.id', $hard->id);
        $this->actingAs($siswa, 'siswa')
            ->postJson(route('siswa.rpg.inkwave.answer', $map), ['question_id' => $hard->id, 'answer_id' => 0])
            ->assertOk();

        $this->question($siswa, $map)
            ->assertUnprocessable();
        $this->assertNotSame($easy->id, $hard->id);
    }

    public function test_completed_inkwave_match_returns_an_authoritative_summary_after_reload(): void
    {
        $siswa = Siswa::factory()->create();
        $map = $this->map(['inkwave_max_questions' => 1]);
        $npc = $this->npc($map, ['poin' => 19]);

        $this->question($siswa, $map)->assertOk();
        $this->actingAs($siswa, 'siswa')
            ->postJson(route('siswa.rpg.inkwave.answer', $map), ['question_id' => $npc->id, 'answer_id' => 0])
            ->assertOk()
            ->assertJsonPath('completed', true);

        $this->question($siswa, $map)
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

        $this->question($siswa, $map)
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
        $this->question($siswa, $map)->assertOk();
        Cache::put("inkwave:summary:{$siswa->id}:{$map->id}", ['points' => 99], now()->addHour());

        $this->actingAs($siswa, 'siswa')->postJson(route('siswa.rpg.reset', $map))->assertOk();

        $this->assertNull(Cache::get("inkwave:pending:{$siswa->id}:{$map->id}"));
        $this->assertNull(Cache::get("inkwave:active:{$siswa->id}:{$map->id}"));
        $this->assertNull(Cache::get("inkwave:summary:{$siswa->id}:{$map->id}"));
        $this->assertNull(Cache::get("inkwave:match:{$siswa->id}:{$map->id}"));
    }

    public function test_accepted_encounter_applies_isolated_idempotent_territory_without_points(): void
    {
        $siswa = Siswa::factory()->create();
        $map = $this->map();
        $this->npc($map);

        $response = $this->encounter($siswa, $map)
            ->assertJsonPath('territory.version', 1)
            ->assertJsonPath('territory.pkg_cells', 9)
            ->assertJsonPath('territory.neutral_cells', 91);
        $this->assertDatabaseCount('point_transactions', 0);
        $this->assertSame(0, (int) RpgGameSession::query()->where('siswa_id', $siswa->id)->value('total_score'));

        $territory = app(InkWaveTerritoryService::class);
        $first = $territory->snapshot($siswa->id, $map);
        $replayed = $territory->applyPulse($siswa->id, $map, $response->json('encounter_token'), 3, 3);
        $this->assertSame($first['version'], $replayed['version']);
        $this->assertSame($first['cells'], $replayed['cells']);
    }

    public function test_territory_is_bounded_and_reset_clears_only_inkwave_state(): void
    {
        $siswa = Siswa::factory()->create();
        $map = $this->map(['grid_size' => 30]);
        $territory = app(InkWaveTerritoryService::class);
        for ($i = 0; $i < 25; $i++) {
            $territory->applyPulse($siswa->id, $map, "event-{$i}", ($i % 5) * 3, intdiv($i, 5) * 3);
        }
        $this->assertLessThanOrEqual(144, $territory->snapshot($siswa->id, $map)['pkg_cells']);

        RpgGameSession::query()->create(['siswa_id' => $siswa->id, 'rpg_map_id' => $map->id, 'pos_x' => 0, 'pos_y' => 0, 'total_score' => 0, 'answered_npcs' => []]);
        $this->actingAs($siswa, 'siswa')->postJson(route('siswa.rpg.reset', $map))->assertOk();
        $this->assertNull(Cache::get($territory->key($siswa->id, $map->id)));
        $this->assertDatabaseHas('rpg_game_sessions', ['siswa_id' => $siswa->id, 'rpg_map_id' => $map->id, 'total_score' => 0]);
    }

    public function test_reset_invalidates_an_unconsumed_encounter_token(): void
    {
        $siswa = Siswa::factory()->create();
        $map = $this->map();
        $this->npc($map);
        $token = $this->encounter($siswa, $map)->json('encounter_token');

        $this->actingAs($siswa, 'siswa')->postJson(route('siswa.rpg.reset', $map))->assertOk();
        $this->actingAs($siswa, 'siswa')
            ->postJson(route('siswa.rpg.inkwave.question', $map), ['encounter_token' => $token])
            ->assertUnprocessable();
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

    public function test_inkwave_view_exposes_a_mode_scoped_quantized_position_bridge(): void
    {
        $map = $this->map();

        $this->actingAs(Siswa::factory()->create(), 'siswa')
            ->get(route('siswa.rpg.inkwave.play', $map))
            ->assertOk()
            ->assertSee('syncInkWavePosition: ({ x, y })', false)
            ->assertSee('shootInkWave: ({ targetId })', false);
    }

    public function test_inkwave_view_exposes_mode_scoped_mutual_combat_and_respawn_state(): void
    {
        $map = $this->map();

        $this->actingAs(Siswa::factory()->create(), 'siswa')
            ->get(route('siswa.rpg.inkwave.play', $map))
            ->assertOk()
            ->assertSee('inkwaveHealth: 100', false)
            ->assertSee('inkwaveEnemyFireTick()', false)
            ->assertSee('retryInkWave: ()', false)
            ->assertSee('inkwaveCombat: this.inkwaveMode ?', false);
    }

    public function test_adventure_view_keeps_combat_state_disabled_by_mode_guard(): void
    {
        $map = $this->map();

        $this->actingAs(Siswa::factory()->create(), 'siswa')
            ->get(route('siswa.rpg.play', $map))
            ->assertOk()
            ->assertViewHas('inkwaveMode', false)
            ->assertSee('if (!this.inkwaveMode || this.inkwaveRespawnOpen || this.inkwavePaused) return;', false);
    }

    public function test_client_supplied_combat_points_are_ignored(): void
    {
        $siswa = Siswa::factory()->create();
        $map = $this->map();
        $this->npc($map, ['poin' => 11]);

        $this->actingAs($siswa, 'siswa')->get(route('siswa.rpg.inkwave.play', $map))->assertOk();
        $this->travel(2)->seconds();
        $this->actingAs($siswa, 'siswa')
            ->postJson(route('siswa.rpg.inkwave.encounter', $map), [
                'target_id' => 0,
                'x' => 3,
                'y' => 3,
                'points' => 999999,
                'damage' => 999999,
            ])
            ->assertOk();

        $this->assertDatabaseCount('point_transactions', 0);
        $this->assertSame(0, (int) RpgGameSession::query()->where('siswa_id', $siswa->id)->value('total_score'));
    }

    public function test_grid_position_endpoint_rejects_fractional_world_coordinates(): void
    {
        $siswa = Siswa::factory()->create();
        $map = $this->map();
        RpgGameSession::query()->create([
            'siswa_id' => $siswa->id,
            'rpg_map_id' => $map->id,
            'pos_x' => 0,
            'pos_y' => 0,
            'total_score' => 0,
            'answered_npcs' => [],
        ]);

        $this->actingAs($siswa, 'siswa')
            ->postJson(route('siswa.rpg.move', $map), ['pos_x' => 1.25, 'pos_y' => 2.5])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['pos_x', 'pos_y']);

        $this->assertDatabaseHas('rpg_game_sessions', [
            'siswa_id' => $siswa->id,
            'rpg_map_id' => $map->id,
            'pos_x' => 0,
            'pos_y' => 0,
        ]);
    }

    public function test_match_timer_is_wall_clock_and_rejects_encounters_after_the_boundary(): void
    {
        $siswa = Siswa::factory()->create();
        $map = $this->map();
        $this->npc($map);

        $this->actingAs($siswa, 'siswa')->get(route('siswa.rpg.inkwave.play', $map))->assertOk();
        $this->travel(180)->seconds(); // The question modal never pauses this server clock.

        $this->actingAs($siswa, 'siswa')
            ->postJson(route('siswa.rpg.inkwave.encounter', $map), ['target_id' => 0, 'x' => 3, 'y' => 3])
            ->assertUnprocessable()
            ->assertJsonPath('match.status', 'finished')
            ->assertJsonPath('summary.territory.coverage_percent', 0);
    }

    public function test_finished_match_summary_survives_reload_without_awarding_points(): void
    {
        $siswa = Siswa::factory()->create();
        $map = $this->map();
        $this->npc($map);

        $this->actingAs($siswa, 'siswa')->get(route('siswa.rpg.inkwave.play', $map))->assertOk();
        $this->travel(181)->seconds();

        $this->actingAs($siswa, 'siswa')
            ->getJson(route('siswa.rpg.inkwave.state', $map))
            ->assertOk()
            ->assertJsonPath('match.finished', true)
            ->assertJsonPath('summary.points', 0)
            ->assertJsonPath('summary.territory.pkg_cells', 0);
        $this->assertDatabaseCount('point_transactions', 0);

        $this->actingAs($siswa, 'siswa')
            ->get(route('siswa.rpg.inkwave.play', $map))
            ->assertOk()
            ->assertSee('inkwaveMatchCompleted: true', false);
    }

    private function question(Siswa $siswa, RpgMap $map)
    {
        $encounter = $this->encounter($siswa, $map);

        return $this->actingAs($siswa, 'siswa')->postJson(
            route('siswa.rpg.inkwave.question', $map),
            ['encounter_token' => $encounter->json('encounter_token')]
        );
    }

    private function encounter(Siswa $siswa, RpgMap $map)
    {
        $this->actingAs($siswa, 'siswa')->get(route('siswa.rpg.inkwave.play', $map))->assertOk();
        $this->travel(2)->seconds();

        return $this->actingAs($siswa, 'siswa')->postJson(route('siswa.rpg.inkwave.encounter', $map), [
            'target_id' => 0,
            'x' => 3,
            'y' => 3,
        ])->assertOk();
    }

    private function map(array $attributes = []): RpgMap
    {
        return RpgMap::query()->create(array_merge([
            'nama' => 'Peta InkWave',
            'grid_size' => 10,
            'background_theme' => 'grass',
            'is_active' => true,
            'enemies' => [['x' => 3, 'y' => 3, 'avatar' => 'enemy_slime', 'speed_level' => 'normal', 'intelligence_level' => 'normal']],
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
