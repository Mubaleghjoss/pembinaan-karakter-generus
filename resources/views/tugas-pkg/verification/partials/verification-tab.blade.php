<!-- Tab: Verifikasi Tugas PKG -->
<div x-show="activeTab === 'verification'" x-cloak x-data="verificationManager()">
    <!-- Stats -->
    @if(isset($stats))
    <div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-3">
        <div class="pkg-card-soft rounded-2xl p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-100 dark:bg-blue-900/30 rounded-lg p-3">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500 dark:text-gray-300">Total</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>
        
        <div class="pkg-card-soft rounded-2xl p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-100 dark:bg-green-900/30 rounded-lg p-3">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500 dark:text-gray-300">Terverifikasi</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['verified'] }}</p>
                </div>
            </div>
        </div>
        
        <div class="pkg-card-soft rounded-2xl p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg p-3">
                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500 dark:text-gray-300">Menunggu</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['unverified'] }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Filters -->
    <x-collapsible-section title="Filter verifikasi" description="Saring tugas berdasarkan status, siswa, bukti, dan tanggal." :open="request()->filled('siswa_id') || request()->filled('karakter_id') || request()->filled('proof_status') || request()->filled('date_from') || request()->filled('date_to') || request('status', 'unverified') !== 'unverified'" :compact="true" class="mb-6">
        <form id="filterForm" method="GET" action="{{ route('tugas-pkg.verification') }}" class="pkg-filter-grid grid-cols-1 md:grid-cols-6 gap-4">
            <input type="hidden" name="tab" value="verification">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Status</label>
                <select name="status" onchange="this.form.submit()" class="w-full px-3 py-2 pkg-field">
                    <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>Semua</option>
                    <option value="unverified" {{ request('status', 'unverified') === 'unverified' ? 'selected' : '' }}>Menunggu</option>
                    <option value="verified" {{ request('status') === 'verified' ? 'selected' : '' }}>Terverifikasi</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Siswa</label>
                <select name="siswa_id" onchange="this.form.submit()" class="w-full px-3 py-2 pkg-field">
                    <option value="">Semua Siswa</option>
                    @if(isset($siswaOptions))
                        @foreach($siswaOptions as $siswa)
                            <option value="{{ $siswa->id }}" {{ request('siswa_id') == $siswa->id ? 'selected' : '' }}>{{ $siswa->nama }} ({{ $siswa->nis }})</option>
                        @endforeach
                    @endif
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Tugas PKG</label>
                <select name="karakter_id" onchange="this.form.submit()" class="w-full px-3 py-2 pkg-field">
                    <option value="">Semua Tugas</option>
                    @if(isset($karakterOptions))
                        @foreach($karakterOptions as $karakter)
                            <option value="{{ $karakter->id }}" {{ request('karakter_id') == $karakter->id ? 'selected' : '' }}>{{ $karakter->nama }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Status Bukti</label>
                <select name="proof_status" onchange="this.form.submit()" class="w-full px-3 py-2 pkg-field">
                    <option value="">Semua Bukti</option>
                    <option value="valid" {{ request('proof_status') === 'valid' ? 'selected' : '' }}>Bukti Valid</option>
                    <option value="no_proof" {{ request('proof_status') === 'no_proof' ? 'selected' : '' }}>Tanpa Bukti</option>
                    <option value="required_missing" {{ request('proof_status') === 'required_missing' ? 'selected' : '' }}>Bukti Wajib Belum Ada</option>
                    <option value="voice_too_long" {{ request('proof_status') === 'voice_too_long' ? 'selected' : '' }}>Voice Note Terlalu Panjang</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" onchange="this.form.submit()" class="w-full px-3 py-2 pkg-field">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" onchange="this.form.submit()" class="w-full px-3 py-2 pkg-field">
            </div>
        </form>
    </x-collapsible-section>

    <!-- Checklist Table -->
    @if(isset($checklists))
    <div class="pkg-panel overflow-hidden relative">
        <!-- Manual refresh loading indicator -->
        <div x-show="refreshing" class="absolute top-0 left-0 w-full h-1 bg-blue-100 overflow-hidden">
            <div class="w-full h-full bg-blue-500 origin-left animate-progress"></div>
        </div>

        <div class="flex flex-col gap-3 border-b border-gray-200 p-4 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-gray-900 dark:text-white">Data verifikasi tidak dimuat ulang otomatis</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">
                    Gunakan tombol refresh untuk mengambil kiriman terbaru dari siswa tanpa halaman berpindah sendiri saat sedang memeriksa bukti.
                </p>
            </div>
            <button
                type="button"
                @click="manualRefresh()"
                :disabled="refreshing"
                class="btn-secondary inline-flex items-center justify-center gap-2 text-sm !px-4 !py-2 disabled:cursor-wait disabled:opacity-70"
            >
                <svg x-show="!refreshing" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6M5 19A9 9 0 0119 5m0 0h-5m5 0v5" />
                </svg>
                <svg x-show="refreshing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span x-text="refreshing ? 'Memuat...' : 'Refresh Data'"></span>
            </button>
        </div>

        <div>
            <form id="bulkForm" action="{{ route('tugas-pkg.verification.bulk-action') }}" method="POST" data-no-csrf-handler>
                @csrf
                <input type="hidden" name="action" id="bulkActionType">
                <input type="hidden" name="reason" id="bulkActionReason">
                <input type="hidden" name="notes" id="bulkActionNotes">

                <div class="space-y-3 p-3 lg:hidden">
                    @forelse($checklists as $checklist)
                        @include('tugas-pkg.verification.partials.mobile-checklist-card', ['checklist' => $checklist])
                    @empty
                        <div class="pkg-empty-state">
                            <h3 class="pkg-empty-title">Tidak ada data verifikasi</h3>
                            <p class="pkg-empty-copy">Ubah filter status, tanggal, siswa, atau tugas PKG untuk melihat hasil lain.</p>
                        </div>
                    @endforelse
                </div>

                <div class="hidden overflow-x-auto lg:block">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left">
                                <input type="checkbox" @change="toggleAll($event)" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Siswa</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Karakter</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($checklists as $checklist)
                        @php
                            $requiresProof = ($checklist->karakter->proof_requirement ?? 'optional') === 'required_any';
                            $voiceLimit = (int) ($checklist->karakter->voice_note_max_seconds ?? 0);
                            $voiceTooLong = $checklist->has_voice_note && $voiceLimit > 0 && (int) ($checklist->voice_note_duration_seconds ?? 0) > $voiceLimit;
                            $missingRequiredProof = $requiresProof && ! $checklist->has_proof;
                            $proofUnavailable = $checklist->proof_media_unavailable;
                            $voiceUnavailable = $checklist->voice_note_media_unavailable;
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" name="ids[]" value="{{ $checklist->id }}" x-model="selectedItems" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $checklist->siswa->nama }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-300">{{ $checklist->siswa->nis }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $checklist->karakter->nama }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-300">{{ Str::limit($checklist->karakter->deskripsi, 50) }}</div>

                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    @if($checklist->has_photo_proof)
                                        <span class="inline-flex items-center rounded-full border {{ $proofUnavailable ? 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300' : 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-300' }} px-2.5 py-1 text-[11px] font-medium">{{ $proofUnavailable ? 'Foto tidak tersedia' : 'Foto' }}</span>
                                    @endif
                                    @if($checklist->has_voice_note)
                                        <span class="inline-flex items-center rounded-full border {{ $voiceUnavailable ? 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300' : 'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-800 dark:bg-violet-900/20 dark:text-violet-300' }} px-2.5 py-1 text-[11px] font-medium">{{ $voiceUnavailable ? 'Voice note tidak tersedia' : 'Voice note' }}</span>
                                    @endif
                                    @if($missingRequiredProof)
                                        <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-[11px] font-medium text-amber-700 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300">Bukti wajib belum ada</span>
                                    @elseif($voiceTooLong)
                                        <span class="inline-flex items-center rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-[11px] font-medium text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">Voice note melebihi batas</span>
                                    @elseif($checklist->has_proof)
                                        <span class="inline-flex items-center rounded-full border border-green-200 bg-green-50 px-2.5 py-1 text-[11px] font-medium text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300">Bukti valid</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-[11px] font-medium text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Tanpa bukti</span>
                                    @endif
                                </div>
                                
                                {{-- Student Note / Evidence --}}
                                @if($checklist->student_note)
                                <div class="mt-1">
                                    <div class="text-xs bg-blue-50 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 px-2 py-1 rounded border border-blue-200 dark:border-blue-700 block">
                                        <span class="font-bold">Catatan:</span> {{ Str::limit($checklist->student_note, 100) }}
                                    </div>
                                </div>
                                @endif

                                {{-- Text Result --}}
                                @if($checklist->hasil_teks)
                                <div class="mt-1">
                                    <div class="text-xs bg-purple-50 dark:bg-purple-900/30 text-purple-800 dark:text-purple-200 px-2 py-1 rounded border border-purple-200 dark:border-purple-700 block">
                                        <span class="font-bold">Jawaban Teks:</span> {{ Str::limit($checklist->hasil_teks, 150) }}
                                    </div>
                                </div>
                                @endif

                                @if($checklist->has_photo_proof && $checklist->proof_media_available)
                                <div class="mt-2 flex items-start gap-3">
                                    <a href="{{ $checklist->proof_url }}" target="_blank" rel="noopener" data-preview-src="{{ $checklist->proof_url }}" data-preview-alt="Bukti foto {{ $checklist->karakter->nama }}" data-preview-title="Bukti foto - {{ $checklist->siswa->nama }}" data-preview-filename="{{ basename($checklist->proof_path ?? 'bukti-foto.jpg') }}" data-preview-meta="Diunggah: {{ $checklist->checked_at?->isoFormat('D MMM YYYY HH:mm') }}" onclick="return window.openImagePreviewFromLink ? window.openImagePreviewFromLink(this) : true;" class="block overflow-hidden rounded-xl border border-blue-200 bg-white shadow-sm transition hover:border-blue-300 hover:shadow dark:border-blue-800 dark:bg-slate-900">
                                        <img
                                            src="{{ $checklist->proof_url }}"
                                            alt="Bukti foto {{ $checklist->karakter->nama }}"
                                            loading="lazy"
                                            class="h-16 w-16 object-cover"
                                        >
                                    </a>
                                    <div class="min-w-0">
                                        <a href="{{ $checklist->proof_url }}" target="_blank" rel="noopener" data-preview-src="{{ $checklist->proof_url }}" data-preview-alt="Bukti foto {{ $checklist->karakter->nama }}" data-preview-title="Bukti foto - {{ $checklist->siswa->nama }}" data-preview-filename="{{ basename($checklist->proof_path ?? 'bukti-foto.jpg') }}" data-preview-meta="Diunggah: {{ $checklist->checked_at?->isoFormat('D MMM YYYY HH:mm') }}" onclick="return window.openImagePreviewFromLink ? window.openImagePreviewFromLink(this) : true;" class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-xs font-medium text-blue-700 transition hover:bg-blue-100 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                                            Lihat bukti foto
                                        </a>
                                        <span class="mt-2 block text-xs text-gray-500 dark:text-gray-400">
                                            {{ $checklist->proof_compressed_size_kb ?? 0 }} KB
                                        </span>
                                    </div>
                                </div>
                                @elseif($proofUnavailable)
                                <div class="mt-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
                                    File bukti foto tidak tersedia di storage server. Path: <span class="font-mono">{{ $checklist->proof_path }}</span>
                                </div>
                                @endif
                                @if($checklist->has_voice_note && $checklist->voice_note_media_available)
                                <div class="mt-2 rounded-xl border border-violet-200 bg-violet-50 p-3 dark:border-violet-800 dark:bg-violet-900/20">
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
                                    <a href="{{ $checklist->voice_note_url }}" target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-2 rounded-lg border border-violet-200 bg-white px-2.5 py-1.5 text-xs font-medium text-violet-700 transition hover:bg-violet-100 dark:border-violet-700 dark:bg-violet-950/40 dark:text-violet-300">
                                        Unduh voice note
                                    </a>
                                </div>
                                @elseif($voiceUnavailable)
                                <div class="mt-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
                                    File voice note tidak tersedia di storage server. Path: <span class="font-mono">{{ $checklist->voice_note_path }}</span>
                                </div>
                                @endif
                                @if(($checklist->proof_bonus_points ?? 0) > 0)
                                <div class="mt-2 text-xs text-blue-600 dark:text-blue-400">
                                    Bonus bukti +{{ $checklist->proof_bonus_points }} poin
                                </div>
                                @endif

                                {{-- Zikr Click History Summary --}}
                                @if($checklist->click_history && is_array($checklist->click_history))
                                @php 
                                    $histArray = $checklist->click_history;
                                    $lastHist = end($histArray);
                                @endphp
                                <div class="mt-1">
                                    <div class="text-xs bg-indigo-50 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-200 px-2 py-1 rounded border border-indigo-200 dark:border-indigo-700 block">
                                        <span class="font-bold">Zikir:</span> {{ $lastHist['count'] ?? 0 }} klik tercatat (Selesai pada {{ $lastHist['time'] ?? '-' }})
                                        <button type="button" onclick="openZikrHistoryModal('{{ addslashes(json_encode($checklist->click_history)) }}')" class="ml-2 font-medium underline text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">Lihat Detail</button>
                                    </div>
                                </div>
                                @endif

                                @php $ortuComments = $checklist->ortuComments ?? collect(); @endphp
                                @if($ortuComments->count() > 0)
                                <div class="mt-1">
                                    @foreach($ortuComments as $oc)
                                    <div class="text-xs bg-teal-50 dark:bg-teal-900/30 text-teal-800 dark:text-teal-200 px-2 py-1 rounded mt-1 border border-teal-200 dark:border-teal-700 block">
                                        <span class="font-bold">Ortu:</span> {{ Str::limit($oc->comment, 80) }}
                                        <span class="text-gray-500 dark:text-gray-400 ml-1 text-[10px]">{{ $oc->created_at->format('d/m H:i') }}</span>
                                    </div>
                                    @endforeach
                                </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                {{ $checklist->checked_at->isoFormat('D MMM YYYY HH:mm') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($checklist->isVerified())
                                <span class="pkg-status-badge pkg-status-success">
                                    Terverifikasi
                                </span>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    oleh {{ $checklist->verifier->username }}
                                </div>
                                @else
                                <span class="pkg-status-badge pkg-status-warning">
                                    Menunggu
                                </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="{{ route('pamong.chat.index', ['tab' => 'pribadi', 'siswa_id' => $checklist->siswa->id]) }}" class="mr-3 rounded-lg px-2 py-1 text-sky-600 transition hover:bg-sky-50 hover:text-sky-900 dark:text-sky-400 dark:hover:bg-sky-900/20 dark:hover:text-sky-300">
                                    Chat siswa ini
                                </a>
                                @if(!$checklist->isVerified())
                                <button type="button" onclick="openVerifyModal({{ $checklist->id }}, '{{ addslashes($checklist->siswa->nama) }}', '{{ addslashes($checklist->karakter->nama) }}')" class="mr-3 rounded-lg px-2 py-1 text-green-600 transition hover:bg-green-50 hover:text-green-900 dark:text-green-400 dark:hover:bg-green-900/20 dark:hover:text-green-300">
                                    Verifikasi
                                </button>
                                @else
                                <button type="button" onclick="openUnverifyModal({{ $checklist->id }}, '{{ addslashes($checklist->siswa->nama) }}', '{{ addslashes($checklist->karakter->nama) }}', {{ $checklist->awarded_points ?? ($checklist->karakter->poin ?? 10) }})" class="mr-3 rounded-lg px-2 py-1 text-yellow-600 transition hover:bg-yellow-50 hover:text-yellow-900 dark:text-yellow-400 dark:hover:bg-yellow-900/20 dark:hover:text-yellow-300">
                                    Batal Verifikasi
                                </button>
                                @endif
                                <button type="button" onclick="openDeleteModal({{ $checklist->id }}, '{{ addslashes($checklist->siswa->nama) }}', '{{ addslashes($checklist->karakter->nama) }}', {{ $checklist->isVerified() ? 'true' : 'false' }}, {{ $checklist->awarded_points ?? ($checklist->karakter->poin ?? 10) }})" class="rounded-lg px-2 py-1 font-medium text-red-600 transition hover:bg-red-50 hover:text-red-900 dark:text-red-400 dark:hover:bg-red-900/20 dark:hover:text-red-300">
                                    Tolak Verifikasi
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-0">
                                <div class="pkg-empty-state">
                                    <h3 class="pkg-empty-title">Tidak ada data verifikasi</h3>
                                    <p class="pkg-empty-copy">Ubah filter status, tanggal, siswa, atau tugas PKG untuk melihat hasil lain.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </form>
        </div>
    </div>

    @if($checklists instanceof \Illuminate\Contracts\Pagination\Paginator)
    <div class="mt-6">
        {{ $checklists->appends(request()->query())->links() }}
    </div>
    @endif

    <!-- Bulk Action Toolbar -->
    <div x-show="selectedItems.length > 0" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-y-full opacity-0"
         x-transition:enter-end="translate-y-0 opacity-100"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="translate-y-0 opacity-100"
         x-transition:leave-end="translate-y-full opacity-0"
         class="fixed inset-x-3 bottom-4 z-[9999] flex flex-wrap items-center justify-center gap-3 rounded-2xl border border-gray-200 bg-white px-3 py-3 shadow-2xl dark:border-gray-700 dark:bg-gray-800 sm:inset-x-auto sm:left-1/2 sm:bottom-8 sm:-translate-x-1/2 sm:flex-nowrap sm:rounded-full sm:px-6">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
            <span x-text="selectedItems.length"></span> terpilih
        </span>
        <div class="h-6 w-px bg-gray-300 dark:bg-gray-600"></div>
        <button @click="confirmBulkAction('verify')" class="text-green-600 hover:text-green-800 dark:text-green-400 font-medium text-sm flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            Verifikasi
        </button>
        <button @click="confirmBulkAction('unverify')" class="text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 font-medium text-sm flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Batal
        </button>
        <button @click="confirmBulkAction('destroy')" class="text-red-600 hover:text-red-800 dark:text-red-400 font-medium text-sm flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            Tolak
        </button>
    </div>
    @endif
</div>

<!-- Bulk Confirm Modal -->
<div id="bulkConfirmModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="pkg-modal p-6 max-w-md w-full mx-4">
        <h3 id="bulkModalTitle" class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Konfirmasi Aksi</h3>
        <p id="bulkModalText" class="text-sm text-gray-600 dark:text-gray-400 mb-4"></p>
        
        <div id="bulkReasonContainer" class="mb-4 hidden">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Alasan <span class="text-xs text-gray-400">(opsional)</span></label>
            <textarea id="bulkReasonInput" rows="2" class="w-full px-3 py-2 pkg-field" placeholder="Masukkan alasan (opsional)..."></textarea>
        </div>

        <div id="bulkNotesContainer" class="mb-4 hidden">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Catatan untuk semua <span class="text-xs text-gray-400">(opsional, 1 catatan untuk semua item)</span></label>
            <textarea id="bulkNotesInput" rows="2" class="w-full px-3 py-2 pkg-field" placeholder="Contoh: Baik, sudah dikerjakan dengan baik"></textarea>
        </div>

        <div class="flex justify-end space-x-3">
            <button type="button" onclick="closeVerificationModal('bulkConfirmModal')" class="pkg-btn-secondary px-4 py-2">
                Batal
            </button>
            <button onclick="submitBulkAction()" id="bulkConfirmBtn" class="pkg-btn-primary px-4 py-2">
                Ya, Lanjutkan
            </button>
        </div>
    </div>
</div>

<!-- Verify Modal -->
<div id="verifyModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="pkg-modal p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Verifikasi Karakter</h3>
        <form id="verifyForm" method="POST" data-no-csrf-handler data-show-loader data-loader-title="Memverifikasi tugas..." data-loader-message="Data verifikasi sedang disimpan.">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                    Siswa: <strong id="verifySiswaName"></strong><br>
                    Karakter: <strong id="verifyKarakterName"></strong>
                </p>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Catatan (Opsional)</label>
                <textarea name="notes" rows="3" class="w-full px-3 py-2 pkg-field" placeholder="Tambahkan catatan..."></textarea>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeVerificationModal('verifyModal')" class="pkg-btn-secondary px-4 py-2">
                    Batal
                </button>
                <button type="submit" class="btn-success px-4 py-2">
                    Verifikasi
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Unverify Modal (with mandatory reason) -->
<div id="unverifyModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="pkg-modal p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-yellow-600 dark:text-yellow-400 mb-4">Batal Verifikasi</h3>
        <form id="unverifyForm" method="POST" data-no-csrf-handler data-show-loader data-loader-title="Membatalkan verifikasi..." data-loader-message="Perubahan sedang disimpan.">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Siswa: <strong id="unverifySiswaName"></strong><br>
                    Karakter: <strong id="unverifyKarakterName"></strong>
                </p>
                <div class="mt-3 rounded-2xl border border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-900/20">
                    <p class="text-sm text-red-700 dark:text-red-300">
                        <strong>Poin akan dikurangi <span id="unverifyPoints"></span> poin</strong> dari siswa ini.
                    </p>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Alasan Pembatalan <span class="text-red-500">*</span></label>
                <textarea name="reason" id="unverifyReason" rows="3" required minlength="5" class="w-full px-3 py-2 pkg-field" placeholder="Masukkan alasan pembatalan verifikasi..."></textarea>
                <p class="text-xs text-gray-500 mt-1">Alasan akan tercatat di riwayat poin siswa</p>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeVerificationModal('unverifyModal')" class="pkg-btn-secondary px-4 py-2">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 rounded-lg bg-yellow-600 text-white transition hover:bg-yellow-700">
                    Ya, Batalkan Verifikasi
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Modal (with mandatory reason) -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="pkg-modal p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-red-600 dark:text-red-400 mb-4">Tolak Verifikasi Tugas</h3>
        <form id="deleteForm" method="POST" data-no-csrf-handler data-show-loader data-loader-title="Menolak verifikasi..." data-loader-message="Tugas sedang dipindahkan ke arsip.">
            @csrf
            @method('DELETE')
            <div class="mb-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Siswa: <strong id="deleteSiswaName"></strong><br>
                    Karakter: <strong id="deleteKarakterName"></strong>
                </p>
                <div id="deletePointsWarning" class="mt-3 hidden rounded-2xl border border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-900/20">
                    <p class="text-sm text-red-700 dark:text-red-300">
                        <strong>Data sudah terverifikasi.</strong> Poin sebesar <span id="deletePoints"></span> poin akan dikurangi dari siswa.
                    </p>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Alasan Penolakan <span class="text-red-500">*</span></label>
                <textarea name="reason" id="deleteReason" rows="3" required minlength="5" class="w-full px-3 py-2 pkg-field" placeholder="Masukkan alasan penolakan tugas..."></textarea>
                <p class="text-xs text-gray-500 mt-1">Alasan akan tercatat di riwayat poin siswa</p>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeVerificationModal('deleteModal')" class="pkg-btn-secondary px-4 py-2">
                    Batal
                </button>
                <button type="submit" class="btn-danger px-4 py-2">
                    Ya, Tolak Verifikasi
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Zikr History Modal -->
<div id="zikrHistoryModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="pkg-modal p-6 max-w-sm w-full mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Riwayat Klik Zikir</h3>
            <button type="button" onclick="closeVerificationModal('zikrHistoryModal')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="max-h-64 overflow-y-auto">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 sticky top-0">
                    <tr>
                        <th scope="col" class="px-6 py-2">Hitungan</th>
                        <th scope="col" class="px-6 py-2">Waktu</th>
                    </tr>
                </thead>
                <tbody id="zikrHistoryBody" class="divide-y divide-gray-200 dark:divide-gray-700">
                    <!-- Injected via JS -->
                </tbody>
            </table>
        </div>
        <div class="mt-4 flex justify-end">
            <button type="button" onclick="closeVerificationModal('zikrHistoryModal')" class="pkg-btn-secondary px-4 py-2">
                Tutup
            </button>
        </div>
    </div>
</div>

<style>
@keyframes progress {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}
.animate-progress {
    animation: progress 1.5s infinite linear;
}
</style>

@push('scripts')
<script>
function verificationManager() {
    return {
        selectedItems: [],
        refreshing: false,
        
        init() {
        },
        
        toggleAll(e) {
            if (e.target.checked) {
                this.selectedItems = [...new Set(Array.from(document.querySelectorAll('#bulkForm input[name="ids[]"]')).map(cb => cb.value))];
            } else {
                this.selectedItems = [];
            }
        },
        
        manualRefresh() {
            if (this.refreshing) return;

            this.refreshing = true;
            window.location.reload();
        },
        
        confirmBulkAction(type) {
            const modal = document.getElementById('bulkConfirmModal');
            const title = document.getElementById('bulkModalTitle');
            const text = document.getElementById('bulkModalText');
            const reasonContainer = document.getElementById('bulkReasonContainer');
            const btn = document.getElementById('bulkConfirmBtn');
            const actionInput = document.getElementById('bulkActionType');
            
            actionInput.value = type;
            reasonContainer.classList.add('hidden');
            document.getElementById('bulkNotesContainer').classList.add('hidden');
            
            if (type === 'verify') {
                title.textContent = 'Verifikasi ' + this.selectedItems.length + ' Item';
                text.textContent = 'Anda yakin ingin memverifikasi semua item yang dipilih? Poin akan diberikan otomatis.';
                document.getElementById('bulkNotesContainer').classList.remove('hidden');
                btn.className = 'btn-success px-4 py-2';
                btn.textContent = 'Ya, Verifikasi Semua';
            } else if (type === 'unverify') {
                title.textContent = 'Batal Verifikasi ' + this.selectedItems.length + ' Item';
                text.textContent = 'Anda yakin ingin membatalkan verifikasi item yang dipilih? Poin akan ditarik kembali.';
                reasonContainer.classList.remove('hidden');
                btn.className = 'px-4 py-2 rounded-lg bg-yellow-600 text-white transition hover:bg-yellow-700';
                btn.textContent = 'Ya, Batalkan Verifikasi';
            } else if (type === 'destroy') {
                title.textContent = 'Tolak Verifikasi ' + this.selectedItems.length + ' Item';
                text.textContent = 'Item yang ditolak akan dipindahkan ke arsip. Poin verifikasi yang sudah diberikan akan ditarik kembali.';
                reasonContainer.classList.remove('hidden');
                btn.className = 'btn-danger px-4 py-2';
                btn.textContent = 'Ya, Tolak Verifikasi';
            }
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }
}

function submitBulkAction() {
    const reasonInput = document.getElementById('bulkReasonInput');
    const reasonHidden = document.getElementById('bulkActionReason');
    const notesInput = document.getElementById('bulkNotesInput');
    const notesHidden = document.getElementById('bulkActionNotes');
    const actionType = document.getElementById('bulkActionType').value;
    const checkedItems = document.querySelectorAll('#bulkForm input[name="ids[]"]:checked');

    if (!checkedItems.length) {
        if (window.showNotification) {
            window.showNotification('Pilih minimal satu tugas terlebih dahulu.', 'warning');
        }
        return;
    }
    
    reasonHidden.value = reasonInput.value || '';
    notesHidden.value = notesInput.value || '';

    if (window.pkgPageLoader) {
        const loaderMap = {
            verify: {
                title: 'Memverifikasi tugas...',
                message: 'Semua data terpilih sedang diproses.',
            },
            unverify: {
                title: 'Membatalkan verifikasi...',
                message: 'Perubahan sedang disimpan.',
            },
            destroy: {
                title: 'Menolak verifikasi...',
                message: 'Tugas terpilih sedang dipindahkan ke arsip.',
            },
        };
        window.pkgPageLoader.show(loaderMap[actionType] || {
            title: 'Memproses data...',
            message: 'Mohon tunggu sebentar.'
        });
    }
    document.getElementById('bulkForm').submit();
}

function openVerifyModal(id, siswaName, karakterName) {
    document.getElementById('verifyModal').classList.remove('hidden');
    document.getElementById('verifyModal').classList.add('flex');
    document.getElementById('verifyForm').action = `/karakter/verification/${id}/verify`;
    document.getElementById('verifySiswaName').textContent = siswaName;
    document.getElementById('verifyKarakterName').textContent = karakterName;
}

function openUnverifyModal(id, siswaName, karakterName, poin) {
    document.getElementById('unverifyModal').classList.remove('hidden');
    document.getElementById('unverifyModal').classList.add('flex');
    document.getElementById('unverifyForm').action = `/karakter/verification/${id}/unverify`;
    document.getElementById('unverifySiswaName').textContent = siswaName;
    document.getElementById('unverifyKarakterName').textContent = karakterName;
    document.getElementById('unverifyPoints').textContent = poin;
    document.getElementById('unverifyReason').value = '';
}

function openDeleteModal(id, siswaName, karakterName, isVerified, poin) {
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('deleteModal').classList.add('flex');
    document.getElementById('deleteForm').action = `/karakter/verification/${id}`;
    document.getElementById('deleteSiswaName').textContent = siswaName;
    document.getElementById('deleteKarakterName').textContent = karakterName;
    document.getElementById('deleteReason').value = '';
    
    const warning = document.getElementById('deletePointsWarning');
    if (isVerified) {
        warning.classList.remove('hidden');
        document.getElementById('deletePoints').textContent = poin;
    } else {
        warning.classList.add('hidden');
    }
}

function closeVerificationModal(modalId) {
    const modal = document.getElementById(modalId);

    if (!modal) {
        return;
    }

    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Close modals on backdrop click
['verifyModal', 'unverifyModal', 'deleteModal', 'bulkConfirmModal', 'zikrHistoryModal'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(e) {
        if (e.target === this) closeVerificationModal(id);
    });
});

function openZikrHistoryModal(historyJson) {
    try {
        const history = JSON.parse(historyJson);
        const tbody = document.getElementById('zikrHistoryBody');
        tbody.innerHTML = '';
        
        if (Array.isArray(history) && history.length > 0) {
            history.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="px-6 py-2 font-medium text-gray-900 dark:text-white">${item.count}</td>
                    <td class="px-6 py-2 text-gray-500 dark:text-gray-400">${item.time}</td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="2" class="px-6 py-4 text-center text-gray-500">Tidak ada riwayat.</td></tr>';
        }
        
        document.getElementById('zikrHistoryModal').classList.remove('hidden');
        document.getElementById('zikrHistoryModal').classList.add('flex');
    } catch(e) {
        console.error("Invalid JSON history data", e);
    }
}
</script>
@endpush
