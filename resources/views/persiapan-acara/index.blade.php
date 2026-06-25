@extends('layouts.app')

@section('title', 'Persiapan Acara')

@section('content')
<div x-data="persiapanAcara()" class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">🎪 Persiapan Acara</h1>
            <p class="text-gray-600 dark:text-gray-400">Rencanakan kegiatan & share undangan ke WhatsApp</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('catatan-rapat.index') }}" class="px-3 py-2 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                ← Catatan Musyawarah
            </a>
            <button @click="openAddModal()" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Buat Persiapan
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    <!-- Acara Cards -->
    @forelse($acaras as $acara)
    <div class="pkg-panel mb-5 overflow-hidden hover:shadow-md transition-shadow">
        <!-- Card Header -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-5 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-white">
                        📩 {{ $acara->judul_acara }}
                        @if($acara->nomor_ke)
                        <span class="text-blue-200 font-normal">Ke {{ $acara->nomor_ke }}</span>
                        @endif
                    </h2>
                    <div class="flex flex-wrap items-center gap-3 mt-1 text-blue-100 text-sm">
                        @if($acara->waktu_acara)
                        <span>📅 {{ $acara->waktu_acara->isoFormat('dddd, D MMM YYYY') }}</span>
                        <span>🕰 {{ $acara->waktu_acara->format('H.i') }}@if($acara->waktu_selesai) – {{ $acara->waktu_selesai }}@endif WIB</span>
                        @endif
                        @if($acara->tempat)
                        <span>🕌 {{ $acara->tempat }}</span>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="shareWhatsApp({{ $acara->id }})" 
                        class="p-2.5 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors shadow-lg" title="Share ke WhatsApp">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                    </button>
                    <button @click='openEditModal(@json($acara))' 
                        class="p-2 bg-white/20 hover:bg-white/30 text-white rounded-lg transition-colors" title="Edit">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Card Body -->
        <div class="px-5 py-4 space-y-4">
            <!-- Info Badges -->
            <div class="flex flex-wrap gap-2">
                @if($acara->peserta)
                <span class="inline-flex items-center gap-1 px-3 py-1 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 text-xs font-medium rounded-full">👥 {{ $acara->peserta }}</span>
                @endif
                @if($acara->pakaian)
                <span class="inline-flex items-center gap-1 px-3 py-1 bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300 text-xs font-medium rounded-full">👕 {{ $acara->pakaian }}</span>
                @endif
            </div>

            <!-- Deskripsi -->
            @if($acara->deskripsi_acara)
            <div>
                <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">📝 Rencana Kegiatan</h4>
                <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $acara->deskripsi_acara }}</p>
            </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if(!empty($acara->perlengkapan))
                <div>
                    <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">🎒 Perlengkapan</h4>
                    <div class="space-y-1">
                        @foreach($acara->perlengkapan as $i => $item)
                        <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <span class="flex-shrink-0 w-5 h-5 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 flex items-center justify-center text-xs font-bold">{{ $i + 1 }}</span>
                            {{ $item }}
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                @if(!empty($acara->catatan_tambahan))
                <div>
                    <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">📝 Ket. Tambahan</h4>
                    <div class="space-y-1">
                        @foreach($acara->catatan_tambahan as $i => $item)
                        <div class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <span class="flex-shrink-0 w-5 h-5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 flex items-center justify-center text-xs font-bold">{{ $i + 1 }}</span>
                            <span>{{ $item }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- Rundown -->
            @if(!empty($acara->rundown))
            <div>
                <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">📋 RUNDOWN KEGIATAN</h4>
                <div class="relative pl-4 border-l-2 border-blue-200 dark:border-blue-800 space-y-3">
                    @foreach($acara->rundown as $item)
                    <div class="relative">
                        <div class="absolute -left-[1.35rem] top-0.5 w-3 h-3 rounded-full bg-blue-500 border-2 border-white dark:border-gray-800"></div>
                        <div class="ml-2">
                            <span class="text-xs font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-2 py-0.5 rounded">🔹 {{ $item['waktu'] ?? '' }}</span>
                            <p class="font-semibold text-sm text-gray-800 dark:text-gray-200 mt-1">{{ $item['kegiatan'] ?? '' }}</p>
                            @if(!empty($item['detail']))
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 whitespace-pre-line">{{ $item['detail'] }}</p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Panitia / PJ Section -->
            @php $allPanitia = $acara->getAllPanitia(); @endphp
            @if(!empty($allPanitia) || $acara->getTimDokumentasiUsers()->isNotEmpty())
            <div class="pt-3 border-t border-gray-100 dark:border-gray-700">
                <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">📌 Susunan Panitia</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                    @foreach($allPanitia as $key => $data)
                    <div class="flex items-start gap-2 p-2 bg-{{ $data['color'] }}-50 dark:bg-{{ $data['color'] }}-900/10 rounded-lg">
                        <span class="text-lg">{{ $data['icon'] }}</span>
                        <div>
                            <p class="text-xs font-bold text-{{ $data['color'] }}-800 dark:text-{{ $data['color'] }}-300">{{ $data['label'] }}</p>
                            <p class="text-xs text-{{ $data['color'] }}-600 dark:text-{{ $data['color'] }}-400">{{ $data['users']->pluck('username')->implode(', ') }}</p>
                        </div>
                    </div>
                    @endforeach
                    
                    @php $timUsers = $acara->getTimDokumentasiUsers(); @endphp
                    @if($timUsers->isNotEmpty())
                    <div class="flex items-start gap-2 p-2 bg-pink-50 dark:bg-pink-900/10 rounded-lg">
                        <span class="text-lg">📸</span>
                        <div>
                            <p class="text-xs font-bold text-pink-800 dark:text-pink-300">Tim Dokumentasi</p>
                            <p class="text-xs text-pink-600 dark:text-pink-400">{{ $timUsers->pluck('username')->implode(', ') }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <!-- Card Footer -->
        <div class="bg-gray-50 dark:bg-gray-750 px-5 py-2 text-xs text-gray-400 dark:text-gray-500 flex justify-between">
            <span>Dibuat oleh {{ $acara->creator->username ?? '-' }}</span>
            <span>{{ $acara->created_at->diffForHumans() }}</span>
        </div>
    </div>
    @empty
    <div class="pkg-panel p-12 text-center">
        <div class="text-6xl mb-4">🎪</div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Belum ada persiapan acara</h3>
        <p class="text-gray-500 dark:text-gray-400 mb-4">Buat rencana acara pertama Anda</p>
        <button @click="openAddModal()" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">+ Buat Persiapan Acara</button>
    </div>
    @endforelse

    <!-- Add/Edit Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-10 bg-black/50 overflow-y-auto" @click.self="showModal = false">
        <div class="bg-white dark:bg-gray-800 rounded-xl w-full max-w-3xl p-6 my-4" @click.stop>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4" x-text="editingId ? '✏️ Edit Persiapan Acara' : '🎪 Buat Persiapan Acara'"></h2>
            
            <form :action="editingId ? '/persiapan-acara/' + editingId : '{{ route('persiapan-acara.store') }}'" method="POST">
                @csrf
                <template x-if="editingId">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="space-y-5">
                    <!-- Judul & Nomor -->
                    <div class="grid grid-cols-3 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Judul Acara *</label>
                            <input type="text" name="judul_acara" x-model="form.judul_acara" required class="w-full px-3 py-2 pkg-field" placeholder="Contoh: PKG">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Acara Ke-</label>
                            <input type="number" name="nomor_ke" x-model="form.nomor_ke" class="w-full px-3 py-2 pkg-field" placeholder="11">
                        </div>
                    </div>

                    <!-- Deskripsi -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Isi Undangan / Deskripsi</label>
                        <textarea name="deskripsi_acara" x-model="form.deskripsi_acara" rows="3" class="w-full px-3 py-2 pkg-field" placeholder="Dengan hormat, kami mengundang seluruh generus..."></textarea>
                    </div>

                    <!-- Waktu dan Tempat -->
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">📅 Waktu Mulai</label>
                            <input type="datetime-local" name="waktu_acara" x-model="form.waktu_acara" class="w-full px-3 py-2 pkg-field">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">🕰 Waktu Selesai</label>
                            <input type="text" name="waktu_selesai" x-model="form.waktu_selesai" class="w-full px-3 py-2 pkg-field" placeholder="22.00">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">🕌 Tempat</label>
                            <input type="text" name="tempat" x-model="form.tempat" class="w-full px-3 py-2 pkg-field" placeholder="Masjid / Aula">
                        </div>
                    </div>

                    <!-- Peserta & Pakaian -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">👥 Peserta</label>
                            <input type="text" name="peserta" x-model="form.peserta" class="w-full px-3 py-2 pkg-field" placeholder="Generus SMP-SMA Desa...">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">👕 Pakaian</label>
                            <input type="text" name="pakaian" x-model="form.pakaian" class="w-full px-3 py-2 pkg-field" placeholder="Seragam Pramuka + Sepatu">
                        </div>
                    </div>

                    <!-- Perlengkapan -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">🎒 Perlengkapan</label>
                        <template x-for="(item, index) in form.perlengkapan" :key="'p'+index">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-xs text-gray-400 w-5 text-center" x-text="index+1"></span>
                                <input type="text" :name="'perlengkapan[' + index + ']'" x-model="form.perlengkapan[index]" placeholder="Nama perlengkapan..." class="flex-1 px-3 py-2 text-sm pkg-field">
                                <button type="button" @click="form.perlengkapan.splice(index, 1)" class="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg" x-show="form.perlengkapan.length > 1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </template>
                        <button type="button" @click="form.perlengkapan.push('')" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">+ Tambah Perlengkapan</button>
                    </div>

                    <!-- Catatan Tambahan -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">📝 Keterangan Tambahan</label>
                        <template x-for="(item, index) in form.catatan_tambahan" :key="'c'+index">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-xs text-gray-400 w-5 text-center" x-text="index+1"></span>
                                <input type="text" :name="'catatan_tambahan[' + index + ']'" x-model="form.catatan_tambahan[index]" placeholder="Catatan..." class="flex-1 px-3 py-2 text-sm pkg-field">
                                <button type="button" @click="form.catatan_tambahan.splice(index, 1)" class="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg" x-show="form.catatan_tambahan.length > 1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </template>
                        <button type="button" @click="form.catatan_tambahan.push('')" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">+ Tambah Catatan</button>
                    </div>

                    <!-- Rundown -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">📋 Rundown Kegiatan</label>
                        <template x-for="(rd, index) in form.rundown" :key="'r'+index">
                            <div class="bg-gray-50 dark:bg-gray-750 rounded-lg p-3 mb-2 border border-gray-200 dark:border-gray-600">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-blue-600 dark:text-blue-400 font-bold text-sm">🔹</span>
                                    <input type="text" :name="'rundown[' + index + '][waktu]'" x-model="rd.waktu" placeholder="14.20 – 14.30" class="w-40 px-3 py-1.5 text-sm pkg-field">
                                    <input type="text" :name="'rundown[' + index + '][kegiatan]'" x-model="rd.kegiatan" placeholder="Nama Kegiatan" class="flex-1 px-3 py-1.5 text-sm pkg-field font-medium focus:ring-2 focus:ring-blue-500">
                                    <button type="button" @click="form.rundown.splice(index, 1)" class="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg" x-show="form.rundown.length > 1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                <textarea :name="'rundown[' + index + '][detail]'" x-model="rd.detail" rows="2" placeholder="Detail kegiatan..." class="w-full px-3 py-1.5 text-sm pkg-field"></textarea>
                            </div>
                        </template>
                        <button type="button" @click="form.rundown.push({waktu: '', kegiatan: '', detail: ''})" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">+ Tambah Rundown</button>
                    </div>

                    <!-- ========== SUSUNAN PANITIA (Collapsible) ========== -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-5">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3">📌 Susunan Panitia <span class="text-gray-400 font-normal text-xs">(klik untuk buka/tutup)</span></h3>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach($pjCategories as $key => $meta)
                            <div x-data="{open: false}" class="border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden">
                                <!-- Header (clickable) -->
                                <button type="button" @click="open = !open" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 flex items-center justify-between hover:bg-gray-100 dark:hover:bg-gray-650 transition-colors">
                                    <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $meta['icon'] }} {{ $meta['label'] }}</span>
                                    <div class="flex items-center gap-1">
                                        <span class="text-xs text-blue-600 dark:text-blue-400 font-medium" x-text="(form.panitia?.{{ $key }}?.length || 0) + ' dipilih'" x-show="form.panitia?.{{ $key }}?.length > 0"></span>
                                        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </button>
                                <!-- Selected names (shown when collapsed) -->
                                <div x-show="!open && form.panitia?.{{ $key }}?.length > 0" class="px-3 py-1.5 border-t border-gray-100 dark:border-gray-600 flex flex-wrap gap-1">
                                    @foreach($users as $user)
                                    <span x-show="form.panitia?.{{ $key }}?.includes({{ $user->id }}) || form.panitia?.{{ $key }}?.includes('{{ $user->id }}')" 
                                        class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs rounded-full">{{ $user->username }}</span>
                                    @endforeach
                                </div>
                                <!-- Checkbox list (shown when expanded) -->
                                <div x-show="open" x-collapse class="max-h-36 overflow-y-auto p-2 space-y-0.5 border-t border-gray-200 dark:border-gray-600">
                                    @foreach($users as $user)
                                    <label class="flex items-center gap-2 px-2 py-1 hover:bg-gray-50 dark:hover:bg-gray-600 rounded cursor-pointer">
                                        <input type="checkbox" 
                                            name="panitia[{{ $key }}][]" 
                                            value="{{ $user->id }}" 
                                            :checked="form.panitia?.{{ $key }}?.includes({{ $user->id }}) || form.panitia?.{{ $key }}?.includes('{{ $user->id }}')"
                                            @change="if ($event.target.checked) { if (!form.panitia.{{ $key }}) form.panitia.{{ $key }} = []; form.panitia.{{ $key }}.push({{ $user->id }}) } else { form.panitia.{{ $key }} = form.panitia.{{ $key }}.filter(id => id != {{ $user->id }}) }"
                                            class="w-3.5 h-3.5 text-blue-600 rounded">
                                        <span class="text-xs text-gray-700 dark:text-gray-300">{{ $user->username }}</span>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach

                            <!-- Tim Dokumentasi (Collapsible) -->
                            <div x-data="{open: false}" class="border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden">
                                <button type="button" @click="open = !open" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 flex items-center justify-between hover:bg-gray-100 dark:hover:bg-gray-650 transition-colors">
                                    <span class="text-sm font-medium text-gray-800 dark:text-gray-200">📸 Tim Dokumentasi</span>
                                    <div class="flex items-center gap-1">
                                        <span class="text-xs text-purple-600 dark:text-purple-400 font-medium" x-text="(form.tim_dokumentasi?.length || 0) + ' dipilih'" x-show="form.tim_dokumentasi?.length > 0"></span>
                                        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </button>
                                <div x-show="!open && form.tim_dokumentasi?.length > 0" class="px-3 py-1.5 border-t border-gray-100 dark:border-gray-600 flex flex-wrap gap-1">
                                    @foreach($users as $user)
                                    <span x-show="form.tim_dokumentasi?.includes({{ $user->id }}) || form.tim_dokumentasi?.includes('{{ $user->id }}')" 
                                        class="px-2 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 text-xs rounded-full">{{ $user->username }}</span>
                                    @endforeach
                                </div>
                                <div x-show="open" x-collapse class="max-h-36 overflow-y-auto p-2 space-y-0.5 border-t border-gray-200 dark:border-gray-600">
                                    @foreach($users as $user)
                                    <label class="flex items-center gap-2 px-2 py-1 hover:bg-gray-50 dark:hover:bg-gray-600 rounded cursor-pointer">
                                        <input type="checkbox" 
                                            name="tim_dokumentasi[]" 
                                            value="{{ $user->id }}" 
                                            :checked="form.tim_dokumentasi?.includes({{ $user->id }}) || form.tim_dokumentasi?.includes('{{ $user->id }}')"
                                            @change="if ($event.target.checked) { form.tim_dokumentasi.push({{ $user->id }}) } else { form.tim_dokumentasi = form.tim_dokumentasi.filter(id => id != {{ $user->id }}) }"
                                            class="w-3.5 h-3.5 text-purple-600 rounded">
                                        <span class="text-xs text-gray-700 dark:text-gray-300">{{ $user->username }}</span>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div>
                        <button type="button" x-show="editingId" @click="deleteAcara()" class="text-red-600 hover:text-red-700 text-sm font-medium">🗑️ Hapus</button>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" @click="showModal = false" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">💾 Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Form -->
    <form id="delete-form" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>
</div>

@push('styles')
<style>[x-cloak] { display: none !important; }</style>
@endpush

@push('scripts')
<script>
function persiapanAcara() {
    return {
        showModal: false,
        editingId: null,
        form: {
            judul_acara: '', nomor_ke: '', deskripsi_acara: '',
            waktu_acara: '', waktu_selesai: '', tempat: '',
            peserta: '', pakaian: '',
            materi_pemateri: [{materi: '', pemateri: ''}],
            perlengkapan: [''], catatan_tambahan: [''],
            rundown: [{waktu: '', kegiatan: '', detail: ''}],
            pj_acara_id: '', tim_dokumentasi: [],
            panitia: {}
        },

        waTexts: @json($acaras->mapWithKeys(fn($a) => [$a->id => $a->generateWhatsAppText()])),

        openAddModal() {
            this.editingId = null;
            this.form = {
                judul_acara: '', nomor_ke: '', deskripsi_acara: '',
                waktu_acara: '', waktu_selesai: '', tempat: '',
                peserta: '', pakaian: '',
                materi_pemateri: [{materi: '', pemateri: ''}],
                perlengkapan: [''], catatan_tambahan: [''],
                rundown: [{waktu: '', kegiatan: '', detail: ''}],
                pj_acara_id: '', tim_dokumentasi: [],
                panitia: {}
            };
            this.showModal = true;
        },

        openEditModal(acara) {
            this.editingId = acara.id;
            this.form = {
                judul_acara: acara.judul_acara || '',
                nomor_ke: acara.nomor_ke || '',
                deskripsi_acara: acara.deskripsi_acara || '',
                waktu_acara: acara.waktu_acara ? acara.waktu_acara.replace(' ', 'T').substring(0, 16) : '',
                waktu_selesai: acara.waktu_selesai || '',
                tempat: acara.tempat || '',
                peserta: acara.peserta || '',
                pakaian: acara.pakaian || '',
                materi_pemateri: (acara.materi_pemateri?.length > 0) ? acara.materi_pemateri : [{materi: '', pemateri: ''}],
                perlengkapan: (acara.perlengkapan?.length > 0) ? acara.perlengkapan : [''],
                catatan_tambahan: (acara.catatan_tambahan?.length > 0) ? acara.catatan_tambahan : [''],
                rundown: (acara.rundown?.length > 0) ? acara.rundown : [{waktu: '', kegiatan: '', detail: ''}],
                pj_acara_id: acara.pj_acara_id || '',
                tim_dokumentasi: acara.tim_dokumentasi || [],
                panitia: acara.panitia || {}
            };
            this.showModal = true;
        },

        async deleteAcara() {
            const confirmed = await window.showConfirmation('Hapus persiapan acara ini?', {
                title: 'Hapus persiapan acara',
                confirmText: 'Hapus',
                tone: 'danger'
            });
            if (!confirmed) return;
            const form = document.getElementById('delete-form');
            form.action = '/persiapan-acara/' + this.editingId;
            form.submit();
        },

        shareWhatsApp(id) {
            const text = this.waTexts[id];
            if (text) {
                window.open('https://wa.me/?text=' + encodeURIComponent(text), '_blank');
            }
        }
    }
}
</script>
@endpush
@endsection


