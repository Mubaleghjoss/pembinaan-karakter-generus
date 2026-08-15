<?php

namespace App\Http\Controllers;

use App\Models\Karakter;
use App\Models\SiswaKarakterChecklist;
use App\Services\GamificationService;
use App\Services\TaskProofAudioService;
use App\Services\TaskProofImageService;
use App\Services\TaskPwaNotificationService;
use App\Support\InvalidKarakterChecklistCleaner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SiswaKarakterController extends Controller
{
    public function __construct(
        protected TaskProofImageService $taskProofImageService,
        protected TaskProofAudioService $taskProofAudioService
    ) {
    }

    /**
     * Display a listing of karakter for siswa to check.
     */
    public function index(Request $request)
    {
        $siswa = Auth::guard('siswa')->user();
        Karakter::deactivateExpiredTasks();
        
        // Selected date (default: today, max 7 days back)
        $selectedDate = $request->get('date', now()->toDateString());
        $minDate = now()->subDays(6)->toDateString();
        $maxDate = now()->toDateString();
        
        // Clamp selectedDate within allowed range
        if ($selectedDate > $maxDate) $selectedDate = $maxDate;
        if ($selectedDate < $minDate) $selectedDate = $minDate;

        app(InvalidKarakterChecklistCleaner::class)->cleanupForSiswa($siswa->id);
        
        // Get active karakter available for the selected date.
        $karakterList = Karakter::active()
            ->availableOn($selectedDate)
            ->orderBy('nama')
            ->get();
        $grouped = $karakterList->groupBy('kategori');

        $taskTotalsByCategory = $karakterList
            ->groupBy('kategori')
            ->map(fn ($tasks) => $tasks->count());
        
        // Get siswa's checked karakter for the SELECTED DATE
        $checkedKarakter = SiswaKarakterChecklist::where('siswa_id', $siswa->id)
            ->whereDate('checked_at', $selectedDate)
            ->with(['karakter', 'verifier'])
            ->orderBy('checked_at', 'desc')
            ->get()
            ->groupBy('karakter_id');

        $categorySummary = SiswaKarakterChecklist::query()
            ->join('karakter', 'siswa_karakter_checklist.karakter_id', '=', 'karakter.id')
            ->where('siswa_karakter_checklist.siswa_id', $siswa->id)
            ->whereDate('siswa_karakter_checklist.checked_at', $selectedDate)
            ->where(function ($query) use ($selectedDate) {
                $query->whereNull('karakter.tanggal_mulai')
                    ->orWhereDate('karakter.tanggal_mulai', '<=', $selectedDate);
            })
            ->where(function ($query) use ($selectedDate) {
                $query->whereNull('karakter.tanggal_selesai')
                    ->orWhereDate('karakter.tanggal_selesai', '>=', $selectedDate);
            })
            ->select(
                'karakter.kategori',
                DB::raw('COUNT(DISTINCT siswa_karakter_checklist.karakter_id) as completed'),
                DB::raw('COUNT(DISTINCT CASE WHEN siswa_karakter_checklist.verified_at IS NOT NULL THEN siswa_karakter_checklist.karakter_id END) as verified')
            )
            ->groupBy('karakter.kategori')
            ->get()
            ->keyBy('kategori');
        
        // Get UNVERIFIED tasks from previous days (still pending verification)
        $pendingVerification = SiswaKarakterChecklist::where('siswa_id', $siswa->id)
            ->whereDate('checked_at', '<', now()->toDateString())
            ->whereNull('verified_at')
            ->with(['karakter'])
            ->orderBy('checked_at', 'desc')
            ->get();

        // Calculate progress per category (for selected date)
        $categoryProgress = [];
        foreach (['harian', 'mingguan', 'bulanan'] as $kat) {
            $summary = $categorySummary->get($kat);
            $categoryProgress[$kat] = [
                'total' => $taskTotalsByCategory->get($kat, 0),
                'completed' => (int) ($summary->completed ?? 0),
                'verified' => (int) ($summary->verified ?? 0),
            ];
        }

        // Build date list for navigation (today + 6 days back)
        $dateList = [];
        for ($i = 0; $i <= 6; $i++) {
            $d = now()->subDays($i);
            $dateList[] = [
                'date' => $d->toDateString(),
                'day_name' => $i === 0 ? 'Hari Ini' : ($i === 1 ? 'Kemarin' : $d->translatedFormat('D')),
                'day_num' => $d->format('d'),
                'month' => $d->translatedFormat('M'),
                'is_today' => $i === 0,
                'is_selected' => $d->toDateString() === $selectedDate,
            ];
        }
        
        return view('siswa.tugas-pkg.index', compact(
            'karakterList', 'checkedKarakter', 'grouped', 'categoryProgress', 
            'pendingVerification', 'selectedDate', 'dateList'
        ));
    }

    /**
     * Check/toggle a karakter for the authenticated siswa.
     */
    public function toggle(Request $request, Karakter $karakter)
    {
        $siswa = Auth::guard('siswa')->user();

        abort_if(
            $siswa->isGraduated() && ! $siswa->canSubmitAsAlumni(),
            403,
            'Pengiriman tugas Alumni sedang dinonaktifkan oleh Admin.'
        );

        $request->validate([
            'student_note' => 'nullable|string|max:1000',
            'hasil_teks' => 'nullable|string|max:5000',
            'proof_image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:20480',
            'proof_voice_note' => 'nullable|file|mimes:mp3,wav,ogg,oga,m4a,aac,webm,mp4|max:25600',
            'proof_voice_note_duration_seconds' => 'nullable|integer|min:1|max:3600',
        ], [
            'proof_image.max' => 'Foto awal terlalu besar untuk diproses. Ambil ulang dengan resolusi lebih ringan atau crop dulu, lalu coba lagi.',
            'proof_image.image' => 'File bukti foto harus berupa gambar yang valid.',
            'proof_image.mimes' => 'Bukti foto hanya mendukung format JPG, PNG, atau WebP.',
            'proof_voice_note.max' => 'Voice note terlalu besar untuk dikirim langsung. Rekam ulang dengan durasi lebih singkat atau gunakan tombol rekam langsung di perangkat.',
            'proof_voice_note.mimes' => 'Voice note hanya mendukung format MP3, WAV, OGG, M4A, AAC, WEBM, atau MP4 audio.',
        ]);
        
        // Determine the target date (default: today, max 7 days back)
        $forDate = $request->input('for_date', now()->toDateString());
        $minDate = now()->subDays(6)->toDateString();
        $maxDate = now()->toDateString();
        
        // Validate date range
        if ($forDate > $maxDate || $forDate < $minDate) {
            return redirect()->route('siswa.tugas-pkg.index', ['date' => now()->toDateString()])
                ->with('error', 'Tanggal tidak valid. Maksimal 7 hari ke belakang.');
        }

        if (! $karakter->isAvailableOn($forDate)) {
            return redirect()->route('siswa.tugas-pkg.index', ['date' => $forDate])
                ->with('error', 'Tanggal yang dipilih berada di luar periode tugas' . ($karakter->formatted_period ? ': ' . $karakter->formatted_period : '.'));
        }

        // Block expired tasks
        if ($karakter->isExpired()) {
            return redirect()->route('siswa.tugas-pkg.index', ['date' => $forDate])
                ->with('error', 'Waktu pengerjaan tugas ini sudah selesai.');
        }

        // Block tasks that haven't started yet
        if (!$karakter->isAvailable()) {
            return redirect()->route('siswa.tugas-pkg.index', ['date' => $forDate])
                ->with('error', 'Tugas ini belum tersedia.');
        }
        
        // Check if already checked on the target date
        $existingCheck = SiswaKarakterChecklist::where('siswa_id', $siswa->id)
            ->where('karakter_id', $karakter->id)
            ->whereDate('checked_at', $forDate)
            ->first();
        
        if ($existingCheck) {
            if (!$existingCheck->isVerified()) {
                return redirect()->route('siswa.tugas-pkg.index', ['date' => $forDate])
                    ->with('success', $siswa->isGraduated()
                        ? 'Tugas ini sudah tercatat dan sedang menunggu verifikasi Admin.'
                        : 'Tugas ini sudah tercatat dan sedang menunggu verifikasi Pamong.');
            }

            return redirect()->route('siswa.tugas-pkg.index', ['date' => $forDate])
                ->with('error', 'Tugas yang sudah diverifikasi tidak dapat dibatalkan.');
        } else {
            // Create new check with the target date (use date + current time)
            $checkedAt = \Carbon\Carbon::parse($forDate)->setTimeFrom(now());
            
            // Parse click_history if provided
            $clickHistory = null;
            if ($request->filled('click_history')) {
                $clickHistory = is_string($request->input('click_history')) 
                    ? json_decode($request->input('click_history'), true) 
                    : $request->input('click_history');
            }

            $proofData = [];
            if ($karakter->allows_photo_proof && $request->hasFile('proof_image')) {
                try {
                    $proofData = $this->taskProofImageService->storeProof(
                        $request->file('proof_image'),
                        $siswa->id,
                        $karakter->id
                    );
                } catch (\RuntimeException $e) {
                    return redirect()->route('siswa.tugas-pkg.index', ['date' => $forDate])
                        ->with('error', $e->getMessage());
                }
            }

            $voiceNoteData = [];
            if ($karakter->allows_voice_note_proof && $request->hasFile('proof_voice_note')) {
                $voiceNoteData = $this->taskProofAudioService->storeVoiceNote(
                    $request->file('proof_voice_note'),
                    $siswa->id,
                    $karakter->id
                );
            }

            $photoUploaded = $karakter->allows_photo_proof && !empty($proofData['path']);
            $voiceUploaded = $karakter->allows_voice_note_proof && !empty($voiceNoteData['path']);

            if (($karakter->proof_requirement ?? 'optional') === 'required_any' && ! $photoUploaded && ! $voiceUploaded) {
                return redirect()->route('siswa.tugas-pkg.index', ['date' => $forDate])
                    ->with('error', 'Tugas ini mewajibkan minimal satu bukti berupa foto atau voice note.');
            }

            $voiceDuration = $voiceUploaded ? (int) $request->input('proof_voice_note_duration_seconds', 0) : null;
            $maxVoiceSeconds = (int) ($karakter->voice_note_max_seconds ?? 0);

            if ($voiceUploaded && $maxVoiceSeconds > 0 && ! $voiceDuration) {
                return redirect()->route('siswa.tugas-pkg.index', ['date' => $forDate])
                    ->with('error', 'Durasi voice note tidak dapat dibaca. Gunakan file audio yang valid lalu coba lagi.');
            }

            if ($voiceUploaded && $maxVoiceSeconds > 0 && $voiceDuration && $voiceDuration > $maxVoiceSeconds) {
                return redirect()->route('siswa.tugas-pkg.index', ['date' => $forDate])
                    ->with('error', 'Durasi voice note melebihi batas tugas ini, maksimal ' . $maxVoiceSeconds . ' detik.');
            }

            $checklist = SiswaKarakterChecklist::create([
                'siswa_id' => $siswa->id,
                'karakter_id' => $karakter->id,
                'checked_at' => $checkedAt,
                'student_note' => $request->input('student_note'),
                'hasil_teks' => $request->input('hasil_teks'),
                'click_history' => $clickHistory,
                'proof_path' => $proofData['path'] ?? null,
                'proof_original_size_kb' => $proofData['original_size_kb'] ?? null,
                'proof_compressed_size_kb' => $proofData['compressed_size_kb'] ?? null,
                'voice_note_path' => $voiceNoteData['path'] ?? null,
                'voice_note_size_kb' => $voiceNoteData['size_kb'] ?? null,
                'voice_note_duration_seconds' => $voiceDuration,
            ]);

            app(TaskPwaNotificationService::class)->notifyPamongAboutSubmission($checklist);
            
            $dateLabel = $forDate === now()->toDateString() ? 'hari ini' : \Carbon\Carbon::parse($forDate)->translatedFormat('d M Y');
            $photoBonus = ($karakter->allows_photo_proof && !empty($proofData['path']))
                ? (int) ($karakter->photo_proof_bonus_points ?? 0)
                : 0;
            $voiceBonus = ($karakter->allows_voice_note_proof && !empty($voiceNoteData['path']))
                ? (int) ($karakter->voice_note_bonus_points ?? 0)
                : 0;
            $proofBonus = $photoBonus + $voiceBonus;
            $pointMessage = "(+" . $karakter->poin . ' poin setelah verifikasi)';

            if ($proofBonus > 0) {
                $pointMessage = "(+" . $karakter->poin . ' poin + bonus bukti +' . $proofBonus . ' poin setelah verifikasi)';
            }

            return redirect()->route('siswa.tugas-pkg.index', ['date' => $forDate])
                ->with('success', "Tugas berhasil dicatat untuk {$dateLabel}! Menunggu verifikasi dari "
                    .($siswa->isGraduated() ? 'Admin' : 'Pamong').". {$pointMessage}");
        }
    }

    /**
     * Display history of checked karakter.
     */
    public function history()
    {
        $siswa = Auth::guard('siswa')->user();
        
        $history = SiswaKarakterChecklist::where('siswa_id', $siswa->id)
            ->with(['karakter', 'verifier'])
            ->orderBy('checked_at', 'desc')
            ->paginate(20);
        
        // Group by month for better display
        $historyByMonth = $history->getCollection()->groupBy(function($item) {
            return $item->checked_at->format('Y-m');
        });
        
        return view('siswa.tugas-pkg.history', compact('history', 'historyByMonth'));
    }

    /**
     * Display verification interface for pamong/admin.
     * This combines both self-assessment (siswa) and pamong assessment.
     */
    public function verificationIndex(Request $request)
    {
        $user = Auth::user();
        $assignedSiswaIds = null;
        
        // Build query for both TracerKarakter and SiswaKarakterChecklist
        $query = SiswaKarakterChecklist::with(['siswa', 'karakter', 'verifier'])
            ->orderBy('checked_at', 'desc');
        
        // Filter by pamong access (if teacher role)
        if ($user->isTeacher()) {
            $assignedSiswaIds = $user->getAssignedSiswaIds();
            $query->whereIn('siswa_id', $assignedSiswaIds);
        }
        
        // Filter by verification status (default to 'unverified' to show pending tasks first)
        $status = $request->input('status', 'unverified');
        if ($status === 'verified') {
            $query->verified();
        } elseif ($status === 'unverified') {
            $query->unverified();
        }
        // status === 'all' or empty string = no filter (show all)
        
        // Filter by siswa
        if ($request->has('siswa_id') && $request->siswa_id) {
            $query->where('siswa_id', $request->siswa_id);
        }
        
        // Filter by karakter
        if ($request->has('karakter_id') && $request->karakter_id) {
            $query->where('karakter_id', $request->karakter_id);
        }

        // Filter by proof status
        $proofStatus = $request->input('proof_status');
        if ($proofStatus === 'valid') {
            $query->where(function ($q) {
                $q->whereNotNull('proof_path')
                    ->orWhere(function ($voiceQuery) {
                        $voiceQuery->whereNotNull('voice_note_path')
                            ->where(function ($voiceConstraint) {
                                $voiceConstraint->whereHas('karakter', function ($karakterQuery) {
                                    $karakterQuery->whereNull('voice_note_max_seconds')
                                        ->orWhere('voice_note_max_seconds', 0);
                                })->orWhereHas('karakter', function ($karakterQuery) {
                                    $karakterQuery->whereColumn('siswa_karakter_checklist.voice_note_duration_seconds', '<=', 'karakter.voice_note_max_seconds');
                                });
                            });
                    });
            });
        } elseif ($proofStatus === 'no_proof') {
            $query->whereNull('proof_path')->whereNull('voice_note_path');
        } elseif ($proofStatus === 'required_missing') {
            $query->whereHas('karakter', function ($karakterQuery) {
                $karakterQuery->where('proof_requirement', 'required_any');
            })
                ->whereNull('proof_path')
                ->whereNull('voice_note_path');
        } elseif ($proofStatus === 'voice_too_long') {
            $query->whereNotNull('voice_note_path')
                ->whereHas('karakter', function ($karakterQuery) {
                    $karakterQuery->whereNotNull('voice_note_max_seconds')
                        ->where('voice_note_max_seconds', '>', 0)
                        ->whereColumn('siswa_karakter_checklist.voice_note_duration_seconds', '>', 'karakter.voice_note_max_seconds');
                });
        }
        
        // Filter by kelas
        if ($request->has('kelas_id') && $request->kelas_id) {
            $query->whereHas('siswa', function($q) use ($request) {
                $q->where('kelas_id', $request->kelas_id);
            });
        }
        
        // Filter by date range
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('checked_at', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('checked_at', '<=', $request->date_to);
        }
        
        $checklists = $query->paginate(20);
        
        $statsQuery = SiswaKarakterChecklist::query();
        if ($user->isTeacher()) {
            $statsQuery->whereIn('siswa_id', $assignedSiswaIds ?? []);
        }

        $statsSummary = $statsQuery
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN verified_at IS NOT NULL THEN 1 ELSE 0 END) as verified')
            ->selectRaw('SUM(CASE WHEN verified_at IS NULL THEN 1 ELSE 0 END) as unverified')
            ->first();

        $stats = [
            'total' => (int) ($statsSummary->total ?? 0),
            'verified' => (int) ($statsSummary->verified ?? 0),
            'unverified' => (int) ($statsSummary->unverified ?? 0),
        ];
        
        // Get filter options
        $kelasOptions = \App\Models\Kelas::where('is_active', true)->orderBy('nama')->get();
        $siswaOptions = $user->isTeacher() 
            ? \App\Models\Siswa::whereIn('id', $assignedSiswaIds ?? [])->orderBy('nama')->get()
            : \App\Models\Siswa::active()->orderBy('nama')->get();
        
        return view('tugas-pkg.verification.index', compact('checklists', 'stats', 'kelasOptions', 'siswaOptions'));
    }

    /**
     * Show form to add karakter check for a student (pamong adds for siswa).
     */
    public function checkKarakter(\App\Models\Siswa $siswa)
    {
        $user = Auth::user();
        
        // Verify pamong access control
        if ($user->isTeacher() && !$user->isAssignedTo($siswa)) {
            abort(403, 'Anda tidak memiliki akses ke siswa ini.');
        }
        
        // Get active karakter available today.
        $karakterList = Karakter::active()
            ->availableOn(today())
            ->get();
        
        // Get today's checked karakter for this student
        $todayChecked = SiswaKarakterChecklist::where('siswa_id', $siswa->id)
            ->whereDate('checked_at', today())
            ->pluck('karakter_id')
            ->toArray();
        
        return view('tugas-pkg.verification.check', compact('siswa', 'karakterList', 'todayChecked'));
    }

    /**
     * Store karakter check (pamong adds for siswa with auto-verification).
     */
    public function storeCheck(Request $request, \App\Models\Siswa $siswa)
    {
        $user = Auth::user();
        
        // Verify pamong access control
        if ($user->isTeacher() && !$user->isAssignedTo($siswa)) {
            abort(403, 'Anda tidak memiliki akses ke siswa ini.');
        }
        
        $request->validate([
            'karakter_ids' => 'required|array|min:1',
            'karakter_ids.*' => 'exists:karakter,id',
            'notes' => 'nullable|string|max:1000',
        ]);
        
        $checkedAt = now();
        $availableKarakter = Karakter::active()
            ->availableOn($checkedAt)
            ->whereIn('id', $request->karakter_ids)
            ->pluck('id')
            ->all();

        $invalidKarakterIds = array_diff($request->karakter_ids, $availableKarakter);

        if (! empty($invalidKarakterIds)) {
            return back()->with('error', 'Ada tugas yang berada di luar periode aktif, sehingga tidak bisa dicatat.');
        }
        
        foreach ($request->karakter_ids as $karakterId) {
            // Create with auto-verification since pamong is adding it
            SiswaKarakterChecklist::create([
                'siswa_id' => $siswa->id,
                'karakter_id' => $karakterId,
                'checked_at' => $checkedAt,
                'verified_by' => $user->id,
                'verified_at' => $checkedAt,
                'notes' => $request->notes,
            ]);
        }
        
        return redirect()->route('tugas-pkg.verification')
            ->with('success', 'Karakter berhasil dicatat dan diverifikasi untuk ' . $siswa->nama);
    }

    /**
     * Verify a karakter checklist.
     */
    public function verify(Request $request, SiswaKarakterChecklist $checklist)
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);
        
        $checklist->update([
            'verified_by' => Auth::id(),
            'verified_at' => now(),
            'notes' => $request->notes,
        ]);

        // Award configurable points from the karakter
        $siswa = $checklist->siswa;
        $karakter = $checklist->karakter;
        if ($siswa && $karakter) {
            try {
                $gamificationService = app(GamificationService::class);
                $siswaPoint = $gamificationService->getOrCreateSiswaPoint($siswa);
                $pointBreakdown = $this->getChecklistPointBreakdown($checklist);
                $siswaPoint->addPoints(
                    $pointBreakdown['total'],
                    'character',
                    'Verifikasi tugas PKG: ' . $karakter->nama . ' (+' . $pointBreakdown['total'] . ' poin' . ($pointBreakdown['proof_bonus'] > 0 ? ', termasuk bonus bukti +' . $pointBreakdown['proof_bonus'] : '') . ')',
                    $checklist,
                    $this->buildChecklistPointMetadata($checklist)
                );

                // Check category completion bonus
                $this->checkCategoryCompletionBonus($siswa, $karakter, $gamificationService);

                // Check badge eligibility
                $gamificationService->checkBadgeEligibility($siswa);
            } catch (\Exception $e) {
                // Don't fail verification if gamification fails
                \Log::warning('Gamification error on verify: ' . $e->getMessage());
            }
        }

        // Log activity for pamong
    $user = Auth::user();
    if ($user && $user->usesPamongPermissionSystem()) {
        \App\Models\PamongActivityLog::log(
            userId: $user->id,
            action: 'verify',
            description: 'Memverifikasi tugas PKG: ' . ($karakter->nama ?? 'karakter') . ' untuk siswa ' . ($siswa->nama ?? ''),
            module: 'tracer_karakter',
            metadata: ['checklist_id' => $checklist->id, 'siswa_id' => $siswa->id ?? null, 'karakter' => $karakter->nama ?? null],
            ipAddress: $request->ip()
        );
    }
    
    return redirect()->route('tugas-pkg.verification', ['tab' => 'verification'])->with('success', 'Tugas berhasil diverifikasi. +' . $this->getChecklistAwardedPoints($checklist) . ' poin diberikan. Bukti unggahan tetap disimpan.');
    }

    /**
     * Unverify a karakter checklist — reverse the awarded points.
     */
    public function unverify(Request $request, SiswaKarakterChecklist $checklist)
    {
        $request->validate([
            'reason' => 'required|string|min:5|max:500',
        ]);

        $reason = $request->reason;
        $actor = Auth::user()->username ?? Auth::user()->name ?? 'Admin';

        // Reverse points if was verified
        if ($checklist->isVerified()) {
            $siswa = $checklist->siswa;
            $karakter = $checklist->karakter;
            $pointsToReverse = $this->getChecklistAwardedPoints($checklist);

            if ($siswa && $karakter) {
                try {
                    $gamificationService = app(GamificationService::class);
                    $siswaPoint = $gamificationService->getOrCreateSiswaPoint($siswa);
                    $siswaPoint->addPoints(
                        -$pointsToReverse,
                        'character',
                        'Batal verifikasi: ' . $karakter->nama . ' (-' . $pointsToReverse . ' poin). Alasan: ' . $reason . ' (oleh ' . $actor . ')',
                        $checklist,
                        $this->buildChecklistPointMetadata($checklist)
                    );
                } catch (\Exception $e) {
                    \Log::warning('Gamification error on unverify: ' . $e->getMessage());
                }
            }
        }

        $checklist->update([
            'verified_by' => null,
            'verified_at' => null,
            'notes' => null,
        ]);
        $checklist->clearStoredEvidenceFiles();
        
        // Log activity for pamong
    $user = Auth::user();
    if ($user && $user->usesPamongPermissionSystem()) {
        \App\Models\PamongActivityLog::log(
            userId: $user->id,
            action: 'verify',
            description: 'Membatalkan verifikasi tugas PKG: ' . ($checklist->karakter->nama ?? 'karakter') . '. Alasan: ' . $reason,
            module: 'tracer_karakter',
            metadata: ['checklist_id' => $checklist->id, 'reason' => $reason],
            ipAddress: $request->ip()
        );
    }
    
    return redirect()->route('tugas-pkg.verification', ['tab' => 'verification'])->with('success', 'Verifikasi ditolak dan poin dikurangi. Bukti unggahan sudah dibersihkan.');
    }

    /**
     * Delete a karakter checklist — reverse points if was verified.
     */
    public function destroy(Request $request, SiswaKarakterChecklist $checklist)
    {
        $request->validate([
            'reason' => 'required|string|min:5|max:500',
        ]);

        $reason = $request->reason;
        $actor = Auth::user()->username ?? Auth::user()->name ?? 'Admin';

        // Reverse points if the checklist was verified
        if ($checklist->isVerified()) {
            $siswa = $checklist->siswa;
            $karakter = $checklist->karakter;
            $pointsToReverse = $this->getChecklistAwardedPoints($checklist);

            if ($siswa && $karakter) {
                try {
                    $gamificationService = app(GamificationService::class);
                    $siswaPoint = $gamificationService->getOrCreateSiswaPoint($siswa);
                    $siswaPoint->addPoints(
                        -$pointsToReverse,
                        'character',
                        'Hapus data: ' . $karakter->nama . ' (-' . $pointsToReverse . ' poin). Alasan: ' . $reason . ' (oleh ' . $actor . ')',
                        $checklist,
                        $this->buildChecklistPointMetadata($checklist)
                    );
                } catch (\Exception $e) {
                    \Log::warning('Gamification error on destroy: ' . $e->getMessage());
                }
            }
        }

        $karakterName = $checklist->karakter->nama ?? 'karakter';
        $siswaName = $checklist->siswa->nama ?? '';

        // Soft-delete: store who deleted and why, then soft-delete
        $checklist->update([
            'deleted_by' => Auth::id(),
            'deleted_reason' => $reason,
        ]);
        $checklist->clearStoredEvidenceFiles();
        $checklist->delete();

        // Log activity for pamong
        $user = Auth::user();
        if ($user && $user->usesPamongPermissionSystem()) {
            \App\Models\PamongActivityLog::log(
                userId: $user->id,
                action: 'delete',
                description: 'Menghapus data tugas PKG: ' . $karakterName . ' untuk siswa ' . $siswaName . '. Alasan: ' . $reason,
                module: 'tracer_karakter',
                metadata: ['reason' => $reason, 'karakter' => $karakterName, 'siswa' => $siswaName],
                ipAddress: $request->ip()
            );
        }

        return redirect()->route('tugas-pkg.verification', ['tab' => 'verification'])->with('success', 'Tugas ditolak dan data dipindahkan ke arsip. Bukti unggahan sudah dibersihkan.');
    }

    /**
     * Restore a soft-deleted karakter checklist and re-award points if was verified.
     */
    public function restore(Request $request, $id)
    {
        $checklist = SiswaKarakterChecklist::onlyTrashed()->findOrFail($id);

        DB::beginTransaction();
        try {
            $checklist->restore();
            $checklist->update([
                'deleted_by' => null,
                'deleted_reason' => null,
            ]);

            // Re-award points if the record was verified
            if ($checklist->isVerified()) {
                $siswa = $checklist->siswa;
                $karakter = $checklist->karakter;
                $points = $this->getChecklistAwardedPoints($checklist);

                if ($siswa && $karakter) {
                    try {
                        $gamificationService = app(GamificationService::class);
                        $siswaPoint = $gamificationService->getOrCreateSiswaPoint($siswa);
                        $actor = Auth::user()->username ?? Auth::user()->name ?? 'Admin';
                        $siswaPoint->addPoints(
                            $points,
                            'character',
                            'Restore data: ' . $karakter->nama . ' (+' . $points . ' poin) oleh ' . $actor,
                            $checklist,
                            $this->buildChecklistPointMetadata($checklist)
                        );
                    } catch (\Exception $e) {
                        \Log::warning('Gamification error on restore: ' . $e->getMessage());
                    }
                }
            }

            DB::commit();

            // Log activity
            $user = Auth::user();
            if ($user && $user->usesPamongPermissionSystem()) {
                \App\Models\PamongActivityLog::log(
                    userId: $user->id,
                    action: 'restore',
                    description: 'Restore data tugas PKG: ' . ($checklist->karakter->nama ?? 'karakter') . ' untuk siswa ' . ($checklist->siswa->nama ?? ''),
                    module: 'tracer_karakter',
                    metadata: ['checklist_id' => $checklist->id],
                    ipAddress: $request->ip()
                );
            }

            return back()->with('success', 'Data berhasil di-restore!' . ($checklist->isVerified() ? ' Poin telah dikembalikan.' : ''));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal restore data: ' . $e->getMessage());
        }
    }

    /**
     * Display history of verified tasks with points detail.
     */
    public function verifiedHistory()
    {
        $siswa = Auth::guard('siswa')->user();
        
        $history = SiswaKarakterChecklist::where('siswa_id', $siswa->id)
            ->verified()
            ->with(['karakter', 'verifier'])
            ->orderBy('verified_at', 'desc')
            ->paginate(20);
        
        $historyStats = SiswaKarakterChecklist::where('siswa_id', $siswa->id)
            ->whereNotNull('verified_at')
            ->join('karakter', 'siswa_karakter_checklist.karakter_id', '=', 'karakter.id')
            ->selectRaw('COUNT(*) as total_verified')
            ->selectRaw('COALESCE(SUM(
                karakter.poin
                + CASE WHEN siswa_karakter_checklist.proof_path IS NOT NULL AND karakter.allows_photo_proof = 1 THEN karakter.photo_proof_bonus_points ELSE 0 END
                + CASE WHEN siswa_karakter_checklist.voice_note_path IS NOT NULL AND karakter.allows_voice_note_proof = 1 THEN karakter.voice_note_bonus_points ELSE 0 END
            ), 0) as total_points')
            ->first();

        $totalVerified = (int) ($historyStats->total_verified ?? 0);
        $totalPoints = (int) ($historyStats->total_points ?? 0);
        
        return view('siswa.tugas-pkg.verified-history', compact('history', 'totalVerified', 'totalPoints'));
    }

    /**
     * Check if all tasks in a category are completed & verified, and award bonus.
     */
    private function checkCategoryCompletionBonus($siswa, $karakter, GamificationService $gamificationService)
    {
        // Get all active tasks in same category
        $categoryTasks = Karakter::active()
            ->where('kategori', $karakter->kategori)
            ->pluck('id');
        
        if ($categoryTasks->isEmpty()) return;

        // Check if all are verified for this siswa
        $verifiedCount = SiswaKarakterChecklist::where('siswa_id', $siswa->id)
            ->whereIn('karakter_id', $categoryTasks)
            ->verified()
            ->distinct('karakter_id')
            ->count('karakter_id');
        
        if ($verifiedCount >= $categoryTasks->count()) {
            // Check if bonus was already awarded today for this category
            $alreadyAwarded = \App\Models\PointTransaction::where('siswa_id', $siswa->id)
                ->where('description', 'like', '%Bonus kategori ' . $karakter->kategori_label . '%')
                ->whereDate('created_at', today())
                ->exists();
            
            if (!$alreadyAwarded) {
                $bonusPoints = 50; // Bonus for completing all tasks in a category
                $siswaPoint = $gamificationService->getOrCreateSiswaPoint($siswa);
                $siswaPoint->addPoints(
                    $bonusPoints,
                    'bonus',
                    '🎉 Bonus kategori ' . $karakter->kategori_label . ': Semua tugas selesai & terverifikasi (+' . $bonusPoints . ' poin)'
                );
            }
        }
    }

    private function getChecklistPointBreakdown(SiswaKarakterChecklist $checklist): array
    {
        $basePoints = (int) ($checklist->karakter?->poin ?? 10);
        $photoBonus = (int) ($checklist->photo_proof_bonus_points ?? 0);
        $voiceBonus = (int) ($checklist->voice_note_bonus_points ?? 0);
        $proofBonus = $photoBonus + $voiceBonus;

        return [
            'base_points' => $basePoints,
            'photo_bonus' => $photoBonus,
            'voice_bonus' => $voiceBonus,
            'proof_bonus' => $proofBonus,
            'total' => $basePoints + $proofBonus,
        ];
    }

    private function getChecklistAwardedPoints(SiswaKarakterChecklist $checklist): int
    {
        return $this->getChecklistPointBreakdown($checklist)['total'];
    }

    private function buildChecklistPointMetadata(SiswaKarakterChecklist $checklist): array
    {
        $breakdown = $this->getChecklistPointBreakdown($checklist);

        return [
            'checklist_id' => $checklist->id,
            'base_points' => $breakdown['base_points'],
            'photo_proof_bonus_points' => $breakdown['photo_bonus'],
            'voice_note_bonus_points' => $breakdown['voice_bonus'],
            'proof_bonus_points' => $breakdown['proof_bonus'],
            'proof_uploaded' => $checklist->has_proof,
            'photo_uploaded' => $checklist->has_photo_proof,
            'voice_note_uploaded' => $checklist->has_voice_note,
        ];
    }
}
