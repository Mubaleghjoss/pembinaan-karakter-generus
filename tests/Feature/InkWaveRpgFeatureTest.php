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

    public function test_proximity_encounter_requires_a_close_player_position_and_issues_a_scoped_question(): void
    {
        $siswa = Siswa::factory()->create();
        $map = $this->map();
        $npc = $this->npc($map);

        $this->actingAs($siswa, 'siswa')->get(route('siswa.rpg.inkwave.play', $map))->assertOk();
        $this->actingAs($siswa, 'siswa')->postJson(route('siswa.rpg.inkwave.encounter', $map), [
            'target_id' => 0,
            'event_kind' => 'proximity',
            'x' => 3,
            'y' => 3,
            'player_x' => 0,
            'player_y' => 0,
        ])->assertUnprocessable();

        $encounter = $this->proximityEncounter($siswa, $map);
        $this->actingAs($siswa, 'siswa')->postJson(route('siswa.rpg.inkwave.question', $map), [
            'encounter_token' => $encounter->json('encounter_token'),
        ])->assertOk()
            ->assertJsonPath('event_kind', 'proximity')
            ->assertJsonPath('question.id', $npc->id)
            ->assertJsonMissingPath('question.jawaban_benar');
    }

    public function test_proximity_answer_is_once_then_a_defeat_question_can_still_be_issued(): void
    {
        $siswa = Siswa::factory()->create();
        $map = $this->map();
        $npc = $this->npc($map, ['jawaban_benar' => 1, 'poin' => 13]);

        $token = $this->proximityEncounter($siswa, $map)->json('encounter_token');
        $this->actingAs($siswa, 'siswa')->postJson(route('siswa.rpg.inkwave.question', $map), ['encounter_token' => $token])->assertOk();
        $this->actingAs($siswa, 'siswa')->postJson(route('siswa.rpg.inkwave.answer', $map), [
            'question_id' => $npc->id,
            'answer_id' => 1,
            'points' => 999999,
        ])->assertOk()->assertJsonPath('event_kind', 'proximity')->assertJsonPath('points', 13);
        $this->actingAs($siswa, 'siswa')->postJson(route('siswa.rpg.inkwave.answer', $map), [
            'question_id' => $npc->id,
            'answer_id' => 1,
        ])->assertUnprocessable();

        $defeat = $this->encounter($siswa, $map, 'defeat');
        $this->actingAs($siswa, 'siswa')->postJson(route('siswa.rpg.inkwave.question', $map), [
            'encounter_token' => $defeat->json('encounter_token'),
        ])->assertOk()->assertJsonPath('event_kind', 'defeat');
        $this->assertDatabaseCount('point_transactions', 1);
    }

    public function test_proximity_encounter_is_rate_limited_per_target_while_stationary(): void
    {
        $siswa = Siswa::factory()->create();
        $map = $this->map();
        $this->npc($map);

        $token = $this->proximityEncounter($siswa, $map)->json('encounter_token');
        $this->actingAs($siswa, 'siswa')->postJson(route('siswa.rpg.inkwave.question', $map), ['encounter_token' => $token])->assertOk();
        $this->actingAs($siswa, 'siswa')->postJson(route('siswa.rpg.inkwave.answer', $map), ['question_id' => $map->activeNpcs()->first()->id, 'answer_id' => 3])->assertOk()->assertJsonPath('points', 0);

        $this->actingAs($siswa, 'siswa')->postJson(route('siswa.rpg.inkwave.encounter', $map), [
            'target_id' => 0,
            'event_kind' => 'proximity',
            'x' => 3,
            'y' => 3,
            'player_x' => 2,
            'player_y' => 3,
        ])->assertStatus(429);
        $this->assertDatabaseCount('point_transactions', 0);
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

    public function test_inkwave_visual_profile_exposes_two_team_bounded_low_poly_markers(): void
    {
        $map = $this->map();

        $this->actingAs(Siswa::factory()->create(), 'siswa')
            ->get(route('siswa.rpg.inkwave.play', $map))
            ->assertOk()
            ->assertSee("'inkwave-low-poly-v2'", false)
            ->assertSee("'pkg-cyan:rival-amber:neutral-slate'", false)
            ->assertSee("'64'", false);

        $script = file_get_contents(resource_path('js/rpg-3d.js'));
        $this->assertStringContainsString('const INKWAVE_MAX_TERRITORY_PATCHES = 64', $script);
        $this->assertStringContainsString('pkg: 0x22d3ee, rival: 0xf59e0b, neutral: 0x64748b', $script);
        $this->assertStringContainsString("decals.userData.inkwaveTeam = team", $script);
        $this->assertStringContainsString('this.addInkWaveArenaAccents(gridSize, colors);', $script);
        $this->assertStringContainsString('addInkWaveArenaAccents(gridSize, colors) {', $script);
        $this->assertStringContainsString('} else {', $script);
        $this->assertStringContainsString('const grid = new THREE.GridHelper', $script);
        $this->assertStringNotContainsString('TextureLoader', $script);
        $this->assertStringNotContainsString('https://', $script);
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

    public function test_rpg_play_serializes_dangerous_server_data_without_ending_the_script(): void
    {
        $payload = '</script><script>window.rpgSerializationPwned=true</script>';
        $map = $this->map([
            'nama' => $payload,
            'background_theme' => $payload,
            'enemies' => [[
                'x' => 3,
                'y' => 3,
                'avatar' => $payload,
                'speed_level' => 'normal',
                'intelligence_level' => 'normal',
            ]],
        ]);
        $this->npc($map, ['nama' => $payload]);

        $this->actingAs(Siswa::factory()->create(), 'siswa')
            ->get(route('siswa.rpg.play', $map))
            ->assertOk()
            ->assertDontSee($payload, false)
            ->assertSee('\\u003C', false);
    }

    public function test_rpg_play_registers_explicit_window_factory_and_root_x_data_contract(): void
    {
        $map = $this->map();

        $this->actingAs(Siswa::factory()->create(), 'siswa')
            ->get(route('siswa.rpg.play', $map))
            ->assertOk()
            ->assertSee('x-data="rpgGame()"', false)
            ->assertSee('window.rpgGame = function rpgGame()', false)
            ->assertSee('data-rpg-factory', false);

        $this->actingAs(Siswa::factory()->create(), 'siswa')
            ->get(route('siswa.rpg.inkwave.play', $map))
            ->assertOk()
            ->assertSee('x-data="rpgGame()"', false)
            ->assertSee('window.rpgGame = function rpgGame()', false)
            ->assertSee('data-rpg-factory', false);
    }

    public function test_inkwave_view_exposes_a_mode_scoped_quantized_position_bridge(): void
    {
        $map = $this->map();

        $this->actingAs(Siswa::factory()->create(), 'siswa')
            ->get(route('siswa.rpg.inkwave.play', $map))
            ->assertOk()
            ->assertSee('syncInkWavePosition: ({ x, y })', false)
            ->assertSee('shootInkWave: (detail = {})', false)
            ->assertSee('checkInkWaveProximity()', false)
            ->assertSee('Tantangan pendekatan', false)
            ->assertSee('Tantangan kemenangan', false);
    }

    public function test_inkwave_assets_expose_ray_held_fire_and_collision_markers(): void
    {
        $script = file_get_contents(resource_path('js/rpg-3d.js'));

        $this->assertStringContainsString('this.aimOrigin = new THREE.Vector3()', $script);
        $this->assertStringContainsString('this.camera.getWorldDirection(this.aimDirection)', $script);
        $this->assertStringContainsString('this.inkWaveFireHeld = true', $script);
        $this->assertStringContainsString('INKWAVE_SHOT_INTERVAL_MS', $script);
        $this->assertStringContainsString('this.isObstacleWorldPoint(previousX + (stepX * ratio)', $script);
        $this->assertStringContainsString('this.dispatchInkWaveShoot({ enemy: hitEnemy })', $script);
        $this->assertStringContainsString("if (this.inkwaveMode) {\n                this.tryInkWaveHeldFire", $script);
        $this->assertStringContainsString('this.dispatchShoot(direction.dx, direction.dy)', $script);
    }

    public function test_inkwave_phase_three_exposes_bounded_jump_and_bomb_markers(): void
    {
        $script = file_get_contents(resource_path('js/rpg-3d.js'));

        $this->assertStringContainsString('this.verticalY = 0', $script);
        $this->assertStringContainsString('INKWAVE_JUMP_MAX_Y', $script);
        $this->assertStringContainsString('this.resetInkWaveVerticalState()', $script);
        $this->assertStringContainsString('INKWAVE_BOMB_COST = 35', $script);
        $this->assertStringContainsString('INKWAVE_BOMB_CAP = 2', $script);
        $this->assertStringContainsString('this.hasInkWaveLineOfSightFrom(blastX, blastZ', $script);
        $this->assertStringContainsString('if (target) this.dispatchInkWaveShoot({ enemy: target.enemy })', $script);
        $this->assertStringContainsString('Serialize to one authoritative encounter', $script);
    }

    public function test_adventure_controls_are_isolated_from_phase_three_actions(): void
    {
        $script = file_get_contents(resource_path('js/rpg-3d.js'));

        $this->assertStringContainsString("if (action === 'jump' && this.inkwaveMode)", $script);
        $this->assertStringContainsString("if (action === 'bomb' && this.inkwaveMode)", $script);
        $this->assertStringContainsString("this.inkwaveMode ? 'W/S/A/D gerak", $script);
        $this->assertStringContainsString("if (!this.inkwaveMode) return;", $script);
        $this->assertStringContainsString('this.dispatchShoot(direction.dx, direction.dy)', $script);
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

    public function test_inkwave_exposes_local_four_vs_four_end_spawn_and_bounded_respawn_markers(): void
    {
        $map = $this->map([
            'obstacles' => [['x' => 0, 'y' => 0], ['x' => 9, 'y' => 9]],
        ]);

        $this->actingAs(Siswa::factory()->create(), 'siswa')
            ->get(route('siswa.rpg.inkwave.play', $map))
            ->assertOk()
            ->assertSee('inkwaveTeamSize: 4', false)
            ->assertSee('initializeInkWaveTeams()', false)
            ->assertSee('const byOwnEnd', false)
            ->assertSee('const byRivalEnd', false)
            ->assertSee("team: 'rival'", false)
            ->assertSee('inkwaveRespawnSeconds = 3', false)
            ->assertSee('findInkWaveRivalSpawnTile()', false)
            ->assertSee('educational progress and territory stay intact', false);
    }

    public function test_inkwave_continuous_actor_loop_exposes_teammate_objectives_and_world_state(): void
    {
        $map = $this->map();

        $this->actingAs(Siswa::factory()->create(), 'siswa')
            ->get(route('siswa.rpg.inkwave.play', $map))
            ->assertOk()
            ->assertSee('updateInkWaveActors(0.25)', false)
            ->assertSee("this.steerInkWaveActor(actor, target || player, delta, now, 'pkg'", false)
            ->assertSee("actor.objective = distance > 4.5 ? 'advance'", false)
            ->assertSee('worldX: Number(tile.x)', false)
            ->assertSee('thinkCooldown: 0', false)
            ->assertSee('this.slideInkWaveActor(actor', false);
    }

    public function test_inkwave_renderer_uses_continuous_positions_without_changing_adventure_targets(): void
    {
        $script = file_get_contents(resource_path('js/rpg-3d.js'));

        $this->assertStringContainsString('inkWaveActorWorldPosition(actor)', $script);
        $this->assertStringContainsString('this.inkWaveActorWorldPosition(enemy)', $script);
        $this->assertStringContainsString('this.inkwaveMode ? 280 : 620', $script);
        $this->assertStringContainsString('this.dispatchShoot(direction.dx, direction.dy)', $script);
    }

    public function test_inkwave_death_flow_has_no_point_award_request(): void
    {
        $map = $this->map();

        $response = $this->actingAs(Siswa::factory()->create(), 'siswa')
            ->get(route('siswa.rpg.inkwave.play', $map))
            ->assertOk();

        $html = $response->getContent();
        $deathFlow = substr($html, strpos($html, 'beginInkWaveRespawn()'), 1800);
        $this->assertStringNotContainsString('points', $deathFlow);
        $this->assertStringNotContainsString('total_score', $deathFlow);
        $this->assertDatabaseCount('point_transactions', 0);
    }

    public function test_adventure_view_keeps_combat_state_disabled_by_mode_guard(): void
    {
        $map = $this->map();

        $this->actingAs(Siswa::factory()->create(), 'siswa')
            ->get(route('siswa.rpg.play', $map))
            ->assertOk()
            ->assertViewHas('inkwaveMode', false)
            ->assertSee('if (!this.inkwaveMode || this.inkwaveRespawnOpen || this.inkwavePaused) return;', false)
            ->assertSee('ammo: 0,', false);
    }

    public function test_inkwave_exposes_a_bounded_ink_tank_and_own_territory_reload_markers(): void
    {
        $map = $this->map();

        $this->actingAs(Siswa::factory()->create(), 'siswa')
            ->get(route('siswa.rpg.inkwave.play', $map))
            ->assertOk()
            ->assertSee('inkwaveInkMax: 100', false)
            ->assertSee('inkwaveInkShotCost: 12', false)
            ->assertSee('startInkWaveInkTank()', false)
            ->assertSee('this.inkwaveTerritory?.cells?.[`${x}:${y}`]', false)
            ->assertSee("this.inkwaveInkReloadState = 'neutral'", false)
            ->assertSee('Math.min(this.inkwaveInkMax', false);
    }

    public function test_inkwave_phase_four_exposes_bounded_evasion_duration_cooldown_and_hit_reduction(): void
    {
        $script = file_get_contents(resource_path('js/rpg-3d.js'));
        $view = file_get_contents(resource_path('views/siswa/rpg/play.blade.php'));

        $this->assertStringContainsString('INKWAVE_EVASION_DURATION_MS = 1500', $script);
        $this->assertStringContainsString('INKWAVE_EVASION_COOLDOWN_MS = 6500', $script);
        $this->assertStringContainsString('INKWAVE_EVASION_HIT_MULTIPLIER = 0.48', $script);
        $this->assertStringContainsString('now + INKWAVE_EVASION_DURATION_MS', $script);
        $this->assertStringContainsString('this.resetInkWaveEvasionState()', $script);
        $this->assertStringContainsString('this.inkWaveEvasionHitMultiplier()', $script);
        $this->assertStringContainsString('shot.damage * airborneFactor * this.inkWaveEvasionHitMultiplier()', $script);
        $this->assertStringNotContainsString('this.applyInkWaveDamage((enemy.intelligence_level', $view);
    }

    public function test_inkwave_phase_four_controls_visuals_and_adventure_isolation_are_mode_scoped(): void
    {
        $script = file_get_contents(resource_path('js/rpg-3d.js'));

        $this->assertStringContainsString("event.code === 'ShiftLeft' || event.code === 'ShiftRight'", $script);
        $this->assertStringContainsString('data-rpg-3d-action="evasion"', $script);
        $this->assertStringContainsString("if (action === 'evasion' && this.inkwaveMode)", $script);
        $this->assertStringContainsString('group.userData.evasionAura = evasionAura', $script);
        $this->assertStringContainsString('this.inkwaveMode && now < this.inkWaveEvasionUntil', $script);
        $this->assertStringContainsString("this.inkwaveMode ? 'W/S/A/D gerak", $script);
        $this->assertStringContainsString("'W/S maju, A/D geser, Q/E putar, Space tembak.", $script);
    }

    public function test_inkwave_rival_projectiles_use_swept_collision_and_only_damage_on_a_real_hit(): void
    {
        $script = file_get_contents(resource_path('js/rpg-3d.js'));
        $view = file_get_contents(resource_path('views/siswa/rpg/play.blade.php'));

        $fireStart = strpos($view, '        inkwaveEnemyFireTick() {');
        $fireEnd = strpos($view, '        applyInkWaveDamage(', $fireStart);
        $fire = substr($view, $fireStart, $fireEnd - $fireStart);
        $this->assertStringContainsString('fireInkWaveEnemyShot', $fire);
        $this->assertStringNotContainsString('applyInkWaveDamage', $fire);
        $this->assertStringContainsString('const samples = Math.max(1, Math.ceil', $script);
        $this->assertStringContainsString('this.isObstacleWorldPoint(previousX + (stepX * ratio)', $script);
        $this->assertStringContainsString('if (hitPlayer)', $script);
        $this->assertStringContainsString('now < shot.ttl', $script);
    }

    public function test_inkwave_respawn_protection_contact_cooldown_and_hostile_clear_are_bounded(): void
    {
        $view = file_get_contents(resource_path('views/siswa/rpg/play.blade.php'));
        $script = file_get_contents(resource_path('js/rpg-3d.js'));

        $this->assertStringContainsString('inkwaveSpawnProtectionMs: 2500', $view);
        $this->assertStringContainsString("source === 'contact' ? 900 : 360", $view);
        $this->assertStringContainsString('now < this.inkwaveSpawnProtectionUntil', $view);
        $this->assertStringContainsString('clearInkWaveHostileShots', $view);
        $this->assertStringContainsString('this.inkWaveEnemyShots.length = 0', $script);
        $this->assertStringContainsString('enemy._nextShotAt = this.inkwaveSpawnProtectionUntil', $view);
    }

    public function test_inkwave_allies_acquire_shoot_with_los_and_defeat_without_education_rewards(): void
    {
        $view = file_get_contents(resource_path('views/siswa/rpg/play.blade.php'));
        $allyCombat = substr($view, strpos($view, 'nearestInkWaveRival(actor'), 6500);

        $this->assertStringContainsString('this.inkwaveHasLineOfSight', $allyCombat);
        $this->assertStringContainsString('this.inkwaveAllyFire(actor, target, now)', $allyCombat);
        $this->assertStringContainsString('inkwaveAllyProjectileCap: 12', $view);
        $this->assertStringContainsString('this.inkwaveSegmentBlocked', $allyCombat);
        $this->assertStringContainsString('this.scheduleEnemyRespawn(defeated)', $allyCombat);
        $this->assertStringNotContainsString('handleEnemyDefeated(defeated)', $allyCombat);
        $this->assertStringNotContainsString('requestInkWaveQuestion', $allyCombat);
    }

    public function test_inkwave_spawn_zones_are_opposite_disjoint_and_use_continuous_actor_avoidance(): void
    {
        $view = file_get_contents(resource_path('views/siswa/rpg/play.blade.php'));

        $this->assertStringContainsString('tile.y < zoneDepth', $view);
        $this->assertStringContainsString('tile.y >= this.gridSize - zoneDepth', $view);
        $this->assertStringContainsString('minimumCrossDistance', $view);
        $this->assertStringContainsString('Math.hypot(Number(actor.worldX ?? actor.x)', $view);
        $this->assertStringContainsString('findInkWaveSafeSpawn()', $view);
        $this->assertStringContainsString('findInkWaveRivalSpawnTile()', $view);
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

    private function encounter(Siswa $siswa, RpgMap $map, string $eventKind = 'defeat')
    {
        $this->actingAs($siswa, 'siswa')->get(route('siswa.rpg.inkwave.play', $map))->assertOk();
        $this->travel(2)->seconds();

        return $this->actingAs($siswa, 'siswa')->postJson(route('siswa.rpg.inkwave.encounter', $map), [
            'target_id' => 0,
            'event_kind' => $eventKind,
            'x' => 3,
            'y' => 3,
        ])->assertOk();
    }

    private function proximityEncounter(Siswa $siswa, RpgMap $map)
    {
        $this->actingAs($siswa, 'siswa')->get(route('siswa.rpg.inkwave.play', $map))->assertOk();

        return $this->actingAs($siswa, 'siswa')->postJson(route('siswa.rpg.inkwave.encounter', $map), [
            'target_id' => 0,
            'event_kind' => 'proximity',
            'x' => 3,
            'y' => 3,
            'player_x' => 2,
            'player_y' => 3,
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
