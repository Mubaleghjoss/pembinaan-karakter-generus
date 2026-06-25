@extends('layouts.siswa')

@section('title', 'Tugas PKG')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6" x-data="window.tugasPkgPage()" x-init="initMediaPermissionGate()">
    <div class="mb-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Tugas PKG</h1>
                <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Tandai tugas yang sudah kamu lakukan</p>
            </div>
            <div class="flex gap-2">
                <button onclick="location.reload()" class="px-3 py-2 text-sm bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 border border-blue-200 dark:border-blue-800 transition-colors flex items-center gap-1">Refresh</button>
                <a href="{{ route('siswa.tugas-pkg.history') }}" class="px-3 py-2 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">Riwayat</a>
                <a href="{{ route('siswa.tugas-pkg.verified-history') }}" class="px-3 py-2 text-sm bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-lg hover:bg-green-200 dark:hover:bg-green-900/50">Terverifikasi</a>
            </div>
        </div>
    </div>

    <div class="mb-5">
        <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-hide -mx-1 px-1">
            @foreach($dateList as $dateItem)
            <a href="{{ route('siswa.tugas-pkg.index', ['date' => $dateItem['date']]) }}"
               class="flex-shrink-0 flex flex-col items-center px-3.5 py-2.5 rounded-xl border-2 transition-all duration-200 min-w-[70px]
                {{ $dateItem['is_selected'] ? 'bg-blue-600 border-blue-600 text-white shadow-lg shadow-blue-600/30 scale-105' : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-blue-300 dark:hover:border-blue-600 hover:bg-blue-50 dark:hover:bg-gray-700' }}">
                <span class="text-[10px] font-medium {{ $dateItem['is_selected'] ? 'text-blue-100' : 'text-gray-400 dark:text-gray-500' }} leading-tight">{{ $dateItem['day_name'] }}</span>
                <span class="text-lg font-bold leading-tight mt-0.5">{{ $dateItem['day_num'] }}</span>
                <span class="text-[10px] {{ $dateItem['is_selected'] ? 'text-blue-200' : 'text-gray-400 dark:text-gray-500' }} leading-tight">{{ $dateItem['month'] }}</span>
                @if($dateItem['is_today'] && !$dateItem['is_selected'])
                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mt-0.5"></span>
                @endif
            </a>
            @endforeach
        </div>
        @if($selectedDate !== now()->toDateString())
        <div class="mt-2 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-300 px-3 py-2 rounded-lg text-xs flex items-center gap-2">
            <span>Tanggal</span>
            <span>Menampilkan tugas tanggal <strong>{{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('l, d F Y') }}</strong></span>
            <a href="{{ route('siswa.tugas-pkg.index') }}" class="ml-auto text-amber-600 dark:text-amber-400 hover:text-amber-800 font-medium underline">Kembali ke Hari Ini</a>
        </div>
        @endif
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    <div x-show="mediaPermissionStatus !== 'granted' && !mediaPermissionDismissed" x-cloak
         class="mb-5 rounded-2xl border px-4 py-4 text-sm shadow-sm"
         :class="mediaPermissionStatus === 'blocked'
            ? 'border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200'
            : 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-200'">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <div class="font-semibold">Izin mikrofon untuk voice note</div>
                <p class="mt-1" x-text="mediaPermissionMessage"></p>
                <div x-show="showMediaPermissionHelp" class="mt-3 rounded-xl border border-current/20 bg-white/60 px-3 py-2 text-xs dark:bg-black/10">
                    <p>Jika izin sudah diblokir, buka ikon gembok atau pengaturan situs di browser, lalu izinkan Mikrofon untuk halaman ini. Setelah itu muat ulang halaman.</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2 sm:justify-end">
                <button type="button" @click="requestMicrophonePermission(true)"
                        class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="mediaPermissionStatus === 'checking'">
                    <span x-text="mediaPermissionStatus === 'checking' ? 'Memeriksa...' : 'Aktifkan Mikrofon'"></span>
                </button>
                <button type="button" @click="showMediaPermissionHelp = !showMediaPermissionHelp"
                        class="inline-flex items-center rounded-lg border border-current/30 bg-white px-3 py-2 text-xs font-semibold text-current hover:bg-white/80 dark:bg-slate-900/40 dark:hover:bg-slate-900/70">
                    <span x-text="showMediaPermissionHelp ? 'Tutup Cara' : 'Cara Mengaktifkan'"></span>
                </button>
                <button type="button" @click="mediaPermissionDismissed = true"
                        class="inline-flex items-center rounded-lg px-3 py-2 text-xs font-semibold text-current hover:bg-white/50 dark:hover:bg-black/10">
                    Nanti
                </button>
            </div>
        </div>
    </div>

    @php
        $categories = [
            'harian' => ['label' => 'Harian', 'color' => 'blue'],
            'mingguan' => ['label' => 'Mingguan', 'color' => 'purple'],
            'bulanan' => ['label' => 'Bulanan', 'color' => 'orange'],
        ];
    @endphp

    @if(isset($pendingVerification) && $pendingVerification->count() > 0 && $selectedDate === now()->toDateString())
    <div class="mb-6">
        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800 overflow-hidden">
            <div class="px-5 py-4 border-b border-yellow-200 dark:border-yellow-800">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-yellow-800 dark:text-yellow-300 flex items-center gap-2">
                        Menunggu Verifikasi
                        <span class="text-xs font-normal bg-yellow-200 dark:bg-yellow-800 text-yellow-800 dark:text-yellow-200 px-2 py-0.5 rounded-full">{{ $pendingVerification->count() }} tugas</span>
                    </h2>
                    <p class="text-xs text-yellow-600 dark:text-yellow-400">Dari hari sebelumnya</p>
                </div>
            </div>
            <div class="divide-y divide-yellow-100 dark:divide-yellow-800/50">
                @foreach($pendingVerification as $pending)
                <div class="px-5 py-3 flex items-center justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ $pending->karakter->nama ?? '-' }}</h3>
                            <span class="px-1.5 py-0.5 text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded">+{{ $pending->awarded_points ?? ($pending->karakter->poin ?? 0) }} poin</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Dicatat: {{ $pending->checked_at->format('d M Y H:i') }} | {{ $pending->checked_at->diffForHumans() }}</p>
                        @if($pending->student_note)
                            <p class="text-xs text-blue-600 dark:text-blue-400 mt-0.5 italic">Catatan: "{{ $pending->student_note }}"</p>
                        @endif
                        @if($pending->has_photo_proof)
                            <div class="mt-2 flex flex-wrap items-center gap-3">
                                <a href="{{ $pending->proof_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-xs font-medium text-blue-700 transition hover:bg-blue-100 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                                    Lihat bukti foto
                                </a>
                            </div>
                        @endif
                        @if($pending->has_voice_note)
                            <div class="mt-2 rounded-lg border border-violet-200 bg-violet-50 px-3 py-2 dark:border-violet-800 dark:bg-violet-900/20">
                                <p class="text-xs font-medium text-violet-700 dark:text-violet-300">Voice note bukti</p>
                                <audio controls preload="none" class="mt-2 w-full max-w-xs">
                                    <source src="{{ $pending->voice_note_url }}">
                                </audio>
                                <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-violet-600 dark:text-violet-400">
                                    <a href="{{ $pending->voice_note_url }}" target="_blank" rel="noopener" class="font-medium underline">Unduh voice note</a>
                                    <span>
                                        {{ $pending->voice_note_size_kb ?? 0 }} KB
                                        @if($pending->voice_note_duration_label)
                                            | {{ $pending->voice_note_duration_label }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                        @endif
                        @if(($pending->proof_bonus_points ?? 0) > 0)
                            <div class="mt-1 text-xs text-blue-600 dark:text-blue-400">Bonus bukti +{{ $pending->proof_bonus_points }} poin</div>
                        @endif
                    </div>
                    <span class="flex-shrink-0 px-3 py-1.5 text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-lg font-medium inline-flex items-center gap-1">Menunggu Verifikasi</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    @foreach($categories as $katKey => $katInfo)
        @php
            $tasks = $grouped->get($katKey, collect());
            $progress = $categoryProgress[$katKey] ?? ['total' => 0, 'completed' => 0, 'verified' => 0];
            $progressPercent = $progress['total'] > 0 ? round(($progress['completed'] / $progress['total']) * 100) : 0;
        @endphp
        @if($tasks->count() > 0)
        <div class="mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-t-lg border border-gray-200 dark:border-gray-700 px-5 py-4">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $katInfo['label'] }}</h2>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $progress['completed'] }}/{{ $progress['total'] }} selesai
                        @if($progress['verified'] > 0)
                            | {{ $progress['verified'] }} terverifikasi
                        @endif
                    </span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-{{ $katInfo['color'] }}-500 h-2 rounded-full transition-all duration-300" style="width: {{ $progressPercent }}%"></div>
                </div>
            </div>

            <div class="border border-t-0 border-gray-200 dark:border-gray-700 rounded-b-lg divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($tasks as $karakter)
                    @php
                        $checks = $checkedKarakter->get($karakter->id);
                        $dateCheck = $checks ? $checks->first() : null;
                        $isChecked = $dateCheck !== null;
                        $isVerified = $dateCheck && $dateCheck->verified_at !== null;
                        $selectedWorkDate = \Carbon\Carbon::parse($selectedDate)->startOfDay();
                        $isExpired = $karakter->tanggal_selesai && $selectedWorkDate->gt($karakter->tanggal_selesai->copy()->startOfDay());
                        $isAvailable = $karakter->isAvailableOn($selectedDate);
                    @endphp
                    <div class="bg-white dark:bg-gray-800 px-5 py-4 {{ $isExpired ? 'opacity-60' : '' }}">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ $karakter->nama }}</h3>
                                    <span class="px-1.5 py-0.5 text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded">+{{ $karakter->poin }} poin</span>
                                    @if(($karakter->photo_proof_bonus_points ?? 0) > 0 || ($karakter->voice_note_bonus_points ?? 0) > 0)
                                        <span class="px-1.5 py-0.5 text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded">
                                            Bonus bukti +{{ ($karakter->photo_proof_bonus_points ?? 0) + ($karakter->voice_note_bonus_points ?? 0) }}
                                        </span>
                                    @endif
                                </div>
                                @if($karakter->deskripsi)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $karakter->deskripsi }}</p>
                                @endif
                                @if($karakter->allows_photo_proof || $karakter->allows_voice_note_proof)
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @if($karakter->allows_photo_proof)
                                            <div class="rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-xs text-blue-700 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                                                <div class="font-medium">Kirim bukti foto dapat poin +{{ $karakter->photo_proof_bonus_points ?? 0 }}</div>
                                                @if($karakter->photo_proof_instruction)
                                                    <div class="mt-1">{{ $karakter->photo_proof_instruction }}</div>
                                                @endif
                                            </div>
                                        @endif
                                        @if($karakter->allows_voice_note_proof)
                                            <div class="rounded-lg border border-violet-200 bg-violet-50 px-2.5 py-1.5 text-xs text-violet-700 dark:border-violet-800 dark:bg-violet-900/20 dark:text-violet-300">
                                                <div class="font-medium">Kirim bukti voice note dapat poin +{{ $karakter->voice_note_bonus_points ?? 0 }}</div>
                                                @if($karakter->voice_note_instruction)
                                                    <div class="mt-1">{{ $karakter->voice_note_instruction }}</div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endif
                                @if($karakter->formatted_period)
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                        Periode: {{ $karakter->formatted_period }}
                                        @if($isExpired)
                                            <span class="text-red-500 font-medium">| Berakhir</span>
                                        @endif
                                    </p>
                                @endif
                                @if($isChecked && $dateCheck->student_note)
                                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-1 italic">Catatan: "{{ $dateCheck->student_note }}"</p>
                                @endif
                                @if($isChecked && $dateCheck->has_photo_proof)
                                    <div class="mt-2 flex items-center gap-3">
                                        <a href="{{ $dateCheck->proof_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-xs font-medium text-blue-700 transition hover:bg-blue-100 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                                            Lihat bukti foto
                                        </a>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $dateCheck->proof_compressed_size_kb ?? 0 }} KB
                                        </span>
                                    </div>
                                @endif
                                @if($isChecked && $dateCheck->has_voice_note)
                                    <div class="mt-2 rounded-lg border border-violet-200 bg-violet-50 px-3 py-2 dark:border-violet-800 dark:bg-violet-900/20">
                                        <p class="text-xs font-medium text-violet-700 dark:text-violet-300">Voice note bukti</p>
                                        <audio controls preload="none" class="mt-2 w-full max-w-xs">
                                            <source src="{{ $dateCheck->voice_note_url }}">
                                        </audio>
                                        <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-violet-600 dark:text-violet-400">
                                            <a href="{{ $dateCheck->voice_note_url }}" target="_blank" rel="noopener" class="font-medium underline">Unduh voice note</a>
                                            <span>
                                                {{ $dateCheck->voice_note_size_kb ?? 0 }} KB
                                                @if($dateCheck->voice_note_duration_label)
                                                    | {{ $dateCheck->voice_note_duration_label }}
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                @endif
                                @if($isChecked && ($dateCheck->proof_bonus_points ?? 0) > 0)
                                    <p class="mt-1 text-xs text-blue-600 dark:text-blue-400">Bonus bukti +{{ $dateCheck->proof_bonus_points }} poin</p>
                                @endif
                            </div>
                            <div class="flex-shrink-0">
                                @if($isExpired)
                                    <span class="px-3 py-2 text-xs bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded-lg inline-flex items-center gap-1">Waktu Selesai</span>
                                @elseif($isVerified)
                                    <span class="px-3 py-2 text-xs bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-lg inline-flex items-center gap-1 font-medium">Terverifikasi</span>
                                @elseif($isChecked)
                                    <span class="px-3 py-2 text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-lg inline-flex items-center gap-1 font-medium">Menunggu Verifikasi</span>
                                @elseif(!$isAvailable)
                                    <span class="px-3 py-2 text-xs bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded-lg inline-flex items-center gap-1">Belum Tersedia</span>
                                @else
                                    @if($karakter->jenis_penyelesaian === 'teks')
                                        <button data-url="{{ route('siswa.tugas-pkg.submit', $karakter) }}" data-nama="{{ $karakter->nama }}" data-target="{{ $karakter->target_teks }}" data-allows-photo-proof="{{ $karakter->allows_photo_proof ? '1' : '0' }}" data-photo-proof-bonus="{{ $karakter->photo_proof_bonus_points ?? 0 }}" data-photo-proof-instruction="{{ $karakter->photo_proof_instruction }}" data-allows-voice-proof="{{ $karakter->allows_voice_note_proof ? '1' : '0' }}" data-voice-proof-bonus="{{ $karakter->voice_note_bonus_points ?? 0 }}" data-voice-proof-instruction="{{ $karakter->voice_note_instruction }}" data-proof-requirement="{{ $karakter->proof_requirement ?? 'optional' }}" data-voice-max-seconds="{{ $karakter->voice_note_max_seconds ?? 0 }}" @click="openTeksModal($el.dataset.url, $el.dataset.nama, $el.dataset.target, $el.dataset.allowsPhotoProof, $el.dataset.photoProofBonus, $el.dataset.photoProofInstruction, $el.dataset.allowsVoiceProof, $el.dataset.voiceProofBonus, $el.dataset.voiceProofInstruction, $el.dataset.proofRequirement, $el.dataset.voiceMaxSeconds)" class="px-3 py-2 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700 inline-flex items-center gap-1 font-medium shadow-sm">Kerjakan Tugas</button>
                                    @elseif($karakter->jenis_penyelesaian === 'klik')
                                        <button data-url="{{ route('siswa.tugas-pkg.submit', $karakter) }}" data-id="{{ $karakter->id }}" data-nama="{{ $karakter->nama }}" data-target="{{ $karakter->target_klik ?? 0 }}" data-poin="{{ $karakter->poin ?? 0 }}" data-deskripsi="{{ $karakter->deskripsi }}" data-allows-photo-proof="{{ $karakter->allows_photo_proof ? '1' : '0' }}" data-photo-proof-bonus="{{ $karakter->photo_proof_bonus_points ?? 0 }}" data-photo-proof-instruction="{{ $karakter->photo_proof_instruction }}" data-allows-voice-proof="{{ $karakter->allows_voice_note_proof ? '1' : '0' }}" data-voice-proof-bonus="{{ $karakter->voice_note_bonus_points ?? 0 }}" data-voice-proof-instruction="{{ $karakter->voice_note_instruction }}" data-proof-requirement="{{ $karakter->proof_requirement ?? 'optional' }}" data-voice-max-seconds="{{ $karakter->voice_note_max_seconds ?? 0 }}" @click="openKlikModal($el.dataset.url, $el.dataset.id, $el.dataset.nama, $el.dataset.target, $el.dataset.poin, $el.dataset.deskripsi, $el.dataset.allowsPhotoProof, $el.dataset.photoProofBonus, $el.dataset.photoProofInstruction, $el.dataset.allowsVoiceProof, $el.dataset.voiceProofBonus, $el.dataset.voiceProofInstruction, $el.dataset.proofRequirement, $el.dataset.voiceMaxSeconds)" class="px-3 py-2 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700 inline-flex items-center gap-1 font-medium shadow-sm">Mulai Hitungan</button>
                                    @else
                                        <button data-url="{{ route('siswa.tugas-pkg.submit', $karakter) }}" data-nama="{{ $karakter->nama }}" data-deskripsi="{{ $karakter->deskripsi }}" data-allows-photo-proof="{{ $karakter->allows_photo_proof ? '1' : '0' }}" data-photo-proof-bonus="{{ $karakter->photo_proof_bonus_points ?? 0 }}" data-photo-proof-instruction="{{ $karakter->photo_proof_instruction }}" data-allows-voice-proof="{{ $karakter->allows_voice_note_proof ? '1' : '0' }}" data-voice-proof-bonus="{{ $karakter->voice_note_bonus_points ?? 0 }}" data-voice-proof-instruction="{{ $karakter->voice_note_instruction }}" data-proof-requirement="{{ $karakter->proof_requirement ?? 'optional' }}" data-voice-max-seconds="{{ $karakter->voice_note_max_seconds ?? 0 }}" @click="openEvidenceModal($el.dataset.url, $el.dataset.nama, $el.dataset.deskripsi, $el.dataset.allowsPhotoProof, $el.dataset.photoProofBonus, $el.dataset.photoProofInstruction, $el.dataset.allowsVoiceProof, $el.dataset.voiceProofBonus, $el.dataset.voiceProofInstruction, $el.dataset.proofRequirement, $el.dataset.voiceMaxSeconds)" class="px-3 py-2 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700 inline-flex items-center gap-1 font-medium shadow-sm">Tandai Selesai</button>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            @if($progress['total'] > 0 && $progress['verified'] >= $progress['total'])
                <div class="mt-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 px-4 py-2 rounded-lg text-sm text-center">Semua tugas {{ strtolower($katInfo['label']) }} sudah terverifikasi. Bonus +50 poin</div>
            @endif
        </div>
        @endif
    @endforeach

    @if($karakterList->isEmpty())
        <div class="pkg-card p-12 text-center">
            <p class="text-gray-500 dark:text-gray-400">Belum ada tugas PKG yang tersedia saat ini.</p>
        </div>
    @endif

    <div x-show="showModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="stopVoiceRecordingSession(true); stopPhotoCameraSession(true); showModal = false" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom pkg-modal text-left overflow-hidden transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <form :action="selectedUrl" method="POST" enctype="multipart/form-data" @submit.prevent="submitTaskForm($event)">
                    @csrf
                    <input type="hidden" name="for_date" value="{{ $selectedDate }}">
                    <input type="hidden" name="proof_voice_note_duration_seconds" value="">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900/30 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">Konfirmasi Tugas: <span x-text="selectedName"></span></h3>
                                @if($selectedDate !== now()->toDateString())
                                <div class="mt-2 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-300 px-3 py-2 rounded-lg text-xs flex items-center gap-2">
                                    Tanggal tugas: <strong>{{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('d F Y') }}</strong>
                                </div>
                                @endif
                                <div x-show="selectedDescription" class="mt-3 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-left dark:border-gray-700 dark:bg-gray-900/40">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Deskripsi tugas</div>
                                    <p class="mt-1 whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-200" x-text="selectedDescription"></p>
                                </div>
                                <div class="mt-2">
                                    <template x-if="proofRequirement === 'required_any'">
                                        <div class="mb-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
                                            Tugas ini mewajibkan minimal satu bukti berupa foto atau voice note.
                                        </div>
                                    </template>
                                    <textarea name="student_note" rows="3" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md p-2" placeholder="Tulis bukti/catatan di sini..."></textarea>
                                </div>
                                <div x-show="showPhotoProofUpload" class="mt-4 rounded-xl border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-900/20">
                                    <label class="block text-sm font-medium text-blue-900 dark:text-blue-200 mb-2">Bukti foto</label>
                                    <input type="file" name="proof_image" accept="image/*" capture="environment" class="sr-only" @change="handleProofFileChange($event, 'photo')">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <button type="button" @click.prevent="startPhotoCapture($event)" class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">Ambil Foto</button>
                                        <button type="button" @click.prevent="openProofPicker($event, 'proof_image', 'library')" class="inline-flex items-center rounded-lg border border-blue-300 bg-white px-3 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100 dark:border-blue-700 dark:bg-blue-950/40 dark:text-blue-200 dark:hover:bg-blue-900/40">Pilih dari Galeri</button>
                                        <span class="text-xs text-blue-800 dark:text-blue-200" x-text="selectedPhotoProofName || 'Belum ada foto dipilih'"></span>
                                    </div>
                                    <div x-show="isPhotoCameraActive" class="mt-3 rounded-xl border border-blue-200 bg-white p-3 shadow-sm dark:border-blue-700 dark:bg-slate-900/70">
                                        <video data-photo-camera-video autoplay playsinline muted class="h-56 w-full rounded-xl bg-black object-cover"></video>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <button type="button" @click.prevent="capturePhotoFromCamera()" class="inline-flex items-center rounded-lg bg-green-600 px-3 py-2 text-sm font-medium text-white hover:bg-green-700">Jepret</button>
                                            <button type="button" @click.prevent="stopPhotoCameraSession(true)" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">Tutup Kamera</button>
                                        </div>
                                    </div>
                                    <div x-show="selectedPhotoProofPreviewUrl" class="mt-3">
                                        <img :src="selectedPhotoProofPreviewUrl" alt="Preview bukti foto" class="h-28 w-28 rounded-xl border border-blue-200 object-cover shadow-sm dark:border-blue-700">
                                        <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-blue-800 dark:text-blue-200">
                                            <span x-show="selectedPhotoProofSizeLabel" x-text="selectedPhotoProofSizeLabel"></span>
                                            <button type="button" @click.prevent="clearProofFile($event, 'photo')" class="font-medium text-red-600 hover:text-red-700 dark:text-red-300 dark:hover:text-red-200">Hapus bukti foto</button>
                                        </div>
                                    </div>
                                    <div x-show="hasLargePhotoWarning()" class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
                                        Ukuran foto cukup besar. Sistem tetap akan mencoba mengompresnya, tetapi unggah bisa terasa lebih lambat.
                                    </div>
                                    <template x-if="photoProofInstruction">
                                        <p class="mt-1 text-xs text-blue-700 dark:text-blue-300" x-text="photoProofInstruction"></p>
                                    </template>
                                    <template x-if="photoProofBonusPoints > 0">
                                        <p class="mt-1 text-xs font-medium text-blue-700 dark:text-blue-300">Bonus bukti foto +<span x-text="photoProofBonusPoints"></span> poin diberikan saat tugas diverifikasi.</p>
                                    </template>
                                </div>
                                <div x-show="showVoiceProofUpload" class="mt-4 rounded-xl border border-violet-200 bg-violet-50 p-3 dark:border-violet-800 dark:bg-violet-900/20">
                                    <label class="block text-sm font-medium text-violet-900 dark:text-violet-200 mb-2">Voice note</label>
                                    <input type="file" name="proof_voice_note" accept="audio/*" capture class="sr-only" @change="handleProofFileChange($event, 'voice')">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <button type="button" :disabled="isRecordingVoice" @click.prevent="toggleVoiceRecording($event)" class="inline-flex items-center rounded-lg bg-violet-600 px-3 py-2 text-sm font-medium text-white hover:bg-violet-700 disabled:cursor-not-allowed disabled:opacity-60">Rekam</button>
                                        <button type="button" :disabled="!isRecordingVoice" @click.prevent="toggleVoicePause()" class="inline-flex items-center rounded-lg bg-amber-500 px-3 py-2 text-sm font-medium text-white hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-60" x-text="isVoiceRecordingPaused ? 'Lanjutkan' : 'Pause'"></button>
                                        <button type="button" :disabled="!isRecordingVoice" @click.prevent="stopVoiceRecording()" class="inline-flex items-center rounded-lg bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60">Stop</button>
                                        <button type="button" :disabled="isRecordingVoice" @click.prevent="openProofPicker($event, 'proof_voice_note', 'library')" class="inline-flex items-center rounded-lg border border-violet-300 bg-white px-3 py-2 text-sm font-medium text-violet-700 hover:bg-violet-100 disabled:cursor-not-allowed disabled:opacity-60 dark:border-violet-700 dark:bg-violet-950/40 dark:text-violet-200 dark:hover:bg-violet-900/40">Pilih File Audio</button>
                                        <span class="text-xs text-violet-800 dark:text-violet-200" x-text="selectedVoiceProofName || 'Belum ada audio dipilih'"></span>
                                    </div>
                                    <p x-show="isRecordingVoice" class="mt-2 text-xs font-medium" :class="isVoiceRecordingPaused ? 'text-amber-600 dark:text-amber-300' : 'text-red-600 dark:text-red-300'">
                                        <span x-text="isVoiceRecordingPaused ? 'Rekaman dijeda' : 'Sedang merekam'"></span>: <span x-text="recordingVoiceDurationLabel"></span>
                                    </p>
                                    <div x-show="selectedVoiceProofPreviewUrl" class="mt-3">
                                        <audio :src="selectedVoiceProofPreviewUrl" controls preload="metadata" class="w-full max-w-sm"></audio>
                                        <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-violet-800 dark:text-violet-200">
                                            <span x-show="selectedVoiceProofSizeLabel" x-text="selectedVoiceProofSizeLabel"></span>
                                            <span x-show="selectedVoiceProofDurationLabel" x-text="selectedVoiceProofDurationLabel"></span>
                                            <button type="button" @click.prevent="clearProofFile($event, 'voice')" class="font-medium text-red-600 hover:text-red-700 dark:text-red-300 dark:hover:text-red-200">Hapus voice note</button>
                                        </div>
                                    </div>
                                    <div x-show="isVoiceDurationInvalid()" class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
                                        Durasi voice note melebihi batas tugas. Rekam ulang atau pilih file audio yang lebih pendek.
                                    </div>
                                    <template x-if="voiceNoteMaxSeconds > 0">
                                        <p class="mt-1 text-xs font-medium text-violet-700 dark:text-violet-300">Durasi maksimal <span x-text="voiceNoteMaxSeconds"></span> detik.</p>
                                    </template>
                                    <template x-if="voiceNoteInstruction">
                                        <p class="mt-1 text-xs text-violet-700 dark:text-violet-300" x-text="voiceNoteInstruction"></p>
                                    </template>
                                    <template x-if="voiceProofBonusPoints > 0">
                                        <p class="mt-1 text-xs font-medium text-violet-700 dark:text-violet-300">Bonus voice note +<span x-text="voiceProofBonusPoints"></span> poin diberikan saat tugas diverifikasi.</p>
                                    </template>
                                </div>
                                <div class="mt-4 rounded-lg border px-3 py-2 text-xs"
                                     :class="proofStatusTone() === 'success'
                                        ? 'border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300'
                                        : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300'">
                                    <span x-text="proofStatusMessage()"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" :disabled="!canSubmitProof()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 text-base font-medium text-white sm:ml-3 sm:w-auto sm:text-sm" :class="canSubmitProof() ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-300 dark:bg-gray-700 cursor-not-allowed opacity-70'">Simpan dan Tandai Selesai</button>
                        <button type="button" @click="stopVoiceRecordingSession(true); stopPhotoCameraSession(true); showModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div x-show="showTeksModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showTeksModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="stopVoiceRecordingSession(true); stopPhotoCameraSession(true); showTeksModal = false" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="showTeksModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" class="inline-block align-bottom pkg-modal text-left overflow-hidden transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <form :action="selectedUrl" method="POST" enctype="multipart/form-data" @submit.prevent="submitTaskForm($event)">
                    @csrf
                    <input type="hidden" name="for_date" value="{{ $selectedDate }}">
                    <input type="hidden" name="proof_voice_note_duration_seconds" value="">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-bold text-gray-900 dark:text-white" id="modal-title"><span x-text="selectedName"></span></h3>
                            <div class="mt-4 bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg border border-blue-100 dark:border-blue-800">
                                <p class="text-sm font-medium text-blue-800 dark:text-blue-300" x-text="targetTeks"></p>
                            </div>
                            <div class="mt-4">
                                <template x-if="proofRequirement === 'required_any'">
                                    <div class="mb-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
                                        Tugas ini mewajibkan minimal satu bukti berupa foto atau voice note.
                                    </div>
                                </template>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Jawaban / Laporan Anda:</label>
                                <textarea name="hasil_teks" rows="4" required class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md p-3" placeholder="Ketik jawaban Anda di sini..."></textarea>
                            </div>
                            <div x-show="showPhotoProofUpload" class="mt-4 rounded-xl border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-900/20">
                                <label class="block text-sm font-medium text-blue-900 dark:text-blue-200 mb-2">Bukti foto</label>
                                <input type="file" name="proof_image" accept="image/*" capture="environment" class="sr-only" @change="handleProofFileChange($event, 'photo')">
                                <div class="flex flex-wrap items-center gap-2">
                                    <button type="button" @click.prevent="startPhotoCapture($event)" class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">Ambil Foto</button>
                                    <button type="button" @click.prevent="openProofPicker($event, 'proof_image', 'library')" class="inline-flex items-center rounded-lg border border-blue-300 bg-white px-3 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100 dark:border-blue-700 dark:bg-blue-950/40 dark:text-blue-200 dark:hover:bg-blue-900/40">Pilih dari Galeri</button>
                                    <span class="text-xs text-blue-800 dark:text-blue-200" x-text="selectedPhotoProofName || 'Belum ada foto dipilih'"></span>
                                </div>
                                <div x-show="isPhotoCameraActive" class="mt-3 rounded-xl border border-blue-200 bg-white p-3 shadow-sm dark:border-blue-700 dark:bg-slate-900/70">
                                    <video data-photo-camera-video autoplay playsinline muted class="h-56 w-full rounded-xl bg-black object-cover"></video>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <button type="button" @click.prevent="capturePhotoFromCamera()" class="inline-flex items-center rounded-lg bg-green-600 px-3 py-2 text-sm font-medium text-white hover:bg-green-700">Jepret</button>
                                        <button type="button" @click.prevent="stopPhotoCameraSession(true)" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">Tutup Kamera</button>
                                    </div>
                                </div>
                                <div x-show="selectedPhotoProofPreviewUrl" class="mt-3">
                                    <img :src="selectedPhotoProofPreviewUrl" alt="Preview bukti foto" class="h-28 w-28 rounded-xl border border-blue-200 object-cover shadow-sm dark:border-blue-700">
                                    <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-blue-800 dark:text-blue-200">
                                        <span x-show="selectedPhotoProofSizeLabel" x-text="selectedPhotoProofSizeLabel"></span>
                                        <button type="button" @click.prevent="clearProofFile($event, 'photo')" class="font-medium text-red-600 hover:text-red-700 dark:text-red-300 dark:hover:text-red-200">Hapus bukti foto</button>
                                    </div>
                                </div>
                                <div x-show="hasLargePhotoWarning()" class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
                                    Ukuran foto cukup besar. Sistem tetap akan mencoba mengompresnya, tetapi unggah bisa terasa lebih lambat.
                                </div>
                                <template x-if="photoProofInstruction">
                                    <p class="mt-1 text-xs text-blue-700 dark:text-blue-300" x-text="photoProofInstruction"></p>
                                </template>
                                <template x-if="photoProofBonusPoints > 0">
                                    <p class="mt-1 text-xs font-medium text-blue-700 dark:text-blue-300">Bonus bukti foto +<span x-text="photoProofBonusPoints"></span> poin diberikan saat tugas diverifikasi.</p>
                                </template>
                            </div>
                            <div x-show="showVoiceProofUpload" class="mt-4 rounded-xl border border-violet-200 bg-violet-50 p-3 dark:border-violet-800 dark:bg-violet-900/20">
                                <label class="block text-sm font-medium text-violet-900 dark:text-violet-200 mb-2">Voice note</label>
                                <input type="file" name="proof_voice_note" accept="audio/*" capture class="sr-only" @change="handleProofFileChange($event, 'voice')">
                                <div class="flex flex-wrap items-center gap-2">
                                    <button type="button" :disabled="isRecordingVoice" @click.prevent="toggleVoiceRecording($event)" class="inline-flex items-center rounded-lg bg-violet-600 px-3 py-2 text-sm font-medium text-white hover:bg-violet-700 disabled:cursor-not-allowed disabled:opacity-60">Rekam</button>
                                    <button type="button" :disabled="!isRecordingVoice" @click.prevent="toggleVoicePause()" class="inline-flex items-center rounded-lg bg-amber-500 px-3 py-2 text-sm font-medium text-white hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-60" x-text="isVoiceRecordingPaused ? 'Lanjutkan' : 'Pause'"></button>
                                    <button type="button" :disabled="!isRecordingVoice" @click.prevent="stopVoiceRecording()" class="inline-flex items-center rounded-lg bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60">Stop</button>
                                    <button type="button" :disabled="isRecordingVoice" @click.prevent="openProofPicker($event, 'proof_voice_note', 'library')" class="inline-flex items-center rounded-lg border border-violet-300 bg-white px-3 py-2 text-sm font-medium text-violet-700 hover:bg-violet-100 disabled:cursor-not-allowed disabled:opacity-60 dark:border-violet-700 dark:bg-violet-950/40 dark:text-violet-200 dark:hover:bg-violet-900/40">Pilih File Audio</button>
                                    <span class="text-xs text-violet-800 dark:text-violet-200" x-text="selectedVoiceProofName || 'Belum ada audio dipilih'"></span>
                                </div>
                                <p x-show="isRecordingVoice" class="mt-2 text-xs font-medium" :class="isVoiceRecordingPaused ? 'text-amber-600 dark:text-amber-300' : 'text-red-600 dark:text-red-300'">
                                    <span x-text="isVoiceRecordingPaused ? 'Rekaman dijeda' : 'Sedang merekam'"></span>: <span x-text="recordingVoiceDurationLabel"></span>
                                </p>
                                <div x-show="selectedVoiceProofPreviewUrl" class="mt-3">
                                    <audio :src="selectedVoiceProofPreviewUrl" controls preload="metadata" class="w-full max-w-sm"></audio>
                                    <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-violet-800 dark:text-violet-200">
                                        <span x-show="selectedVoiceProofSizeLabel" x-text="selectedVoiceProofSizeLabel"></span>
                                        <span x-show="selectedVoiceProofDurationLabel" x-text="selectedVoiceProofDurationLabel"></span>
                                        <button type="button" @click.prevent="clearProofFile($event, 'voice')" class="font-medium text-red-600 hover:text-red-700 dark:text-red-300 dark:hover:text-red-200">Hapus voice note</button>
                                    </div>
                                </div>
                                <div x-show="isVoiceDurationInvalid()" class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
                                    Durasi voice note melebihi batas tugas. Rekam ulang atau pilih file audio yang lebih pendek.
                                </div>
                                <template x-if="voiceNoteMaxSeconds > 0">
                                    <p class="mt-1 text-xs font-medium text-violet-700 dark:text-violet-300">Durasi maksimal <span x-text="voiceNoteMaxSeconds"></span> detik.</p>
                                </template>
                                <template x-if="voiceNoteInstruction">
                                    <p class="mt-1 text-xs text-violet-700 dark:text-violet-300" x-text="voiceNoteInstruction"></p>
                                </template>
                                <template x-if="voiceProofBonusPoints > 0">
                                    <p class="mt-1 text-xs font-medium text-violet-700 dark:text-violet-300">Bonus voice note +<span x-text="voiceProofBonusPoints"></span> poin diberikan saat tugas diverifikasi.</p>
                                </template>
                            </div>
                            <div class="mt-4 rounded-lg border px-3 py-2 text-xs"
                                 :class="proofStatusTone() === 'success'
                                    ? 'border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300'
                                    : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300'">
                                <span x-text="proofStatusMessage()"></span>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" :disabled="!canSubmitProof()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 text-base font-medium text-white sm:ml-3 sm:w-auto sm:text-sm" :class="canSubmitProof() ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-300 dark:bg-gray-700 cursor-not-allowed opacity-70'">Kirim Jawaban</button>
                        <button type="button" @click="stopVoiceRecordingSession(true); stopPhotoCameraSession(true); showTeksModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div x-show="showKlikModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showKlikModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeKlikModal()" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="showKlikModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" class="inline-block align-bottom bg-white dark:bg-gray-900 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full border border-gray-200 dark:border-gray-700">
                <form :action="selectedUrl" method="POST" id="zikrForm" enctype="multipart/form-data" @submit.prevent="submitTaskForm($event)">
                    @csrf
                    <input type="hidden" name="for_date" value="{{ $selectedDate }}">
                    <input type="hidden" name="click_history" :value="JSON.stringify(clickHistory)">
                    <input type="hidden" name="proof_voice_note_duration_seconds" value="">
                    <div class="px-6 py-6 text-center">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1"><span x-text="selectedName"></span></h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Target hitungan: <span class="font-bold text-blue-600 dark:text-blue-400" x-text="targetKlik"></span></p>
                        <div x-show="deskripsiKlik" class="mb-4 text-sm text-gray-600 dark:text-gray-300 italic whitespace-pre-wrap px-4 py-2 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700 mx-auto max-w-sm" x-text="deskripsiKlik"></div>
                        <div x-show="totalPoin > 0 && targetKlik > 0" class="mb-4 inline-block px-3 py-1 bg-yellow-100 dark:bg-yellow-900/40 text-yellow-800 dark:text-yellow-300 rounded-full text-sm font-semibold border border-yellow-200 dark:border-yellow-700">
                            Poin Diraih: <span x-text="Math.floor((currentKlik / targetKlik) * totalPoin)"></span> / <span x-text="totalPoin"></span>
                        </div>
                        <template x-if="proofRequirement === 'required_any'">
                            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
                                Setelah hitungan selesai, kamu tetap harus mengunggah minimal satu bukti berupa foto atau voice note.
                            </div>
                        </template>
                        <div class="relative w-48 h-48 mx-auto mb-8 cursor-pointer group" @click="doClick()" style="-webkit-tap-highlight-color: transparent;">
                            <svg class="w-full h-full transform -rotate-90">
                                <circle class="text-gray-200 dark:text-gray-700" stroke-width="8" stroke="currentColor" fill="transparent" r="88" cx="96" cy="96"/>
                                <circle class="text-blue-600 transition-all duration-300 ease-out" stroke-width="8" :stroke-dasharray="2 * Math.PI * 88" :stroke-dashoffset="2 * Math.PI * 88 * (1 - currentKlik/targetKlik)" stroke-linecap="round" stroke="currentColor" fill="transparent" r="88" cx="96" cy="96"/>
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center bg-white dark:bg-gray-800 rounded-full shadow-inner m-4 border-4 border-gray-50 dark:border-gray-700 group-hover:scale-[0.98] group-active:scale-95 transition-transform duration-100">
                                <span class="text-5xl font-black text-gray-900 dark:text-white tracking-tighter" x-text="currentKlik"></span>
                                <span class="text-xs font-bold text-gray-400 dark:text-gray-500 mt-1 uppercase tracking-widest">TAP DISINI</span>
                            </div>
                        </div>
                        <button type="submit" :disabled="currentKlik < targetKlik || !canSubmitProof()" class="w-full py-4 px-6 rounded-xl font-bold text-white transition-all duration-300 shadow-lg" :class="currentKlik >= targetKlik && canSubmitProof() ? 'bg-green-500 hover:bg-green-600 shadow-green-500/30' : 'bg-gray-300 dark:bg-gray-700 cursor-not-allowed opacity-70'">
                            <span x-show="currentKlik < targetKlik">Lengkapi Hitungan (<span x-text="targetKlik - currentKlik"></span> lagi)</span>
                            <span x-show="currentKlik >= targetKlik && canSubmitProof()">Selesai. Simpan</span>
                            <span x-show="currentKlik >= targetKlik && !canSubmitProof()">Lengkapi bukti dulu</span>
                        </button>
                        <div x-show="showPhotoProofUpload" class="mt-4 rounded-xl border border-blue-200 bg-blue-50 p-3 text-left dark:border-blue-800 dark:bg-blue-900/20">
                            <label class="block text-sm font-medium text-blue-900 dark:text-blue-200 mb-2">Bukti foto</label>
                            <input type="file" name="proof_image" accept="image/*" capture="environment" class="sr-only" @change="handleProofFileChange($event, 'photo')">
                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" @click.prevent="startPhotoCapture($event)" class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">Ambil Foto</button>
                                <button type="button" @click.prevent="openProofPicker($event, 'proof_image', 'library')" class="inline-flex items-center rounded-lg border border-blue-300 bg-white px-3 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100 dark:border-blue-700 dark:bg-blue-950/40 dark:text-blue-200 dark:hover:bg-blue-900/40">Pilih dari Galeri</button>
                                <span class="text-xs text-blue-800 dark:text-blue-200" x-text="selectedPhotoProofName || 'Belum ada foto dipilih'"></span>
                            </div>
                            <div x-show="isPhotoCameraActive" class="mt-3 rounded-xl border border-blue-200 bg-white p-3 shadow-sm dark:border-blue-700 dark:bg-slate-900/70">
                                <video data-photo-camera-video autoplay playsinline muted class="h-56 w-full rounded-xl bg-black object-cover"></video>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <button type="button" @click.prevent="capturePhotoFromCamera()" class="inline-flex items-center rounded-lg bg-green-600 px-3 py-2 text-sm font-medium text-white hover:bg-green-700">Jepret</button>
                                    <button type="button" @click.prevent="stopPhotoCameraSession(true)" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">Tutup Kamera</button>
                                </div>
                            </div>
                            <div x-show="selectedPhotoProofPreviewUrl" class="mt-3">
                                <img :src="selectedPhotoProofPreviewUrl" alt="Preview bukti foto" class="h-28 w-28 rounded-xl border border-blue-200 object-cover shadow-sm dark:border-blue-700">
                                <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-blue-800 dark:text-blue-200">
                                    <span x-show="selectedPhotoProofSizeLabel" x-text="selectedPhotoProofSizeLabel"></span>
                                    <button type="button" @click.prevent="clearProofFile($event, 'photo')" class="font-medium text-red-600 hover:text-red-700 dark:text-red-300 dark:hover:text-red-200">Hapus bukti foto</button>
                                </div>
                            </div>
                            <div x-show="hasLargePhotoWarning()" class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
                                Ukuran foto cukup besar. Sistem tetap akan mencoba mengompresnya, tetapi unggah bisa terasa lebih lambat.
                            </div>
                            <template x-if="photoProofInstruction">
                                <p class="mt-1 text-xs text-blue-700 dark:text-blue-300" x-text="photoProofInstruction"></p>
                            </template>
                            <template x-if="photoProofBonusPoints > 0">
                                <p class="mt-1 text-xs font-medium text-blue-700 dark:text-blue-300">Bonus bukti foto +<span x-text="photoProofBonusPoints"></span> poin diberikan saat tugas diverifikasi.</p>
                            </template>
                        </div>
                        <div x-show="showVoiceProofUpload" class="mt-4 rounded-xl border border-violet-200 bg-violet-50 p-3 text-left dark:border-violet-800 dark:bg-violet-900/20">
                            <label class="block text-sm font-medium text-violet-900 dark:text-violet-200 mb-2">Voice note</label>
                            <input type="file" name="proof_voice_note" accept="audio/*" capture class="sr-only" @change="handleProofFileChange($event, 'voice')">
                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" :disabled="isRecordingVoice" @click.prevent="toggleVoiceRecording($event)" class="inline-flex items-center rounded-lg bg-violet-600 px-3 py-2 text-sm font-medium text-white hover:bg-violet-700 disabled:cursor-not-allowed disabled:opacity-60">Rekam</button>
                                <button type="button" :disabled="!isRecordingVoice" @click.prevent="toggleVoicePause()" class="inline-flex items-center rounded-lg bg-amber-500 px-3 py-2 text-sm font-medium text-white hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-60" x-text="isVoiceRecordingPaused ? 'Lanjutkan' : 'Pause'"></button>
                                <button type="button" :disabled="!isRecordingVoice" @click.prevent="stopVoiceRecording()" class="inline-flex items-center rounded-lg bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60">Stop</button>
                                <button type="button" :disabled="isRecordingVoice" @click.prevent="openProofPicker($event, 'proof_voice_note', 'library')" class="inline-flex items-center rounded-lg border border-violet-300 bg-white px-3 py-2 text-sm font-medium text-violet-700 hover:bg-violet-100 disabled:cursor-not-allowed disabled:opacity-60 dark:border-violet-700 dark:bg-violet-950/40 dark:text-violet-200 dark:hover:bg-violet-900/40">Pilih File Audio</button>
                                <span class="text-xs text-violet-800 dark:text-violet-200" x-text="selectedVoiceProofName || 'Belum ada audio dipilih'"></span>
                            </div>
                            <p x-show="isRecordingVoice" class="mt-2 text-xs font-medium" :class="isVoiceRecordingPaused ? 'text-amber-600 dark:text-amber-300' : 'text-red-600 dark:text-red-300'">
                                <span x-text="isVoiceRecordingPaused ? 'Rekaman dijeda' : 'Sedang merekam'"></span>: <span x-text="recordingVoiceDurationLabel"></span>
                            </p>
                            <div x-show="selectedVoiceProofPreviewUrl" class="mt-3">
                                <audio :src="selectedVoiceProofPreviewUrl" controls preload="metadata" class="w-full max-w-sm"></audio>
                                <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-violet-800 dark:text-violet-200">
                                    <span x-show="selectedVoiceProofSizeLabel" x-text="selectedVoiceProofSizeLabel"></span>
                                    <span x-show="selectedVoiceProofDurationLabel" x-text="selectedVoiceProofDurationLabel"></span>
                                    <button type="button" @click.prevent="clearProofFile($event, 'voice')" class="font-medium text-red-600 hover:text-red-700 dark:text-red-300 dark:hover:text-red-200">Hapus voice note</button>
                                </div>
                            </div>
                            <div x-show="isVoiceDurationInvalid()" class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
                                Durasi voice note melebihi batas tugas. Rekam ulang atau pilih file audio yang lebih pendek.
                            </div>
                            <template x-if="voiceNoteMaxSeconds > 0">
                                <p class="mt-1 text-xs font-medium text-violet-700 dark:text-violet-300">Durasi maksimal <span x-text="voiceNoteMaxSeconds"></span> detik.</p>
                            </template>
                            <template x-if="voiceNoteInstruction">
                                <p class="mt-1 text-xs text-violet-700 dark:text-violet-300" x-text="voiceNoteInstruction"></p>
                            </template>
                            <template x-if="voiceProofBonusPoints > 0">
                                <p class="mt-1 text-xs font-medium text-violet-700 dark:text-violet-300">Bonus voice note +<span x-text="voiceProofBonusPoints"></span> poin diberikan saat tugas diverifikasi.</p>
                            </template>
                        </div>
                        <div class="mt-4 rounded-lg border px-3 py-2 text-xs text-left"
                             :class="proofStatusTone() === 'success'
                                ? 'border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300'
                                : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300'">
                            <span x-text="proofStatusMessage()"></span>
                        </div>
                        <button type="button" @click="closeKlikModal()" class="mt-4 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 underline font-medium">Tutup dan Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<script>
window.tugasPkgPage = function () {
    return {
        showModal: false,
        showTeksModal: false,
        showKlikModal: false,
        selectedUrl: '',
        selectedName: '',
        selectedDescription: '',
        targetTeks: '',
        targetKlik: 0,
        currentKlik: 0,
        clickHistory: [],
        savedKliks: {},
        currentKarakterId: '',
        totalPoin: 0,
        deskripsiKlik: '',
        showPhotoProofUpload: false,
        showVoiceProofUpload: false,
        photoProofBonusPoints: 0,
        voiceProofBonusPoints: 0,
        photoProofInstruction: '',
        voiceNoteInstruction: '',
        selectedPhotoProofName: '',
        selectedVoiceProofName: '',
        selectedPhotoProofPreviewUrl: '',
        selectedVoiceProofPreviewUrl: '',
        selectedPhotoProofSizeBytes: 0,
        selectedPhotoProofSizeLabel: '',
        selectedVoiceProofSizeBytes: 0,
        selectedVoiceProofSizeLabel: '',
        selectedVoiceProofDurationSeconds: 0,
        selectedVoiceProofDurationLabel: '',
        isPhotoCameraActive: false,
        photoCameraForm: null,
        photoCameraStream: null,
        isRecordingVoice: false,
        isVoiceRecordingPaused: false,
        recordingVoiceSeconds: 0,
        recordingVoiceDurationLabel: '0 detik',
        recordingVoiceForm: null,
        recordingVoiceStream: null,
        recordingVoiceChunks: [],
        recordingVoiceMimeType: '',
        mediaRecorderInstance: null,
        recordingVoiceTimer: null,
        discardVoiceRecordingOnStop: false,
        proofRequirement: 'optional',
        voiceNoteMaxSeconds: 0,
        mediaPermissionStatus: 'checking',
        mediaPermissionMessage: 'Memeriksa izin kamera dan mikrofon...',
        mediaPermissionDismissed: false,
        showMediaPermissionHelp: false,
        cameraPermissionState: 'unknown',
        microphonePermissionState: 'unknown',
        async initMediaPermissionGate() {
            await this.refreshMediaPermissionState();

            if (this.mediaPermissionStatus === 'prompt') {
                window.setTimeout(() => this.requestMicrophonePermission(false), 350);
            }
        },
        async queryBrowserPermission(name) {
            if (!navigator.permissions || typeof navigator.permissions.query !== 'function') {
                return 'unknown';
            }

            try {
                const status = await navigator.permissions.query({ name });
                if (status && typeof status.onchange !== 'undefined') {
                    status.onchange = () => this.refreshMediaPermissionState();
                }

                return status.state || 'unknown';
            } catch (error) {
                return 'unknown';
            }
        },
        async refreshMediaPermissionState() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || !window.isSecureContext) {
                this.mediaPermissionStatus = 'blocked';
                this.mediaPermissionMessage = 'Browser belum bisa meminta akses kamera dan mikrofon dari halaman ini. Buka lewat HTTPS atau gunakan browser terbaru.';
                return;
            }

            const [camera, microphone] = await Promise.all([
                this.queryBrowserPermission('camera'),
                this.queryBrowserPermission('microphone'),
            ]);

            this.cameraPermissionState = camera;
            this.microphonePermissionState = microphone;

            if (microphone === 'granted') {
                this.mediaPermissionStatus = 'granted';
                this.mediaPermissionMessage = camera === 'granted'
                    ? 'Kamera dan mikrofon sudah aktif.'
                    : 'Mikrofon sudah aktif. Kamera akan diminta saat kamu memakai Ambil Foto.';
                return;
            }

            if (microphone === 'denied') {
                this.mediaPermissionStatus = 'blocked';
                this.mediaPermissionMessage = 'Izin mikrofon masih diblokir. Ubah izin situs di browser, lalu coba lagi.';
                return;
            }

            this.mediaPermissionStatus = 'prompt';
            this.mediaPermissionMessage = 'Klik Aktifkan Mikrofon atau tombol Rekam agar browser menampilkan permintaan izin voice note.';
        },
        setMediaPermissionError(error, scope = 'media') {
            const errorName = error && typeof error === 'object' ? error.name : '';
            const permissionLabel = scope === 'camera'
                ? 'kamera'
                : (scope === 'microphone' ? 'mikrofon' : 'kamera atau mikrofon');
            const settingsLabel = scope === 'camera'
                ? 'Kamera'
                : (scope === 'microphone' ? 'Mikrofon' : 'Kamera dan Mikrofon');
            const deviceLabel = scope === 'camera'
                ? 'kamera'
                : (scope === 'microphone' ? 'mikrofon' : 'kamera atau mikrofon');

            if (errorName === 'NotAllowedError' || errorName === 'PermissionDeniedError' || errorName === 'SecurityError') {
                this.mediaPermissionStatus = 'blocked';
                this.mediaPermissionMessage = `Izin ${permissionLabel} ditolak. Buka pengaturan situs di browser, izinkan ${settingsLabel}, lalu coba lagi.`;
                this.showMediaPermissionHelp = true;
                return;
            }

            if (errorName === 'NotFoundError' || errorName === 'DevicesNotFoundError') {
                this.mediaPermissionStatus = 'blocked';
                this.mediaPermissionMessage = `Perangkat ${deviceLabel} tidak ditemukan. Periksa perangkat atau gunakan pilihan unggah file.`;
                return;
            }

            if (errorName === 'NotReadableError' || errorName === 'TrackStartError') {
                this.mediaPermissionStatus = 'blocked';
                this.mediaPermissionMessage = `${settingsLabel} sedang dipakai aplikasi lain. Tutup aplikasi lain, lalu coba lagi.`;
                return;
            }

            this.mediaPermissionStatus = 'prompt';
            this.mediaPermissionMessage = `${settingsLabel} belum bisa dibuka. Coba aktifkan ulang atau gunakan pilihan unggah file.`;
        },
        async requestMicrophonePermission(manual = true) {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || !window.isSecureContext) {
                await this.refreshMediaPermissionState();
                return;
            }

            this.mediaPermissionStatus = 'checking';
            this.mediaPermissionMessage = 'Meminta izin mikrofon...';

            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                stream.getTracks().forEach((track) => track.stop());

                this.microphonePermissionState = 'granted';
                this.mediaPermissionStatus = 'granted';
                this.mediaPermissionMessage = 'Mikrofon sudah aktif. Kamu bisa merekam voice note dari tombol Rekam.';
                this.showMediaPermissionHelp = false;

                if (manual) {
                    this.notify('Mikrofon sudah aktif.', 'success');
                }
            } catch (error) {
                console.error('Microphone permission error:', error);
                const errorName = error && typeof error === 'object' ? error.name : '';

                if (!manual && (errorName === 'NotAllowedError' || errorName === 'PermissionDeniedError' || errorName === 'SecurityError')) {
                    this.mediaPermissionStatus = 'prompt';
                    this.mediaPermissionMessage = 'Browser belum menampilkan izin otomatis. Klik Aktifkan Mikrofon atau Rekam agar permintaan izin muncul.';
                    return;
                }

                this.setMediaPermissionError(error, 'microphone');

                if (manual) {
                    this.notify(this.mediaPermissionMessage, 'warning');
                }
            }
        },
        async requestMediaPermissions(manual = true) {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || !window.isSecureContext) {
                await this.refreshMediaPermissionState();
                return;
            }

            this.mediaPermissionStatus = 'checking';
            this.mediaPermissionMessage = 'Meminta izin kamera dan mikrofon...';

            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: true,
                    audio: true,
                });

                stream.getTracks().forEach((track) => track.stop());
                this.mediaPermissionStatus = 'granted';
                this.mediaPermissionMessage = 'Kamera dan mikrofon sudah aktif.';
                this.cameraPermissionState = 'granted';
                this.microphonePermissionState = 'granted';
                this.showMediaPermissionHelp = false;

                if (manual) {
                    this.notify('Kamera dan mikrofon sudah aktif.', 'success');
                }
            } catch (error) {
                console.error('Media permission error:', error);
                this.setMediaPermissionError(error, 'media');

                if (manual) {
                    this.notify(this.mediaPermissionMessage, 'warning');
                }
            }
        },
        resetProofSelections() {
            this.stopVoiceRecordingSession(true);
            this.stopPhotoCameraSession(true);

            if (this.selectedPhotoProofPreviewUrl) {
                URL.revokeObjectURL(this.selectedPhotoProofPreviewUrl);
            }
            if (this.selectedVoiceProofPreviewUrl) {
                URL.revokeObjectURL(this.selectedVoiceProofPreviewUrl);
            }

            this.selectedPhotoProofName = '';
            this.selectedVoiceProofName = '';
            this.selectedPhotoProofPreviewUrl = '';
            this.selectedVoiceProofPreviewUrl = '';
            this.selectedPhotoProofSizeBytes = 0;
            this.selectedPhotoProofSizeLabel = '';
            this.selectedVoiceProofSizeBytes = 0;
            this.selectedVoiceProofSizeLabel = '';
            this.selectedVoiceProofDurationSeconds = 0;
            this.selectedVoiceProofDurationLabel = '';

            const root = this.$root || document;
            root.querySelectorAll('[name="proof_image"], [name="proof_voice_note"], [name="proof_voice_note_duration_seconds"]').forEach((input) => {
                input.value = '';
            });
        },
        setProofOptions(allowsPhotoProof, photoProofBonus, photoInstruction, allowsVoiceProof, voiceProofBonus, voiceInstruction, proofRequirement, voiceNoteMaxSeconds) {
            this.showPhotoProofUpload = allowsPhotoProof === '1';
            this.showVoiceProofUpload = allowsVoiceProof === '1';
            this.photoProofBonusPoints = parseInt(photoProofBonus, 10) || 0;
            this.voiceProofBonusPoints = parseInt(voiceProofBonus, 10) || 0;
            this.photoProofInstruction = photoInstruction || '';
            this.voiceNoteInstruction = voiceInstruction || '';
            this.proofRequirement = proofRequirement || 'optional';
            this.voiceNoteMaxSeconds = parseInt(voiceNoteMaxSeconds, 10) || 0;
        },
        notify(message, tone = 'warning') {
            if (window.showNotification) {
                window.showNotification(message, tone);
                return;
            }
            window.alert(message);
        },
        formatFileSize(bytes) {
            const size = Number(bytes) || 0;
            if (size < 1024) {
                return size + ' B';
            }
            if (size < 1024 * 1024) {
                return (size / 1024).toFixed(1).replace('.0', '') + ' KB';
            }
            return (size / (1024 * 1024)).toFixed(1).replace('.0', '') + ' MB';
        },
        formatDuration(totalSeconds) {
            const seconds = Math.max(0, Math.ceil(Number(totalSeconds) || 0));
            const minutes = Math.floor(seconds / 60);
            const remainder = seconds % 60;
            return minutes > 0
                ? `${minutes}:${remainder.toString().padStart(2, '0')}`
                : `${remainder} detik`;
        },
        isLikelyMobileDevice() {
            return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent || '');
        },
        canUseBrowserPhotoCamera() {
            return !!(
                window.isSecureContext
                && navigator.mediaDevices
                && navigator.mediaDevices.getUserMedia
            );
        },
        async startPhotoCapture(event) {
            const form = this.resolveActiveForm(event);
            this.photoCameraForm = form;

            if (this.isLikelyMobileDevice()) {
                this.openProofPicker(event, 'proof_image', 'camera');
                return;
            }

            if (!this.canUseBrowserPhotoCamera()) {
                this.openProofPicker(event, 'proof_image', 'camera');
                return;
            }

            this.stopPhotoCameraSession(true);

            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: { ideal: 'environment' },
                    },
                    audio: false,
                });

                this.photoCameraForm = form;
                this.photoCameraStream = stream;
                this.isPhotoCameraActive = true;
                this.cameraPermissionState = 'granted';
                if (this.microphonePermissionState === 'granted') {
                    this.mediaPermissionStatus = 'granted';
                    this.mediaPermissionMessage = 'Kamera dan mikrofon sudah aktif.';
                }

                requestAnimationFrame(() => {
                    const activeForm = this.resolveActiveForm();
                    const video = activeForm ? activeForm.querySelector('[data-photo-camera-video]') : null;
                    if (video) {
                        video.srcObject = stream;
                        video.play().catch(() => {});
                    }
                });
            } catch (error) {
                console.error('Photo camera error:', error);
                this.setMediaPermissionError(error, 'camera');
                this.notify('Kamera belum bisa dibuka langsung. Gunakan Pilih dari Galeri atau cek izin kamera browser.', 'warning');
                this.openProofPicker(event, 'proof_image', 'camera');
            }
        },
        stopPhotoCameraSession(clearForm = false) {
            if (this.photoCameraStream) {
                this.photoCameraStream.getTracks().forEach((track) => track.stop());
                this.photoCameraStream = null;
            }

            const activeForm = this.resolveActiveForm();
            const video = activeForm ? activeForm.querySelector('[data-photo-camera-video]') : null;
            if (video) {
                video.pause();
                video.srcObject = null;
            }

            this.isPhotoCameraActive = false;
            if (clearForm) {
                this.photoCameraForm = null;
            }
        },
        capturePhotoFromCamera() {
            const form = this.resolveActiveForm();
            const video = form ? form.querySelector('[data-photo-camera-video]') : null;
            const input = form ? form.querySelector('[name="proof_image"]') : null;

            if (!video || !input || !video.videoWidth || !video.videoHeight) {
                this.notify('Kamera belum siap. Tunggu sebentar lalu coba lagi.', 'warning');
                return;
            }

            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;

            const context = canvas.getContext('2d');
            if (!context) {
                this.notify('Gagal menyiapkan hasil foto.', 'warning');
                return;
            }

            context.drawImage(video, 0, 0, canvas.width, canvas.height);

            canvas.toBlob(async (blob) => {
                if (!blob) {
                    this.notify('Foto tidak berhasil diambil. Coba lagi.', 'warning');
                    return;
                }

                const file = new File([blob], `bukti-foto-${Date.now()}.jpg`, {
                    type: 'image/jpeg',
                    lastModified: Date.now(),
                });
                const transfer = new DataTransfer();
                transfer.items.add(file);
                input.files = transfer.files;

                await this.handleProofFileChange({ target: input }, 'photo');
                this.stopPhotoCameraSession(true);
            }, 'image/jpeg', 0.92);
        },
        resetVoiceRecordingState() {
            this.isRecordingVoice = false;
            this.isVoiceRecordingPaused = false;
            this.recordingVoiceSeconds = 0;
            this.recordingVoiceDurationLabel = '0 detik';
            this.recordingVoiceForm = null;
            this.recordingVoiceChunks = [];
            this.recordingVoiceMimeType = '';
            this.mediaRecorderInstance = null;
            this.discardVoiceRecordingOnStop = false;

            this.stopVoiceRecordingTimer();

            if (this.recordingVoiceStream) {
                this.recordingVoiceStream.getTracks().forEach((track) => track.stop());
                this.recordingVoiceStream = null;
            }
        },
        startVoiceRecordingTimer() {
            this.stopVoiceRecordingTimer();
            this.recordingVoiceTimer = setInterval(() => {
                this.recordingVoiceSeconds += 1;
                this.recordingVoiceDurationLabel = this.formatDuration(this.recordingVoiceSeconds);
            }, 1000);
        },
        stopVoiceRecordingTimer() {
            if (this.recordingVoiceTimer) {
                clearInterval(this.recordingVoiceTimer);
                this.recordingVoiceTimer = null;
            }
        },
        getPreferredVoiceMimeType() {
            return 'audio/webm';
        },
        getVoiceRecordExtension(mimeType) {
            return 'webm';
        },
        canUseBrowserVoiceRecorder() {
            return !!(
                window.isSecureContext
                && navigator.mediaDevices
                && navigator.mediaDevices.getUserMedia
                && typeof MediaRecorder !== 'undefined'
            );
        },
        getVoiceRecorderUnavailableMessage() {
            if (!window.isSecureContext) {
                return 'Rekam voice note langsung membutuhkan HTTPS atau localhost/127.0.0.1.';
            }

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                return 'Browser ini belum mendukung akses mikrofon langsung. Gunakan Chrome terbaru atau tombol Pilih File Audio.';
            }

            if (typeof MediaRecorder === 'undefined') {
                return 'Browser ini belum mendukung perekam voice note langsung. Gunakan Chrome terbaru atau tombol Pilih File Audio.';
            }

            return 'Perekam voice note langsung belum tersedia di browser ini. Gunakan tombol Pilih File Audio.';
        },
        fallbackToDeviceVoiceRecorder(event, reason = '') {
            if (!this.isLikelyMobileDevice()) {
                const message = reason === 'permission'
                    ? 'Izin mikrofon browser ditolak. Izinkan mikrofon di pengaturan situs browser, lalu klik Rekam lagi.'
                    : this.getVoiceRecorderUnavailableMessage();

                this.mediaPermissionStatus = reason === 'permission' ? 'blocked' : 'prompt';
                this.mediaPermissionMessage = message;
                this.showMediaPermissionHelp = reason === 'permission';
                this.notify(message, 'warning');
                return;
            }

            this.openProofPicker(event, 'proof_voice_note', 'record');

            if (reason === 'permission') {
                this.notify('Izin mikrofon browser ditolak. Membuka perekam audio bawaan perangkat sebagai alternatif.', 'warning');
                return;
            }

            if (reason === 'unsupported') {
                this.notify('Perekam langsung browser tidak tersedia. Membuka perekam audio bawaan perangkat.', 'warning');
                return;
            }

            this.notify('Membuka perekam audio bawaan perangkat sebagai alternatif.', 'warning');
        },
        stopVoiceRecordingSession(discard = false) {
            this.discardVoiceRecordingOnStop = discard;

            if (this.mediaRecorderInstance && this.mediaRecorderInstance.state !== 'inactive') {
                this.mediaRecorderInstance.stop();
                return;
            }

            this.resetVoiceRecordingState();
        },
        pauseVoiceRecording() {
            if (!this.mediaRecorderInstance || this.mediaRecorderInstance.state !== 'recording') {
                return;
            }

            if (typeof this.mediaRecorderInstance.pause !== 'function') {
                this.notify('Pause rekaman belum didukung di browser ini.', 'warning');
                return;
            }

            this.mediaRecorderInstance.pause();
            this.isVoiceRecordingPaused = true;
            this.stopVoiceRecordingTimer();
        },
        toggleVoicePause() {
            if (!this.isRecordingVoice) {
                return;
            }

            if (this.isVoiceRecordingPaused) {
                this.resumeVoiceRecording();
                return;
            }

            this.pauseVoiceRecording();
        },
        resumeVoiceRecording() {
            if (!this.mediaRecorderInstance || this.mediaRecorderInstance.state !== 'paused') {
                return;
            }

            if (typeof this.mediaRecorderInstance.resume !== 'function') {
                this.notify('Lanjutkan rekaman belum didukung di browser ini.', 'warning');
                return;
            }

            this.mediaRecorderInstance.resume();
            this.isVoiceRecordingPaused = false;
            this.startVoiceRecordingTimer();
        },
        async toggleVoiceRecording(event) {
            if (this.isRecordingVoice) {
                this.stopVoiceRecording();
                return;
            }

            if (!this.canUseBrowserVoiceRecorder()) {
                this.fallbackToDeviceVoiceRecorder(event, 'unsupported');
                return;
            }

            await this.startVoiceRecording(event);
        },
        async startVoiceRecording(event) {
            if (!this.canUseBrowserVoiceRecorder()) {
                this.fallbackToDeviceVoiceRecorder(event, 'unsupported');
                return;
            }

            this.stopVoiceRecordingSession(true);

            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                const recorder = new MediaRecorder(stream);
                this.microphonePermissionState = 'granted';
                this.mediaPermissionStatus = 'granted';
                this.mediaPermissionMessage = this.cameraPermissionState === 'granted'
                    ? 'Kamera dan mikrofon sudah aktif.'
                    : 'Mikrofon sudah aktif. Kamu bisa merekam voice note.';

                this.recordingVoiceForm = this.resolveActiveForm(event);
                this.recordingVoiceStream = stream;
                this.recordingVoiceChunks = [];
                this.recordingVoiceMimeType = recorder.mimeType || this.getPreferredVoiceMimeType();
                this.mediaRecorderInstance = recorder;
                this.isRecordingVoice = true;
                this.isVoiceRecordingPaused = false;
                this.recordingVoiceSeconds = 0;
                this.recordingVoiceDurationLabel = '0 detik';
                this.startVoiceRecordingTimer();

                recorder.ondataavailable = (mediaEvent) => {
                    if (mediaEvent.data && mediaEvent.data.size > 0) {
                        this.recordingVoiceChunks.push(mediaEvent.data);
                    }
                };

                recorder.onerror = () => {
                    this.notify('Rekam voice note gagal. Coba lagi atau gunakan pilih file audio.', 'warning');
                    this.resetVoiceRecordingState();
                };

                recorder.onpause = () => {
                    this.isVoiceRecordingPaused = true;
                    this.stopVoiceRecordingTimer();
                };

                recorder.onresume = () => {
                    this.isVoiceRecordingPaused = false;
                    this.startVoiceRecordingTimer();
                };

                recorder.onstop = async () => {
                    const shouldDiscard = this.discardVoiceRecordingOnStop;
                    const form = this.resolveActiveForm();
                    const input = form ? form.querySelector('[name="proof_voice_note"]') : null;

                    if (!shouldDiscard && input && this.recordingVoiceChunks.length > 0) {
                        const blob = new Blob(this.recordingVoiceChunks, { type: this.recordingVoiceMimeType || 'audio/webm' });
                        const extension = this.getVoiceRecordExtension(this.recordingVoiceMimeType || 'audio/webm');
                        const file = new File([blob], `voice-note-${Date.now()}.${extension}`, {
                            type: this.recordingVoiceMimeType || 'audio/webm',
                            lastModified: Date.now(),
                        });
                        const transfer = new DataTransfer();
                        transfer.items.add(file);
                        input.files = transfer.files;
                        await this.handleProofFileChange({ target: input }, 'voice');
                    }

                    this.resetVoiceRecordingState();
                };

                recorder.start();
            } catch (error) {
                const errorName = error && typeof error === 'object' ? error.name : '';
                const errorMessage = error && typeof error === 'object' && 'message' in error ? String(error.message || '') : '';
                console.error('Voice note recorder error:', error);
                this.setMediaPermissionError(error, 'microphone');

                if (errorName === 'NotAllowedError' || errorName === 'PermissionDeniedError' || errorName === 'SecurityError') {
                    this.fallbackToDeviceVoiceRecorder(event, 'permission');
                } else if (errorName === 'NotReadableError' || errorName === 'TrackStartError') {
                    this.notify('Mikrofon sedang dipakai aplikasi lain. Tutup aplikasi yang memakai mikrofon lalu coba lagi.', 'warning');
                } else {
                    this.notify('Rekam voice note belum bisa dimulai.' + (errorMessage ? ' ' + errorMessage : ' Gunakan tombol Pilih File Audio atau coba muat ulang halaman.'), 'warning');
                }

                this.resetVoiceRecordingState();
            }
        },
        stopVoiceRecording() {
            if (!this.mediaRecorderInstance || this.mediaRecorderInstance.state === 'inactive') {
                this.resetVoiceRecordingState();
                return;
            }

            this.mediaRecorderInstance.stop();
        },
        hasPhotoProof() {
            return !!this.selectedPhotoProofName;
        },
        hasVoiceProof() {
            return !!this.selectedVoiceProofName;
        },
        proofRequirementMissing() {
            return this.proofRequirement === 'required_any' && !this.hasPhotoProof() && !this.hasVoiceProof();
        },
        isVoiceDurationInvalid() {
            return this.hasVoiceProof()
                && this.voiceNoteMaxSeconds > 0
                && this.selectedVoiceProofDurationSeconds > this.voiceNoteMaxSeconds;
        },
        hasLargePhotoWarning() {
            return this.selectedPhotoProofSizeBytes > (5 * 1024 * 1024);
        },
        canSubmitProof() {
            return !this.proofRequirementMissing() && !this.isVoiceDurationInvalid();
        },
        proofStatusTone() {
            if (this.isVoiceDurationInvalid() || this.proofRequirementMissing()) {
                return 'warning';
            }
            return 'success';
        },
        proofStatusMessage() {
            if (this.proofRequirementMissing()) {
                return 'Minimal satu bukti wajib dipilih sebelum tugas dikirim.';
            }
            if (this.isVoiceDurationInvalid()) {
                return 'Durasi voice note melewati batas tugas. Ganti atau hapus voice note ini.';
            }
            if (this.hasPhotoProof() || this.hasVoiceProof()) {
                return 'Bukti sudah valid dan siap dikirim.';
            }
            return 'Tugas siap dikirim. Bukti tambahan masih opsional.';
        },
        resolveActiveForm(event = null) {
            const trigger = event?.currentTarget || event?.target || document.activeElement;
            if (trigger && typeof trigger.closest === 'function') {
                const triggerForm = trigger.closest('form');
                if (triggerForm) {
                    return triggerForm;
                }
            }

            if (this.recordingVoiceForm) {
                return this.recordingVoiceForm;
            }

            return this.showKlikModal
                ? document.getElementById('zikrForm')
                : document.querySelector('form[action="' + this.selectedUrl + '"]');
        },
        openProofPicker(event, fieldName, mode = 'library') {
            const form = this.resolveActiveForm(event);
            const input = form ? form.querySelector(`[name="${fieldName}"]`) : null;

            if (input) {
                if (fieldName === 'proof_image') {
                    if (mode === 'camera') {
                        input.setAttribute('capture', 'environment');
                    } else {
                        input.removeAttribute('capture');
                    }
                } else if (fieldName === 'proof_voice_note') {
                    if (mode === 'record') {
                        input.setAttribute('capture', 'user');
                    } else {
                        input.removeAttribute('capture');
                    }
                }

                input.click();
            }
        },
        clearProofFile(event, type) {
            const form = this.resolveActiveForm(event);
            const fieldName = type === 'photo' ? 'proof_image' : 'proof_voice_note';
            const input = form ? form.querySelector(`[name="${fieldName}"]`) : null;
            const durationInput = form ? form.querySelector('[name="proof_voice_note_duration_seconds"]') : null;

            if (input) {
                input.value = '';
            }
            if (durationInput && type === 'voice') {
                durationInput.value = '';
            }

            if (type === 'photo') {
                if (this.selectedPhotoProofPreviewUrl) {
                    URL.revokeObjectURL(this.selectedPhotoProofPreviewUrl);
                }
                this.selectedPhotoProofName = '';
                this.selectedPhotoProofPreviewUrl = '';
                this.selectedPhotoProofSizeBytes = 0;
                this.selectedPhotoProofSizeLabel = '';
                return;
            }

            if (this.selectedVoiceProofPreviewUrl) {
                URL.revokeObjectURL(this.selectedVoiceProofPreviewUrl);
            }
            this.selectedVoiceProofName = '';
            this.selectedVoiceProofPreviewUrl = '';
            this.selectedVoiceProofSizeBytes = 0;
            this.selectedVoiceProofSizeLabel = '';
            this.selectedVoiceProofDurationSeconds = 0;
            this.selectedVoiceProofDurationLabel = '';
        },
        async handleProofFileChange(event, type) {
            const file = event.target.files && event.target.files[0] ? event.target.files[0] : null;
            const fileName = file ? file.name : '';
            const durationInput = event.target.form ? event.target.form.querySelector('[name="proof_voice_note_duration_seconds"]') : null;

            if (type === 'photo') {
                if (this.selectedPhotoProofPreviewUrl) {
                    URL.revokeObjectURL(this.selectedPhotoProofPreviewUrl);
                }
                this.selectedPhotoProofName = fileName;
                this.selectedPhotoProofPreviewUrl = file ? URL.createObjectURL(file) : '';
                this.selectedPhotoProofSizeBytes = file ? file.size : 0;
                this.selectedPhotoProofSizeLabel = file ? this.formatFileSize(file.size) : '';
                return;
            }

            if (this.selectedVoiceProofPreviewUrl) {
                URL.revokeObjectURL(this.selectedVoiceProofPreviewUrl);
            }
            this.selectedVoiceProofName = fileName;
            this.selectedVoiceProofPreviewUrl = file ? URL.createObjectURL(file) : '';
            this.selectedVoiceProofSizeBytes = file ? file.size : 0;
            this.selectedVoiceProofSizeLabel = file ? this.formatFileSize(file.size) : '';
            this.selectedVoiceProofDurationSeconds = 0;
            this.selectedVoiceProofDurationLabel = '';

            if (!file) {
                if (durationInput) {
                    durationInput.value = '';
                }
                return;
            }

            try {
                const duration = await this.resolveAudioDuration(file);
                if (durationInput) {
                    durationInput.value = Math.ceil(duration);
                }
                this.selectedVoiceProofDurationSeconds = Math.ceil(duration);
                this.selectedVoiceProofDurationLabel = this.formatDuration(duration);
            } catch (_error) {
                if (durationInput) {
                    durationInput.value = '';
                }
                this.selectedVoiceProofDurationSeconds = 0;
            }
        },
        resolveAudioDuration(file) {
            return new Promise((resolve, reject) => {
                const audio = document.createElement('audio');
                const objectUrl = URL.createObjectURL(file);
                audio.preload = 'metadata';
                audio.src = objectUrl;
                audio.onloadedmetadata = () => {
                    const duration = audio.duration;
                    URL.revokeObjectURL(objectUrl);
                    resolve(duration);
                };
                audio.onerror = () => {
                    URL.revokeObjectURL(objectUrl);
                    reject(new Error('Durasi audio tidak dapat dibaca.'));
                };
            });
        },
        async submitTaskForm(event) {
            const form = event.target;
            const photoInput = form.querySelector('[name="proof_image"]');
            const voiceInput = form.querySelector('[name="proof_voice_note"]');
            const voiceDurationInput = form.querySelector('[name="proof_voice_note_duration_seconds"]');
            const hasPhoto = !!(photoInput && photoInput.files && photoInput.files.length > 0);
            const hasVoice = !!(voiceInput && voiceInput.files && voiceInput.files.length > 0);

            if (this.proofRequirement === 'required_any' && !hasPhoto && !hasVoice) {
                this.notify('Tugas ini mewajibkan minimal satu bukti berupa foto atau voice note.', 'warning');
                return;
            }

            if (hasVoice && this.voiceNoteMaxSeconds > 0) {
                try {
                    const duration = await this.resolveAudioDuration(voiceInput.files[0]);
                    if (!Number.isFinite(duration) || duration <= 0) {
                        this.notify('Durasi voice note tidak dapat dibaca. Gunakan file audio yang valid.', 'warning');
                        return;
                    }

                    const roundedDuration = Math.ceil(duration);
                    if (voiceDurationInput) {
                        voiceDurationInput.value = roundedDuration;
                    }

                    if (roundedDuration > this.voiceNoteMaxSeconds) {
                        this.notify('Durasi voice note melebihi batas tugas ini. Maksimal ' + this.voiceNoteMaxSeconds + ' detik.', 'warning');
                        return;
                    }
                } catch (error) {
                    this.notify(error.message || 'Voice note tidak bisa dibaca.', 'warning');
                    return;
                }
            }

            if (window.pkgPageLoader) {
                window.pkgPageLoader.show({
                    title: 'Mengunggah bukti...',
                    message: 'Tugas sedang dikirim.'
                });
            }

            try {
                sessionStorage.setItem('pkgActionSuccess', 'Bukti berhasil dikirim.');
            } catch (_error) {}

            form.submit();
        },
        openEvidenceModal(url, name, description, allowsPhotoProof, photoProofBonus, photoInstruction, allowsVoiceProof, voiceProofBonus, voiceInstruction, proofRequirement, voiceNoteMaxSeconds) {
            this.selectedUrl = url;
            this.selectedName = name;
            this.selectedDescription = description || '';
            this.resetProofSelections();
            this.setProofOptions(allowsPhotoProof, photoProofBonus, photoInstruction, allowsVoiceProof, voiceProofBonus, voiceInstruction, proofRequirement, voiceNoteMaxSeconds);
            this.showModal = true;
        },
        openTeksModal(url, name, target, allowsPhotoProof, photoProofBonus, photoInstruction, allowsVoiceProof, voiceProofBonus, voiceInstruction, proofRequirement, voiceNoteMaxSeconds) {
            this.selectedUrl = url;
            this.selectedName = name;
            this.targetTeks = target;
            this.resetProofSelections();
            this.setProofOptions(allowsPhotoProof, photoProofBonus, photoInstruction, allowsVoiceProof, voiceProofBonus, voiceInstruction, proofRequirement, voiceNoteMaxSeconds);
            this.showTeksModal = true;
        },
        openKlikModal(url, id, name, target, poin, deskripsi, allowsPhotoProof, photoProofBonus, photoInstruction, allowsVoiceProof, voiceProofBonus, voiceInstruction, proofRequirement, voiceNoteMaxSeconds) {
            this.selectedUrl = url;
            this.currentKarakterId = id;
            this.selectedName = name;
            this.targetKlik = parseInt(target, 10) || 0;
            this.totalPoin = parseInt(poin, 10) || 0;
            this.deskripsiKlik = deskripsi || '';
            this.resetProofSelections();
            this.setProofOptions(allowsPhotoProof, photoProofBonus, photoInstruction, allowsVoiceProof, voiceProofBonus, voiceInstruction, proofRequirement, voiceNoteMaxSeconds);
            if (this.savedKliks[id]) {
                this.currentKlik = this.savedKliks[id].count;
                this.clickHistory = [...this.savedKliks[id].history];
            } else {
                this.currentKlik = 0;
                this.clickHistory = [];
            }
            this.showKlikModal = true;
        },
        async closeKlikModal() {
            this.stopVoiceRecordingSession(true);
            this.stopPhotoCameraSession(true);
            if (this.currentKlik > 0 && this.currentKlik < this.targetKlik) {
                const confirmed = await window.showConfirmation('Hitungan belum selesai. Reset kembali ke 0?', {
                    title: 'Hitungan belum selesai',
                    confirmText: 'Reset hitungan',
                    cancelText: 'Simpan sementara',
                    tone: 'warning'
                });
                if (confirmed) {
                    delete this.savedKliks[this.currentKarakterId];
                    this.currentKlik = 0;
                    this.clickHistory = [];
                    this.showKlikModal = false;
                } else {
                    this.savedKliks[this.currentKarakterId] = { count: this.currentKlik, history: [...this.clickHistory] };
                    this.showKlikModal = false;
                }
            } else if (this.currentKlik >= this.targetKlik) {
                this.savedKliks[this.currentKarakterId] = { count: this.currentKlik, history: [...this.clickHistory] };
                this.showKlikModal = false;
            } else {
                this.showKlikModal = false;
            }
        },
        doClick() {
            if (this.currentKlik >= this.targetKlik) {
                return;
            }

            this.currentKlik++;

            if (this.currentKlik % 100 === 0 || this.currentKlik === this.targetKlik) {
                const now = new Date();
                const timeStr = now.getHours().toString().padStart(2, '0')
                    + ':' + now.getMinutes().toString().padStart(2, '0')
                    + ':' + now.getSeconds().toString().padStart(2, '0');
                this.clickHistory.push({ count: this.currentKlik, time: timeStr });
            }
        }
    };
};
</script>

@endsection
