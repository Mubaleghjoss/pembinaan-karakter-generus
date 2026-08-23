<?php

namespace App\Http\Controllers;

use App\Models\RpgMap;
use App\Models\RpgNpc;
use App\Models\RpgGameSession;
use App\Models\RpgCharacter;
use App\Models\ThemeSetting;
use App\Support\RpgCatalog;
use App\Services\GamificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RpgGameController extends Controller
{
    private const RPG_PRESENCE_WINDOW_SECONDS = 20;
    private const PUBLIC_RPG_CACHE_SECONDS = 60;

    public function __construct()
    {
        $this->middleware('auth')->only([
            'adminStoreMap',
            'adminUpdateMap',
            'adminDuplicateMap',
            'adminDeleteMap',
            'adminGetMap',
            'adminStoreNpc',
            'adminUpdateNpc',
            'adminDeleteNpc',
        ]);
        $this->middleware('pamong.permission:game,view')->only([
            'adminIndex',
            'adminGetMap',
        ]);
        $this->middleware('pamong.permission:game,create')->only([
            'adminStoreMap',
            'adminDuplicateMap',
            'adminStoreNpc',
        ]);
        $this->middleware('pamong.permission:game,edit')->only([
            'adminUpdateMap',
            'adminUpdateNpc',
        ]);
        $this->middleware('pamong.permission:game,delete')->only([
            'adminDeleteMap',
            'adminDeleteNpc',
        ]);
    }

    // ============ SISWA METHODS ============

    /**
     * List available RPG maps
     */
    public function index()
    {
        $siswa = Auth::guard('siswa')->user();
        $maps = RpgMap::where('is_active', true)
            ->withCount(['activeNpcs as npc_count'])
            ->get();

        // Get sessions for this student
        $sessions = RpgGameSession::where('siswa_id', $siswa->id)
            ->pluck('total_score', 'rpg_map_id')
            ->toArray();

        $completedMaps = RpgGameSession::where('siswa_id', $siswa->id)
            ->whereNotNull('completed_at')
            ->pluck('rpg_map_id')
            ->toArray();

        // Map yang bos-nya sudah dikalahkan siswa ini.
        $bossDefeatedMaps = RpgGameSession::where('siswa_id', $siswa->id)
            ->whereNotNull('boss_defeated_at')
            ->pluck('rpg_map_id')
            ->toArray();

        // Get or create character
        $character = RpgCharacter::firstOrCreate(
            ['siswa_id' => $siswa->id],
            ['avatar' => RpgCatalog::resolvePlayerAvatar(null), 'nama_karakter' => $siswa->nama, 'warna' => '#3B82F6']
        );

        return view('siswa.rpg.index', compact('maps', 'sessions', 'completedMaps', 'bossDefeatedMaps', 'character'));
    }

    /**
     * Experimental first-person 3D maze demo before merging into the RPG gameplay.
     */
    public function beta3d(Request $request)
    {
        $siswa = Auth::guard('siswa')->user();
        $maps = RpgMap::with(['activeNpcs:id,rpg_map_id,nama,avatar,pos_x,pos_y,pertanyaan,pilihan_jawaban,jawaban_benar,poin,is_active'])
            ->where('is_active', true)
            ->orderBy('nama')
            ->get();

        $selectedMap = $maps->firstWhere('id', (int) $request->query('map')) ?? $maps->first();
        $betaMapPayload = null;

        if ($selectedMap) {
            $session = RpgGameSession::firstOrCreate(
                ['siswa_id' => $siswa->id, 'rpg_map_id' => $selectedMap->id],
                ['pos_x' => 0, 'pos_y' => 0, 'answered_npcs' => [], 'total_score' => 0]
            );
            $session->touch();

            $character = RpgCharacter::firstOrCreate(
                ['siswa_id' => $siswa->id],
                ['avatar' => RpgCatalog::resolvePlayerAvatar(null), 'nama_karakter' => $siswa->nama, 'warna' => '#3B82F6']
            );

            $betaMapPayload = [
                'id' => $selectedMap->id,
                'nama' => $selectedMap->nama,
                'grid_size' => $selectedMap->grid_size,
                'background_theme' => $selectedMap->background_theme,
                'obstacles' => $this->normalizeObstacles($selectedMap->obstacles),
                'npcs' => $selectedMap->activeNpcs
                    ->map(fn (RpgNpc $npc) => [
                        'id' => $npc->id,
                        'nama' => $npc->nama,
                        'avatar' => RpgCatalog::resolveNpcAvatar($npc->avatar),
                        'pos_x' => $npc->pos_x,
                        'pos_y' => $npc->pos_y,
                        'pertanyaan' => $npc->pertanyaan,
                        'pilihan_jawaban' => $npc->pilihan_jawaban ?? [],
                        'jawaban_benar' => $this->correctAnswerIndex($npc),
                        'poin' => $npc->poin,
                    ])
                    ->values(),
                'enemies' => RpgCatalog::normalizeEnemies($selectedMap->enemies),
                'difficulty' => $selectedMap->difficulty ?? 'easy',
                'shield_duration_seconds' => $selectedMap->shield_duration_seconds ?? 8,
                'ammo_per_pickup' => $selectedMap->ammo_per_pickup ?? 3,
                'shield_pickups_count' => $selectedMap->shield_pickups_count ?? 1,
                'ammo_pickups_count' => $selectedMap->ammo_pickups_count ?? 2,
                'session' => [
                    'pos_x' => $session->pos_x,
                    'pos_y' => $session->pos_y,
                ],
                'character' => [
                    'nama' => $character->nama_karakter ?: $siswa->nama,
                    'avatar' => $character->avatar,
                    'avatar_display' => RpgCatalog::resolvePlayerAvatar($character->avatar),
                    'warna' => $character->warna ?: '#3B82F6',
                ],
                'urls' => [
                    'state' => route('siswa.rpg.state', $selectedMap),
                    'move' => route('siswa.rpg.move', $selectedMap),
                ],
                'csrf_token' => csrf_token(),
            ];
        }

        return view('siswa.rpg.beta-3d', compact('maps', 'selectedMap', 'betaMapPayload'));
    }

    /**
     * Play an RPG map
     */
    public function play(RpgMap $rpgMap)
    {
        if (!$rpgMap->is_active) {
            return redirect()->route('siswa.rpg.index')->with('error', 'Map tidak tersedia.');
        }

        $siswa = Auth::guard('siswa')->user();

        // Get or create session
        $session = RpgGameSession::firstOrCreate(
            ['siswa_id' => $siswa->id, 'rpg_map_id' => $rpgMap->id],
            ['pos_x' => 0, 'pos_y' => 0, 'answered_npcs' => [], 'total_score' => 0]
        );

        // Touch session for online presence
        $session->touch();

        // Get character
        $character = RpgCharacter::firstOrCreate(
            ['siswa_id' => $siswa->id],
            ['avatar' => RpgCatalog::resolvePlayerAvatar(null), 'nama_karakter' => $siswa->nama, 'warna' => '#3B82F6']
        );

        // Get active NPCs
        $npcs = $rpgMap->activeNpcs()->get();

        // Get enemies and obstacles from map
        $enemies = RpgCatalog::normalizeEnemies($rpgMap->enemies);
        $obstacles = $rpgMap->obstacles ?? [];

        // Boss config (mode Petualangan): null bila map tak mengaktifkan bos.
        $boss = $rpgMap->boss_enabled
            ? RpgCatalog::normalizeBossConfig($rpgMap->boss_config, (int) $rpgMap->grid_size)
            : null;
        $bossDefeated = ! is_null($session->boss_defeated_at ?? null);

        return view('siswa.rpg.play', compact('rpgMap', 'session', 'character', 'npcs', 'enemies', 'obstacles', 'boss', 'bossDefeated'));
    }

    /**
     * AJAX: Move player
     */
    public function move(Request $request, RpgMap $rpgMap)
    {
        $request->validate([
            'pos_x' => 'required|integer|min:0|max:' . ($rpgMap->grid_size - 1),
            'pos_y' => 'required|integer|min:0|max:' . ($rpgMap->grid_size - 1),
        ]);

        $siswa = Auth::guard('siswa')->user();
        $session = RpgGameSession::where('siswa_id', $siswa->id)
            ->where('rpg_map_id', $rpgMap->id)
            ->first();

        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Session tidak ditemukan'], 404);
        }

        $newX = $request->pos_x;
        $newY = $request->pos_y;

        // Check obstacles
        $obstacles = $rpgMap->obstacles ?? [];
        foreach ($obstacles as $obs) {
            if (($obs['x'] ?? -1) == $newX && ($obs['y'] ?? -1) == $newY) {
                return response()->json(['success' => false, 'message' => 'Jalan terhalang!', 'blocked' => true]);
            }
        }

        $session->pos_x = $newX;
        $session->pos_y = $newY;
        $session->save(); // Also updates updated_at for presence

        // Check if there's an NPC at this position
        $npc = RpgNpc::where('rpg_map_id', $rpgMap->id)
            ->where('pos_x', $newX)
            ->where('pos_y', $newY)
            ->where('is_active', true)
            ->first();

        $npcEncounter = null;
        if ($npc && !$session->hasAnsweredNpc($npc->id)) {
            $npcEncounter = [
                'id' => $npc->id,
                'nama' => $npc->nama,
                'avatar' => $npc->avatar,
                'avatar_display' => RpgCatalog::resolveNpcAvatar($npc->avatar),
                'pertanyaan' => $npc->pertanyaan,
                'pilihan_jawaban' => $npc->pilihan_jawaban,
                'jawaban_benar' => $this->correctAnswerIndex($npc),
                'poin' => $npc->poin,
            ];
        }

        return response()->json([
            'success' => true,
            'pos_x' => $session->pos_x,
            'pos_y' => $session->pos_y,
            'npc_encounter' => $npcEncounter,
        ]);
    }

    /**
     * AJAX: Answer NPC question
     */
    public function answer(Request $request, RpgMap $rpgMap)
    {
        $request->validate([
            'npc_id' => 'required|integer|exists:rpg_npcs,id',
            'jawaban' => 'required|integer|min:0|max:3',
        ]);

        $siswa = Auth::guard('siswa')->user();
        $session = RpgGameSession::where('siswa_id', $siswa->id)
            ->where('rpg_map_id', $rpgMap->id)
            ->first();

        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Session tidak ditemukan'], 404);
        }

        $npc = RpgNpc::findOrFail($request->npc_id);

        // Already answered?
        if ($session->hasAnsweredNpc($npc->id)) {
            return response()->json(['success' => false, 'message' => 'Sudah dijawab sebelumnya']);
        }

        $correctAnswerIndex = $this->correctAnswerIndex($npc);
        $isCorrect = $this->isCorrectAnswer((int) $request->jawaban, $npc);

        // Only mark as answered if correct (wrong answers can be retried)
        if ($isCorrect) {
            $session->markNpcAnswered($npc->id);
            $session->total_score += $npc->poin;

            // Award gamification points
            try {
                $gamificationService = app(GamificationService::class);
                $siswaPoint = $gamificationService->getOrCreateSiswaPoint($siswa);
                $siswaPoint->addPoints(
                    $npc->poin,
                    'rpg',
                    "Game: {$npc->nama} - Jawaban benar (+{$npc->poin} poin)",
                    $npc
                );
            } catch (\Throwable $e) {
                Log::error('RPG point award failed: ' . $e->getMessage());
            }
        }

        // Check completion
        $totalNpcs = RpgNpc::where('rpg_map_id', $rpgMap->id)->where('is_active', true)->count();
        $answeredCount = count($session->answered_npcs ?? []);
        $isCompleted = $answeredCount >= $totalNpcs;

        if ($isCompleted && !$session->completed_at) {
            $session->completed_at = now();
        }

        $session->save();

        return response()->json([
            'success' => true,
            'correct' => $isCorrect,
            'jawaban_benar' => $correctAnswerIndex,
            'poin' => $isCorrect ? $npc->poin : 0,
            'total_score' => $session->total_score,
            'answered_count' => $answeredCount,
            'total_npcs' => $totalNpcs,
            'completed' => $isCompleted,
        ]);
    }

    /**
     * AJAX: Bos di mode Petualangan dikalahkan (peluru menghabiskan HP bos).
     * Beri bonus poin sekali per map per siswa (idempoten).
     */
    public function bossDefeat(Request $request, RpgMap $rpgMap)
    {
        if (! $rpgMap->boss_enabled) {
            return response()->json(['success' => false, 'message' => 'Map ini tidak memiliki bos.'], 422);
        }

        $siswa = Auth::guard('siswa')->user();
        $session = RpgGameSession::firstOrCreate(
            ['siswa_id' => $siswa->id, 'rpg_map_id' => $rpgMap->id],
            ['pos_x' => 0, 'pos_y' => 0, 'answered_npcs' => [], 'total_score' => 0]
        );

        // Idempoten: hanya beri bonus sekali.
        if ($session->boss_defeated_at) {
            return response()->json([
                'success' => true,
                'already' => true,
                'message' => 'Bos sudah pernah dikalahkan.',
                'total_score' => $session->total_score,
            ]);
        }

        $bossConfig = RpgCatalog::normalizeBossConfig($rpgMap->boss_config, (int) $rpgMap->grid_size);
        $bonus = (int) ($bossConfig['reward_points'] ?? 0);

        $session->boss_defeated_at = now();
        if ($bonus > 0) {
            $session->total_score += $bonus;
        }
        $session->save();

        if ($bonus > 0) {
            try {
                app(GamificationService::class)->awardGamePoints(
                    $siswa,
                    $bonus,
                    "Petualangan: Mengalahkan Bos \"{$bossConfig['nama']}\" di peta {$rpgMap->nama} (+{$bonus} poin)",
                    $rpgMap
                );
            } catch (\Throwable $e) {
                Log::error('RPG boss point award failed: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'already' => false,
            'bonus' => $bonus,
            'total_score' => $session->total_score,
            'message' => "Bos dikalahkan! +{$bonus} poin",
        ]);
    }

    /**
     * AJAX: Get game state (includes online players)
     */
    public function getGameState(RpgMap $rpgMap)
    {
        $siswa = Auth::guard('siswa')->user();
        $session = RpgGameSession::where('siswa_id', $siswa->id)
            ->where('rpg_map_id', $rpgMap->id)
            ->first();

        if (!$session) {
            return response()->json(['success' => false], 404);
        }

        // Touch session for presence
        $session->touch();

        // Get other online players (updated_at within last 15 seconds)
        // OPTIMASI: hindari N+1 — ambil semua RpgCharacter pemain online dalam 1 query.
        $onlineSessions = RpgGameSession::where('rpg_map_id', $rpgMap->id)
            ->where('siswa_id', '!=', $siswa->id)
            ->where('updated_at', '>=', now()->subSeconds(15))
            ->with(['siswa:id,nama'])
            ->get(['id', 'siswa_id', 'pos_x', 'pos_y']);

        $characters = RpgCharacter::whereIn('siswa_id', $onlineSessions->pluck('siswa_id'))
            ->get(['siswa_id', 'avatar', 'nama_karakter', 'warna'])
            ->keyBy('siswa_id');

        $onlinePlayers = $onlineSessions->map(function ($s) use ($characters) {
            $char = $characters->get($s->siswa_id);
            $avatar = $char->avatar ?? RpgCatalog::resolvePlayerAvatar(null);
            return [
                'siswa_id' => $s->siswa_id,
                'nama' => $char->nama_karakter ?? $s->siswa->nama ?? 'Player',
                'avatar' => $avatar,
                'avatar_display' => RpgCatalog::resolvePlayerAvatar($avatar),
                'warna' => $char->warna ?? '#3B82F6',
                'pos_x' => $s->pos_x,
                'pos_y' => $s->pos_y,
            ];
        });

        $presence = $this->getPresenceSummary($rpgMap, $siswa->id);

        return response()->json([
            'success' => true,
            'pos_x' => $session->pos_x,
            'pos_y' => $session->pos_y,
            'answered_npcs' => $session->answered_npcs ?? [],
            'total_score' => $session->total_score,
            'completed' => $session->completed_at !== null,
            'online_players' => $onlinePlayers,
            'active_players_count' => $presence['active_players_count'],
            'active_students_count' => $presence['active_students_count'],
            'active_guests_count' => $presence['active_guests_count'],
        ]);
    }

    /**
     * AJAX: Update character customization
     */
    public function updateCharacter(Request $request)
    {
        $request->validate([
            'avatar' => 'required|string|max:10',
            'nama_karakter' => 'nullable|string|max:50',
            'warna' => 'nullable|string|max:10',
        ]);

        $siswa = Auth::guard('siswa')->user();

        $character = RpgCharacter::updateOrCreate(
            ['siswa_id' => $siswa->id],
            [
                'avatar' => $request->avatar,
                'nama_karakter' => $request->nama_karakter ?? $siswa->nama,
                'warna' => $request->warna ?? '#3B82F6',
            ]
        );

        return response()->json(['success' => true, 'character' => $character]);
    }

    /**
     * AJAX: Heartbeat for online presence
     */
    public function heartbeat(Request $request)
    {
        $request->validate([
            'rpg_map_id' => 'required|integer|exists:rpg_maps,id',
        ]);

        $siswa = Auth::guard('siswa')->user();
        $session = RpgGameSession::where('siswa_id', $siswa->id)
            ->where('rpg_map_id', $request->rpg_map_id)
            ->first();

        if ($session) {
            $session->touch();
        }

        $presence = $this->getPresenceSummary(RpgMap::findOrFail($request->rpg_map_id), $siswa->id);

        return response()->json([
            'success' => true,
            'active_players_count' => $presence['active_players_count'],
            'active_students_count' => $presence['active_students_count'],
            'active_guests_count' => $presence['active_guests_count'],
        ]);
    }

    /**
     * AJAX: Reset game session (play again)
     */
    public function resetSession(RpgMap $rpgMap)
    {
        $siswa = Auth::guard('siswa')->user();
        $session = RpgGameSession::where('siswa_id', $siswa->id)
            ->where('rpg_map_id', $rpgMap->id)
            ->first();

        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Session tidak ditemukan'], 404);
        }

        // Deduct gamification points that were earned from this map
        $earnedScore = $session->total_score;
        if ($earnedScore > 0) {
            try {
                $gamificationService = app(GamificationService::class);
                $siswaPoint = $gamificationService->getOrCreateSiswaPoint($siswa);
                $siswaPoint->addPoints(
                    -$earnedScore,
                    'rpg',
                    "Reset game: {$rpgMap->nama} - Poin dikembalikan (-{$earnedScore})"
                );
            } catch (\Throwable $e) {
                Log::error('RPG reset point deduction failed: ' . $e->getMessage());
            }
        }

        $session->pos_x = 0;
        $session->pos_y = 0;
        $session->answered_npcs = [];
        $session->total_score = 0;
        $session->completed_at = null;
        $session->save();

        return response()->json(['success' => true, 'message' => 'Game direset. Poin game dikembalikan. Selamat bermain lagi.']);
    }

    // ============ ADMIN METHODS ============

    /**
     * Public: list active RPG maps for guest play.
     */
    public function publicIndex()
    {
        $theme = ThemeSetting::current();
        $maps = Cache::remember('public_rpg_active_maps', now()->addSeconds(self::PUBLIC_RPG_CACHE_SECONDS), function () {
            return RpgMap::where('is_active', true)
                ->withCount(['activeNpcs as npc_count'])
                ->orderBy('created_at', 'desc')
                ->get();
        });
        $totalChallenges = $maps->sum('npc_count');
        $karakterCount = \App\Models\KarakterLuhur::where('is_active', true)->count();

        return view('public.rpg', compact('maps', 'theme', 'totalChallenges', 'karakterCount'));
    }

    /**
     * Admin: Manage RPG maps
     */
    public function adminIndex()
    {
        $maps = RpgMap::with(['activeNpcs:id,rpg_map_id,nama,avatar,pos_x,pos_y,is_active'])
            ->withCount(['npcs', 'gameSessions'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get top players
        $topPlayers = RpgGameSession::select('siswa_id')
            ->selectRaw('SUM(total_score) as total')
            ->groupBy('siswa_id')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->with('siswa:id,nama,nis')
            ->get();

        $mapsIndex = $maps->map(function ($map) {
            $difficulty = $map->difficulty ?? 'easy';
            $shieldDuration = (int) ($map->shield_duration_seconds ?? 8);
            $ammoPerPickup = (int) ($map->ammo_per_pickup ?? 3);
            $shieldPickupCount = (int) ($map->shield_pickups_count ?? 1);
            $ammoPickupCount = (int) ($map->ammo_pickups_count ?? 2);

            $presetKey = 'custom';

            if (
                $difficulty === 'easy' &&
                $shieldDuration === 12 &&
                $ammoPerPickup === 4 &&
                $shieldPickupCount === 2 &&
                $ammoPickupCount === 4
            ) {
                $presetKey = 'relaxed';
            } elseif (
                $difficulty === 'medium' &&
                $shieldDuration === 8 &&
                $ammoPerPickup === 3 &&
                $shieldPickupCount === 1 &&
                $ammoPickupCount === 2
            ) {
                $presetKey = 'balanced';
            } elseif (
                $difficulty === 'hard' &&
                $shieldDuration === 6 &&
                $ammoPerPickup === 2 &&
                $shieldPickupCount === 1 &&
                $ammoPickupCount === 1
            ) {
                $presetKey = 'challenge';
            }

            return [
                'id' => $map->id,
                'nama' => $map->nama,
                'difficulty' => $difficulty,
                'is_active' => (bool) $map->is_active,
                'npcs_count' => (int) $map->npcs_count,
                'game_sessions_count' => (int) $map->game_sessions_count,
                'preset_key' => $presetKey,
            ];
        })->values();

        return view('admin.rpg.index', compact('maps', 'topPlayers', 'mapsIndex'));
    }

    /**
     * Public: play a map in guest/demo mode without storing progress.
     */
    public function publicPlay(RpgMap $rpgMap)
    {
        if (!$rpgMap->is_active) {
            abort(404);
        }

        $theme = ThemeSetting::current();
        $payload = Cache::remember("public_rpg_map_payload_{$rpgMap->id}", now()->addSeconds(self::PUBLIC_RPG_CACHE_SECONDS), function () use ($rpgMap) {
            return [
                'npcs' => $rpgMap->activeNpcs()->get(),
                'enemies' => RpgCatalog::normalizeEnemies($rpgMap->enemies),
                'obstacles' => $rpgMap->obstacles ?? [],
            ];
        });

        $npcs = $payload['npcs'];
        $enemies = $payload['enemies'];
        $obstacles = $payload['obstacles'];

        $presence = $this->getPresenceSummary($rpgMap);

        return view('public.rpg-play', compact(
            'theme',
            'rpgMap',
            'npcs',
            'enemies',
            'obstacles',
            'presence'
        ));
    }

    /**
     * Public: heartbeat guest presence for active player count.
     */
    public function publicPresence(Request $request, RpgMap $rpgMap)
    {
        if (! $rpgMap->is_active) {
            abort(404);
        }

        $validated = $request->validate([
            'guest_token' => ['required', 'string', 'max:120', 'regex:/^[A-Za-z0-9_-]+$/'],
        ]);

        $presence = $this->getPresenceSummary($rpgMap, null, $validated['guest_token']);

        return response()->json([
            'success' => true,
            'active_players_count' => $presence['active_players_count'],
            'active_students_count' => $presence['active_students_count'],
            'active_guests_count' => $presence['active_guests_count'],
        ]);
    }

    /**
     * Admin: Create map
     */
    public function adminStoreMap(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'grid_size' => 'integer|min:5|max:20',
            'background_theme' => 'string|max:50',
            'obstacles' => 'nullable|array',
            'enemies' => 'nullable|array',
            'boss_enabled' => 'nullable|boolean',
            'boss' => 'nullable|array',
            'difficulty' => 'nullable|string|in:easy,medium,hard',
            'shield_duration_seconds' => 'nullable|integer|min:1|max:60',
            'ammo_per_pickup' => 'nullable|integer|min:1|max:999',
            'shield_pickups_count' => 'nullable|integer|min:0|max:10',
            'ammo_pickups_count' => 'nullable|integer|min:0|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['grid_size'] = $validated['grid_size'] ?? 10;
        $validated['background_theme'] = $validated['background_theme'] ?? 'grass';
        $validated['difficulty'] = $validated['difficulty'] ?? 'easy';
        $validated['shield_duration_seconds'] = $validated['shield_duration_seconds'] ?? 8;
        $validated['ammo_per_pickup'] = $validated['ammo_per_pickup'] ?? 3;
        $validated['shield_pickups_count'] = $validated['shield_pickups_count'] ?? 1;
        $validated['ammo_pickups_count'] = $validated['ammo_pickups_count'] ?? 2;
        $validated['obstacles'] = $this->normalizeObstacles($request->input('obstacles', []));
        $validated['enemies'] = RpgCatalog::normalizeEnemies($request->input('enemies', []));
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['boss_enabled'] = $request->boolean('boss_enabled', false);
        $validated['boss_config'] = $validated['boss_enabled']
            ? RpgCatalog::normalizeBossConfig($request->input('boss', []), (int) $validated['grid_size'])
            : null;

        $map = RpgMap::create($validated);
        $this->forgetPublicRpgCache($map->id);

        return response()->json(['success' => true, 'message' => 'Map berhasil dibuat', 'data' => $map]);
    }

    /**
     * Admin: Update map
     */
    public function adminUpdateMap(Request $request, RpgMap $rpgMap)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'grid_size' => 'integer|min:5|max:20',
            'background_theme' => 'string|max:50',
            'obstacles' => 'nullable|array',
            'enemies' => 'nullable|array',
            'boss_enabled' => 'nullable|boolean',
            'boss' => 'nullable|array',
            'difficulty' => 'nullable|string|in:easy,medium,hard',
            'shield_duration_seconds' => 'nullable|integer|min:1|max:60',
            'ammo_per_pickup' => 'nullable|integer|min:1|max:999',
            'shield_pickups_count' => 'nullable|integer|min:0|max:10',
            'ammo_pickups_count' => 'nullable|integer|min:0|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['grid_size'] = $validated['grid_size'] ?? $rpgMap->grid_size;
        $validated['background_theme'] = $validated['background_theme'] ?? $rpgMap->background_theme;
        $validated['difficulty'] = $validated['difficulty'] ?? ($rpgMap->difficulty ?? 'easy');
        $validated['shield_duration_seconds'] = $validated['shield_duration_seconds'] ?? ($rpgMap->shield_duration_seconds ?? 8);
        $validated['ammo_per_pickup'] = $validated['ammo_per_pickup'] ?? ($rpgMap->ammo_per_pickup ?? 3);
        $validated['shield_pickups_count'] = $validated['shield_pickups_count'] ?? ($rpgMap->shield_pickups_count ?? 1);
        $validated['ammo_pickups_count'] = $validated['ammo_pickups_count'] ?? ($rpgMap->ammo_pickups_count ?? 2);
        $validated['obstacles'] = $this->normalizeObstacles($request->input('obstacles', []));
        $validated['enemies'] = RpgCatalog::normalizeEnemies($request->input('enemies', []));
        $validated['is_active'] = $request->boolean('is_active', $rpgMap->is_active);
        $validated['boss_enabled'] = $request->boolean('boss_enabled', false);
        $validated['boss_config'] = $validated['boss_enabled']
            ? RpgCatalog::normalizeBossConfig($request->input('boss', []), (int) $validated['grid_size'])
            : null;

        $rpgMap->update($validated);
        $this->forgetPublicRpgCache($rpgMap->id);

        return response()->json(['success' => true, 'message' => 'Map berhasil diupdate', 'data' => $rpgMap]);
    }

    /**
     * Admin: Duplicate map with NPCs and combat settings
     */
    public function adminDuplicateMap(RpgMap $rpgMap)
    {
        $copy = RpgMap::create([
            'nama' => $rpgMap->nama . ' Copy',
            'deskripsi' => $rpgMap->deskripsi,
            'grid_size' => $rpgMap->grid_size,
            'background_theme' => $rpgMap->background_theme,
            'obstacles' => $this->normalizeObstacles($rpgMap->obstacles),
            'enemies' => RpgCatalog::normalizeEnemies($rpgMap->enemies),
            'boss_enabled' => (bool) $rpgMap->boss_enabled,
            'boss_config' => $rpgMap->boss_enabled
                ? RpgCatalog::normalizeBossConfig($rpgMap->boss_config, (int) $rpgMap->grid_size)
                : null,
            'difficulty' => $rpgMap->difficulty ?? 'easy',
            'shield_duration_seconds' => $rpgMap->shield_duration_seconds ?? 8,
            'ammo_per_pickup' => $rpgMap->ammo_per_pickup ?? 3,
            'shield_pickups_count' => $rpgMap->shield_pickups_count ?? 1,
            'ammo_pickups_count' => $rpgMap->ammo_pickups_count ?? 2,
            'is_active' => false,
        ]);

        foreach ($rpgMap->npcs()->get() as $npc) {
            RpgNpc::create([
                'rpg_map_id' => $copy->id,
                'nama' => $npc->nama,
                'avatar' => RpgCatalog::resolveNpcAvatar($npc->avatar),
                'pos_x' => $npc->pos_x,
                'pos_y' => $npc->pos_y,
                'pertanyaan' => $npc->pertanyaan,
                'pilihan_jawaban' => $npc->pilihan_jawaban,
                'jawaban_benar' => $npc->jawaban_benar,
                'poin' => $npc->poin,
                'is_active' => $npc->is_active,
            ]);
        }
        $this->forgetPublicRpgCache($copy->id);

        return response()->json([
            'success' => true,
            'message' => 'Map berhasil diduplikat sebagai draft baru.',
            'data' => $copy,
        ]);
    }

    /**
     * Admin: Delete map
     */
    public function adminDeleteMap(RpgMap $rpgMap)
    {
        $mapId = $rpgMap->id;
        $rpgMap->delete();
        $this->forgetPublicRpgCache($mapId);
        return response()->json(['success' => true, 'message' => 'Map berhasil dihapus']);
    }

    /**
     * Admin: Get map detail with NPCs (AJAX)
     */
    public function adminGetMap(RpgMap $rpgMap)
    {
        $rpgMap->load('npcs');
        $rpgMap->setAttribute('obstacles', $this->normalizeObstacles($rpgMap->obstacles));
        $rpgMap->setAttribute('enemies', RpgCatalog::normalizeEnemies($rpgMap->enemies));
        return response()->json(['success' => true, 'data' => $rpgMap]);
    }

    /**
     * Admin: Store NPC
     */
    public function adminStoreNpc(Request $request)
    {
        $validated = $request->validate([
            'rpg_map_id' => 'required|exists:rpg_maps,id',
            'nama' => 'required|string|max:255',
            'avatar' => 'nullable|string|max:50',
            'pos_x' => 'required|integer|min:0',
            'pos_y' => 'required|integer|min:0',
            'pertanyaan' => 'required|string',
            'pilihan_jawaban' => 'required|array|min:2|max:4',
            'pilihan_jawaban.*' => 'required|string',
            'jawaban_benar' => 'required|integer|min:0|max:3',
            'poin' => 'integer|min:1',
        ]);

        $validated['avatar'] = $validated['avatar'] ?? '🧙';
        $validated['avatar'] = RpgCatalog::resolveNpcAvatar($validated['avatar'] ?? null);
        $validated['poin'] = $validated['poin'] ?? 10;

        $npc = RpgNpc::create($validated);
        $this->forgetPublicRpgCache((int) $npc->rpg_map_id);

        return response()->json(['success' => true, 'message' => 'NPC berhasil ditambahkan', 'data' => $npc]);
    }

    /**
     * Admin: Update NPC
     */
    public function adminUpdateNpc(Request $request, RpgNpc $rpgNpc)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'avatar' => 'nullable|string|max:50',
            'pos_x' => 'required|integer|min:0',
            'pos_y' => 'required|integer|min:0',
            'pertanyaan' => 'required|string',
            'pilihan_jawaban' => 'required|array|min:2|max:4',
            'pilihan_jawaban.*' => 'required|string',
            'jawaban_benar' => 'required|integer|min:0|max:3',
            'poin' => 'integer|min:1',
            'is_active' => 'boolean',
        ]);

        $validated['avatar'] = RpgCatalog::resolveNpcAvatar($validated['avatar'] ?? null);

        $rpgNpc->update($validated);
        $this->forgetPublicRpgCache((int) $rpgNpc->rpg_map_id);

        return response()->json(['success' => true, 'message' => 'NPC berhasil diupdate', 'data' => $rpgNpc]);
    }

    /**
     * Admin: Delete NPC
     */
    public function adminDeleteNpc(RpgNpc $rpgNpc)
    {
        $mapId = (int) $rpgNpc->rpg_map_id;
        $rpgNpc->delete();
        $this->forgetPublicRpgCache($mapId);
        return response()->json(['success' => true, 'message' => 'NPC berhasil dihapus']);
    }

    protected function forgetPublicRpgCache(?int $mapId = null): void
    {
        Cache::forget('public_rpg_active_maps');

        if ($mapId) {
            Cache::forget("public_rpg_map_payload_{$mapId}");
        }
    }

    protected function normalizeObstacles($obstacles): array
    {
        return collect($obstacles ?? [])
            ->map(function ($obstacle) {
                return [
                    'x' => (int) ($obstacle['x'] ?? 0),
                    'y' => (int) ($obstacle['y'] ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    protected function getPresenceSummary(RpgMap $rpgMap, ?int $siswaId = null, ?string $guestToken = null): array
    {
        $activeStudents = RpgGameSession::where('rpg_map_id', $rpgMap->id)
            ->where('updated_at', '>=', now()->subSeconds(self::RPG_PRESENCE_WINDOW_SECONDS))
            ->count();

        if ($siswaId) {
            $hasCurrentStudent = RpgGameSession::where('rpg_map_id', $rpgMap->id)
                ->where('siswa_id', $siswaId)
                ->where('updated_at', '>=', now()->subSeconds(self::RPG_PRESENCE_WINDOW_SECONDS))
                ->exists();

            if (!$hasCurrentStudent) {
                $activeStudents++;
            }
        }

        $activeGuests = $this->syncGuestPresence($rpgMap->id, $guestToken);

        return [
            'active_players_count' => $activeStudents + $activeGuests,
            'active_students_count' => $activeStudents,
            'active_guests_count' => $activeGuests,
        ];
    }

    protected function syncGuestPresence(int $mapId, ?string $guestToken = null): int
    {
        $cacheKey = "rpg_guest_presence_map_{$mapId}";
        $now = now()->timestamp;
        $cutoff = $now - self::RPG_PRESENCE_WINDOW_SECONDS;

        $presence = Cache::get($cacheKey, []);
        if (!is_array($presence)) {
            $presence = [];
        }

        $presence = array_filter($presence, static function ($timestamp) use ($cutoff) {
            return is_numeric($timestamp) && (int) $timestamp >= $cutoff;
        });

        if ($guestToken) {
            $presence[hash('sha256', $guestToken)] = $now;
        }

        if (count($presence) > 100) {
            arsort($presence);
            $presence = array_slice($presence, 0, 100, true);
        }

        Cache::put($cacheKey, $presence, now()->addMinutes(5));

        return count($presence);
    }

    protected function correctAnswerIndex(RpgNpc $npc): int
    {
        $choices = is_array($npc->pilihan_jawaban) ? $npc->pilihan_jawaban : [];
        $raw = (int) $npc->jawaban_benar;
        $count = count($choices);

        if ($raw >= 0 && $raw < $count) {
            return $raw;
        }

        if ($raw >= 1 && $raw <= $count) {
            return $raw - 1;
        }

        return $raw;
    }

    protected function isCorrectAnswer(int $selectedIndex, RpgNpc $npc): bool
    {
        $choices = is_array($npc->pilihan_jawaban) ? $npc->pilihan_jawaban : [];
        $raw = (int) $npc->jawaban_benar;

        if ($selectedIndex === $this->correctAnswerIndex($npc)) {
            return true;
        }

        return $raw >= 1 && $raw <= count($choices) && $selectedIndex === $raw - 1;
    }
}
