@php
    $requiresProof = ($checklist->karakter->proof_requirement ?? 'optional') === 'required_any';
    $voiceLimit = (int) ($checklist->karakter->voice_note_max_seconds ?? 0);
    $voiceTooLong = $checklist->has_voice_note && $voiceLimit > 0 && (int) ($checklist->voice_note_duration_seconds ?? 0) > $voiceLimit;
    $missingRequiredProof = $requiresProof && ! $checklist->has_proof;
    $proofMeta = 'Diunggah: ' . ($checklist->checked_at?->isoFormat('D MMM YYYY HH:mm') ?? '-');
    $proofUnavailable = $checklist->proof_media_unavailable;
    $voiceUnavailable = $checklist->voice_note_media_unavailable;
    $ortuComments = $checklist->ortuComments ?? collect();
    $hasEvidenceDetails = $checklist->student_note
        || $checklist->hasil_teks
        || $checklist->has_photo_proof
        || $checklist->has_voice_note
        || ($checklist->click_history && is_array($checklist->click_history))
        || $ortuComments->isNotEmpty();
@endphp

<article class="pkg-card-soft border border-slate-200 p-4 dark:border-slate-700">
    <div class="flex items-start gap-3">
        <input
            type="checkbox"
            name="ids[]"
            value="{{ $checklist->id }}"
            x-model="selectedItems"
            class="mt-1 h-5 w-5 rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
        >

        <div class="min-w-0 flex-1 space-y-3">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="break-words text-sm font-semibold text-gray-900 dark:text-white">{{ $checklist->siswa->nama }}</p>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $checklist->siswa->nis }}</p>
                </div>

                <div class="shrink-0 text-right">
                    @if($checklist->isVerified())
                        <span class="pkg-status-badge pkg-status-success">Terverifikasi</span>
                        <p class="mt-1 max-w-28 truncate text-[11px] text-gray-500 dark:text-gray-400">
                            {{ $checklist->verifier->username ?? '-' }}
                        </p>
                    @else
                        <span class="pkg-status-badge pkg-status-warning">Menunggu</span>
                    @endif
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white/60 p-3 dark:border-slate-700 dark:bg-slate-950/20">
                <p class="text-[11px] font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Tugas PKG</p>
                <p class="mt-1 break-words text-sm font-semibold text-gray-900 dark:text-white">{{ $checklist->karakter->nama }}</p>
                <p class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-300">{{ Str::limit($checklist->karakter->deskripsi, 110) }}</p>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $checklist->checked_at->isoFormat('D MMM YYYY HH:mm') }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if($checklist->has_photo_proof)
                    <span class="pkg-status-badge {{ $proofUnavailable ? 'pkg-status-warning' : 'pkg-status-info' }}">{{ $proofUnavailable ? 'Foto tidak tersedia' : 'Foto' }}</span>
                @endif
                @if($checklist->has_voice_note)
                    <span class="pkg-status-badge {{ $voiceUnavailable ? 'pkg-status-warning' : 'pkg-status-neutral' }}">{{ $voiceUnavailable ? 'Voice note tidak tersedia' : 'Voice note' }}</span>
                @endif
                @if($missingRequiredProof)
                    <span class="pkg-status-badge pkg-status-warning">Bukti wajib belum ada</span>
                @elseif($voiceTooLong)
                    <span class="pkg-status-badge pkg-status-danger">Voice note melebihi batas</span>
                @elseif($checklist->has_proof)
                    <span class="pkg-status-badge pkg-status-success">Bukti valid</span>
                @else
                    <span class="pkg-status-badge pkg-status-neutral">Tanpa bukti</span>
                @endif
                @if(($checklist->proof_bonus_points ?? 0) > 0)
                    <span class="text-xs font-medium text-blue-600 dark:text-blue-400">+{{ $checklist->proof_bonus_points }} poin bukti</span>
                @endif
            </div>

            @if($hasEvidenceDetails)
            <details class="pkg-task-evidence overflow-hidden rounded-xl border border-slate-200 bg-white/70 dark:border-slate-700 dark:bg-slate-950/20">
                <summary class="flex min-h-11 cursor-pointer list-none items-center justify-between gap-3 px-3 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                    <span>Lihat jawaban dan bukti</span>
                    <svg class="h-4 w-4 shrink-0 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/>
                    </svg>
                </summary>
                <div class="space-y-3 border-t border-slate-200 p-3 dark:border-slate-700">
                @if($checklist->student_note)
                <div class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs leading-5 text-blue-800 dark:border-blue-700 dark:bg-blue-900/30 dark:text-blue-200">
                    <span class="font-semibold">Catatan:</span> {{ Str::limit($checklist->student_note, 140) }}
                </div>
                @endif

                @if($checklist->hasil_teks)
                <div class="rounded-lg border border-purple-200 bg-purple-50 px-3 py-2 text-xs leading-5 text-purple-800 dark:border-purple-700 dark:bg-purple-900/30 dark:text-purple-200">
                    <span class="font-semibold">Jawaban:</span> {{ Str::limit($checklist->hasil_teks, 180) }}
                </div>
                @endif

                @if($checklist->has_photo_proof && $checklist->proof_media_available)
                <div class="flex items-start gap-3 rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-900/20">
                    <a href="{{ $checklist->proof_url }}" target="_blank" rel="noopener" data-preview-src="{{ $checklist->proof_url }}" data-preview-alt="Bukti foto {{ $checklist->karakter->nama }}" data-preview-title="Bukti foto - {{ $checklist->siswa->nama }}" data-preview-filename="{{ basename($checklist->proof_path ?? 'bukti-foto.jpg') }}" data-preview-meta="{{ $proofMeta }}" onclick="return window.openImagePreviewFromLink ? window.openImagePreviewFromLink(this) : true;" class="block h-16 w-16 shrink-0 overflow-hidden rounded-lg border border-blue-200 bg-white dark:border-blue-800 dark:bg-slate-900">
                        <img src="{{ $checklist->proof_url }}" alt="Bukti foto {{ $checklist->karakter->nama }}" loading="lazy" class="h-full w-full object-cover">
                    </a>
                    <div class="min-w-0">
                        <a href="{{ $checklist->proof_url }}" target="_blank" rel="noopener" data-preview-src="{{ $checklist->proof_url }}" data-preview-alt="Bukti foto {{ $checklist->karakter->nama }}" data-preview-title="Bukti foto - {{ $checklist->siswa->nama }}" data-preview-filename="{{ basename($checklist->proof_path ?? 'bukti-foto.jpg') }}" data-preview-meta="{{ $proofMeta }}" onclick="return window.openImagePreviewFromLink ? window.openImagePreviewFromLink(this) : true;" class="text-xs font-semibold text-blue-700 underline underline-offset-2 dark:text-blue-300">
                            Lihat bukti foto
                        </a>
                        <p class="mt-1 text-xs text-blue-700/80 dark:text-blue-300/80">{{ $checklist->proof_compressed_size_kb ?? 0 }} KB</p>
                    </div>
                </div>
                @elseif($proofUnavailable)
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs leading-5 text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
                    File bukti foto tidak tersedia di storage server.
                    <span class="break-all font-mono">{{ $checklist->proof_path }}</span>
                </div>
                @endif

                @if($checklist->has_voice_note && $checklist->voice_note_media_available)
                <div class="rounded-lg border border-violet-200 bg-violet-50 p-3 dark:border-violet-800 dark:bg-violet-900/20">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-xs font-semibold text-violet-700 dark:text-violet-300">Voice note bukti</p>
                        <span class="text-xs text-violet-600 dark:text-violet-400">
                            {{ $checklist->voice_note_size_kb ?? 0 }} KB
                            @if($checklist->voice_note_duration_label)
                                | {{ $checklist->voice_note_duration_label }}
                            @endif
                        </span>
                    </div>
                    <audio controls preload="none" class="mt-2 w-full">
                        <source src="{{ $checklist->voice_note_url }}" type="{{ $checklist->voice_note_mime_type }}">
                    </audio>
                </div>
                @elseif($voiceUnavailable)
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs leading-5 text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
                    File voice note tidak tersedia di storage server.
                    <span class="break-all font-mono">{{ $checklist->voice_note_path }}</span>
                </div>
                @endif

                @if($checklist->click_history && is_array($checklist->click_history))
                @php
                    $histArray = $checklist->click_history;
                    $lastHist = end($histArray);
                @endphp
                <div class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs leading-5 text-indigo-800 dark:border-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-200">
                    <span class="font-semibold">Zikir:</span> {{ $lastHist['count'] ?? 0 }} klik tercatat
                    <button type="button" onclick="openZikrHistoryModal('{{ addslashes(json_encode($checklist->click_history)) }}')" class="ml-1 font-semibold underline underline-offset-2">Detail</button>
                </div>
                @endif

                @if($ortuComments->count() > 0)
                <div class="space-y-1">
                    @foreach($ortuComments as $oc)
                        <div class="rounded-lg border border-teal-200 bg-teal-50 px-3 py-2 text-xs leading-5 text-teal-800 dark:border-teal-700 dark:bg-teal-900/30 dark:text-teal-200">
                            <span class="font-semibold">Ortu:</span> {{ Str::limit($oc->comment, 100) }}
                            <span class="text-[10px] text-gray-500 dark:text-gray-400">{{ $oc->created_at->format('d/m H:i') }}</span>
                        </div>
                    @endforeach
                </div>
                @endif
                </div>
            </details>
            @endif

            <div class="grid grid-cols-2 gap-2">
                @if(!$checklist->isVerified())
                    <button type="button" onclick="openVerifyModal({{ $checklist->id }}, '{{ addslashes($checklist->siswa->nama) }}', '{{ addslashes($checklist->karakter->nama) }}')" class="btn-success col-span-2 justify-center px-3 py-2 text-sm">
                        Verifikasi
                    </button>
                @else
                    <button type="button" onclick="openUnverifyModal({{ $checklist->id }}, '{{ addslashes($checklist->siswa->nama) }}', '{{ addslashes($checklist->karakter->nama) }}', {{ $checklist->awarded_points ?? ($checklist->karakter->poin ?? 10) }})" class="pkg-btn-secondary col-span-2 justify-center px-3 py-2 text-sm">
                        Batal verifikasi
                    </button>
                @endif

                <a href="{{ route('pamong.chat.index', ['tab' => 'pribadi', 'siswa_id' => $checklist->siswa->id]) }}" class="pkg-btn-secondary justify-center px-3 py-2 text-sm">
                    Chat siswa
                </a>

                <button type="button" onclick="openDeleteModal({{ $checklist->id }}, '{{ addslashes($checklist->siswa->nama) }}', '{{ addslashes($checklist->karakter->nama) }}', {{ $checklist->isVerified() ? 'true' : 'false' }}, {{ $checklist->awarded_points ?? ($checklist->karakter->poin ?? 10) }})" class="btn-danger justify-center px-3 py-2 text-sm">
                    Tolak verifikasi
                </button>
            </div>
        </div>
    </div>
</article>
