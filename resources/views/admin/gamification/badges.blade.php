@extends('layouts.app')

@section('title', 'Kelola Pin Penghargaan')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="pkg-page-header">
            <div>
                <h1 class="pkg-page-heading">Kelola Pin Penghargaan</h1>
                <p class="pkg-page-subheading">Buat pin untuk kehadiran, tugas PKG, dan capaian level dengan aturan yang lebih konsisten.</p>
            </div>
            <button onclick="openPinModal()" class="pkg-btn-primary px-4 py-2">
                + Tambah Pin
            </button>
        </div>

        @include('admin.gamification.partials.navigation')
        <!-- Info Box -->
        <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl">
            <p class="text-sm text-blue-800 dark:text-blue-300">
                <strong>Cara Kerja:</strong> Pin penghargaan otomatis diberikan ke siswa saat mereka memenuhi target yang sudah ditentukan.
                Ada 3 jenis: <strong>Kehadiran</strong> (berapa kali hadir), <strong>Karakter / Tugas PKG</strong> (berapa tugas yang sudah diverifikasi), dan <strong>Naik Level</strong> (saat mencapai level tertentu).
            </p>
        </div>

        <!-- Pin Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            @foreach($badges as $badge)
            <div class="pkg-panel p-6 {{ !$badge->is_active ? 'opacity-60' : '' }}">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-14 h-14 rounded-full flex items-center justify-center text-3xl" style="background-color: {{ $badge->warna }}20">
                            {{ $badge->icon_url }}
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800 dark:text-white">{{ $badge->nama }}</h3>
                            <span class="text-xs px-2 py-0.5 rounded-full" style="background-color: {{ $badge->warna }}20; color: {{ $badge->warna }}">
                                {{ $badge->kategori === 'attendance' ? 'Kehadiran' : ($badge->kategori === 'level' ? 'Naik Level' : 'Karakter / Tugas PKG') }}
                            </span>
                        </div>
                    </div>
                    <div class="flex gap-1">
                        <button onclick="editPin({{ $badge->id }})" class="p-2 text-gray-400 hover:text-indigo-600 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <button onclick="deletePin({{ $badge->id }})" class="p-2 text-gray-400 hover:text-red-600 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-3">{{ $badge->deskripsi }}</p>
                
                <div class="mt-4 flex items-center justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">{{ $badge->user_badges_count }} siswa sudah mendapat</span>
                </div>
                
                <!-- Aturan yang jelas -->
                <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <div class="flex items-center gap-2 p-2 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg">
                        <span class="text-sm font-semibold text-indigo-700 dark:text-indigo-300">Aturan</span>
                        <span class="text-sm font-medium text-indigo-700 dark:text-indigo-300">{{ $badge->criteria_description }}</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Global Rank / Leaderboard -->
        <div x-data="{ showLeaderboard: false }">
            <div class="pkg-panel">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center cursor-pointer" @click="showLeaderboard = !showLeaderboard">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">Peringkat Global Siswa</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $leaderboard->count() }} siswa sudah mendapatkan poin</p>
                    </div>
                    <svg class="w-5 h-5 text-gray-500 transition-transform" :class="showLeaderboard ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
                
                <!-- Top 3 always visible -->
                <div class="p-4 grid grid-cols-3 gap-4">
                    @foreach($leaderboard->take(3) as $index => $sp)
                        @php
                            $medals = ['P1', 'P2', 'P3'];
                            $bgColors = ['bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800', 'bg-gray-50 dark:bg-gray-700/50 border-gray-200 dark:border-gray-600', 'bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-800'];
                        @endphp
                        <div class="p-4 rounded-lg border {{ $bgColors[$index] ?? '' }} text-center">
                            <div class="text-3xl mb-1">{{ $medals[$index] }}</div>
                            <div class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ $sp->siswa->nama ?? '-' }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $sp->siswa->school_grade_label ?? 'Kelas belum dikonfirmasi' }}</div>
                            <div class="text-lg font-bold text-indigo-600 dark:text-indigo-400 mt-1">{{ number_format($sp->total_points) }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Level {{ $sp->level }}</div>
                        </div>
                    @endforeach
                </div>
                
                <!-- Full leaderboard (collapsible) -->
                <div x-show="showLeaderboard" x-collapse x-cloak>
                    <div class="pkg-mobile-table overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Rank</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Siswa</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Kelas Sekolah</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Level</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Kehadiran</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Karakter</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Total Poin</th>
                                </tr>
                            </thead>
                            <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($leaderboard as $index => $sp)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 {{ $index < 3 ? 'font-semibold' : '' }}">
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white" data-label="Peringkat">
                                        @if($index < 3)
                                            {{ ['P1', 'P2', 'P3'][$index] }}
                                        @else
                                            #{{ $index + 1 }}
                                        @endif
                                    </td>
                                    <td class="pkg-mobile-main px-4 py-3 text-sm text-gray-900 dark:text-white" data-label="Siswa">{{ $sp->siswa->nama ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400" data-label="Kelas Sekolah">{{ $sp->siswa->school_grade_label ?? 'Belum dikonfirmasi' }}</td>
                                    <td class="px-4 py-3 text-sm text-center text-indigo-600 dark:text-indigo-400 font-medium" data-label="Level">{{ $sp->level }}</td>
                                    <td class="px-4 py-3 text-sm text-center text-blue-600 dark:text-blue-400" data-label="Kehadiran">{{ number_format($sp->attendance_points) }}</td>
                                    <td class="px-4 py-3 text-sm text-center text-green-600 dark:text-green-400" data-label="Tugas PKG">{{ number_format($sp->character_points) }}</td>
                                    <td class="px-4 py-3 text-sm text-right font-bold text-gray-900 dark:text-white" data-label="Total poin">{{ number_format($sp->total_points) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pin Modal (Simplified) -->
<div id="pinModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
        <h2 id="modalTitle" class="text-xl font-bold text-gray-800 dark:text-white mb-4">Tambah Pin Penghargaan</h2>
        <form id="pinForm" onsubmit="savePin(event)">
            <input type="hidden" id="pinId">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Pin</label>
                    <input type="text" id="pinName" required placeholder="Contoh: Siswa Rajin, Bintang PKG" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Deskripsi</label>
                    <textarea id="pinDesc" required rows="2" placeholder="Contoh: Diberikan untuk siswa yang rajin hadir" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white"></textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kategori</label>
                        <select id="pinCategory" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white" onchange="onCategoryChange()">
                            <option value="attendance">Kehadiran</option>
                            <option value="character">Karakter / Tugas PKG</option>
                            <option value="level">Naik Level</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Warna</label>
                        <input type="color" id="pinColor" value="#3B82F6" class="w-full h-10 border border-gray-300 dark:border-gray-600 rounded-lg">
                    </div>
                </div>

                <!-- Icon Picker -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Icon Pin</label>
                    <div class="flex flex-wrap gap-2" id="iconPicker">
                        @php
                            $icons = ['PIN', 'STAR', 'TROFI', 'P1', 'P2', 'P3', 'L4', 'STR', 'FAST', 'MEDAL', 'HADIR', 'KAL', 'OK', 'KUAT', 'LULUS', 'BUKU', 'HEBAT', 'RAJA', 'PIN2', 'RAYA', 'AMAN', 'ROKET', 'MAX'];
                        @endphp
                        @foreach($icons as $ic)
                        <button type="button" onclick="selectIcon('{{ $ic }}')" class="icon-btn w-10 h-10 text-xs font-semibold rounded-lg border border-gray-200 dark:border-gray-600 hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 flex items-center justify-center transition" data-icon="{{ $ic }}">
                            {{ $ic }}
                        </button>
                        @endforeach
                    </div>
                    <input type="hidden" id="pinIcon" value="">
                </div>
                
                <!-- Target -->
                <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-xl">
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2" id="targetLabel">Target: Jumlah Hadir</label>
                    <input type="number" id="targetValue" required min="1" value="10" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white text-lg font-bold text-center">
                    <select id="targetLevelSelect" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white text-lg font-bold text-center hidden">
                        @foreach($levels as $lvl)
                            <option value="{{ $lvl->level }}">Level {{ $lvl->level }} - {{ $lvl->nama }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2" id="targetHint">
                        Pin akan diberikan otomatis jika siswa sudah hadir minimal sejumlah ini.
                    </p>
                </div>

                <!-- Bonus Poin -->
                <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl">
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Bonus Poin saat Dapat Pin</label>
                    <input type="number" id="pinPoinReward" min="0" value="0" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white text-lg font-bold text-center">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                        Poin bonus yang otomatis ditambahkan ke siswa saat mendapatkan pin ini. Set <strong>0</strong> jika tidak ada bonus.
                    </p>
                </div>

                <!-- Preview -->
                <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg text-sm text-green-800 dark:text-green-300" id="rulePreview">
                    <strong>Aturan:</strong> <span id="ruleText">Pin diberikan jika siswa sudah hadir minimal 10 kali.</span>
                </div>
            </div>
            
            <div class="mt-6 flex gap-3">
                <button type="button" onclick="closePinModal()" class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    Batal
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const badges = @json($badges);
let selectedIcon = '';

function selectIcon(icon) {
    selectedIcon = icon;
    document.getElementById('pinIcon').value = icon;
    document.querySelectorAll('.icon-btn').forEach(btn => {
        btn.classList.remove('border-indigo-500', 'bg-indigo-50', 'ring-2', 'ring-indigo-300');
        if (btn.dataset.icon === icon) {
            btn.classList.add('border-indigo-500', 'bg-indigo-50', 'ring-2', 'ring-indigo-300');
        }
    });
}

function onCategoryChange() {
    const cat = document.getElementById('pinCategory').value;
    const label = document.getElementById('targetLabel');
    const hint = document.getElementById('targetHint');
    const numInput = document.getElementById('targetValue');
    const lvlSelect = document.getElementById('targetLevelSelect');
    
    if (cat === 'attendance') {
        label.textContent = 'Target: Jumlah Hadir';
        hint.textContent = 'Pin akan diberikan otomatis jika siswa sudah hadir minimal sejumlah ini.';
        numInput.classList.remove('hidden');
        lvlSelect.classList.add('hidden');
        numInput.required = true;
    } else if (cat === 'character') {
        label.textContent = 'Target: Jumlah Tugas PKG Terverifikasi';
        hint.textContent = 'Pin akan diberikan otomatis jika siswa sudah menyelesaikan & terverifikasi sejumlah tugas PKG.';
        numInput.classList.remove('hidden');
        lvlSelect.classList.add('hidden');
        numInput.required = true;
    } else if (cat === 'level') {
        label.textContent = 'Target: Mencapai Level';
        hint.textContent = 'Pin akan diberikan otomatis saat siswa mencapai level ini.';
        numInput.classList.add('hidden');
        lvlSelect.classList.remove('hidden');
        numInput.required = false;
    }
    updateRulePreview();
}

function updateRulePreview() {
    const cat = document.getElementById('pinCategory').value;
    const ruleText = document.getElementById('ruleText');
    
    if (cat === 'level') {
        const sel = document.getElementById('targetLevelSelect');
        const text = sel.options[sel.selectedIndex]?.text || '';
        ruleText.textContent = `Pin diberikan saat siswa mencapai ${text}.`;
    } else {
        const val = document.getElementById('targetValue').value || 0;
        if (cat === 'attendance') {
            ruleText.textContent = `Pin diberikan jika siswa sudah hadir minimal ${val} kali.`;
        } else {
            ruleText.textContent = `Pin diberikan jika siswa sudah menyelesaikan & diverifikasi ${val} tugas PKG.`;
        }
    }
}

// Update preview on value change
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('targetValue').addEventListener('input', updateRulePreview);
    document.getElementById('targetLevelSelect').addEventListener('change', updateRulePreview);
});

function openPinModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Pin Penghargaan';
    document.getElementById('pinId').value = '';
    document.getElementById('pinForm').reset();
    document.getElementById('pinIcon').value = '';
    selectedIcon = '';
    document.querySelectorAll('.icon-btn').forEach(btn => btn.classList.remove('border-indigo-500', 'bg-indigo-50', 'ring-2', 'ring-indigo-300'));
    onCategoryChange();
    document.getElementById('pinModal').classList.remove('hidden');
    document.getElementById('pinModal').classList.add('flex');
}

function closePinModal() {
    document.getElementById('pinModal').classList.add('hidden');
    document.getElementById('pinModal').classList.remove('flex');
}

function editPin(id) {
    const badge = badges.find(b => b.id === id);
    if (!badge) return;
    
    document.getElementById('modalTitle').textContent = 'Edit Pin Penghargaan';
    document.getElementById('pinId').value = badge.id;
    document.getElementById('pinName').value = badge.nama;
    document.getElementById('pinDesc').value = badge.deskripsi;
    document.getElementById('pinCategory').value = badge.kategori;
    document.getElementById('pinColor').value = badge.warna;
    
    const criteriaType = badge.kriteria?.type || '';
    if (criteriaType === 'level_reached') {
        document.getElementById('targetLevelSelect').value = badge.kriteria?.value || 1;
    } else {
        document.getElementById('targetValue').value = badge.kriteria?.value || 10;
    }
    document.getElementById('pinPoinReward').value = badge.poin_reward || 0;
    
    if (badge.icon) {
        selectIcon(badge.icon);
    }
    
    onCategoryChange();
    document.getElementById('pinModal').classList.remove('hidden');
    document.getElementById('pinModal').classList.add('flex');
}

function savePin(e) {
    e.preventDefault();
    
    const id = document.getElementById('pinId').value;
    const category = document.getElementById('pinCategory').value;
    let criteriaType, criteriaValue;
    
    if (category === 'level') {
        criteriaType = 'level_reached';
        criteriaValue = parseInt(document.getElementById('targetLevelSelect').value);
    } else {
        criteriaType = category === 'attendance' ? 'attendance_count' : 'verified_character_count';
        criteriaValue = parseInt(document.getElementById('targetValue').value);
    }
    
    const data = {
        nama: document.getElementById('pinName').value,
        deskripsi: document.getElementById('pinDesc').value,
        kategori: category,
        warna: document.getElementById('pinColor').value,
        icon: document.getElementById('pinIcon').value || null,
        poin_reward: parseInt(document.getElementById('pinPoinReward').value) || 0,
        kriteria: {
            type: criteriaType,
            value: criteriaValue
        }
    };
    
    const url = id ? `/gamification/badges/${id}` : '/gamification/badges';
    const method = id ? 'PUT' : 'POST';
    
    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(r => {
        if (!r.ok) return r.json().then(err => { throw err; });
        return r.json();
    })
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            window.showNotification(data.message || 'Gagal menyimpan pin', 'error');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        if (err.errors) {
            window.showNotification('Validasi gagal: ' + Object.values(err.errors).flat().join(', '), 'error');
        } else {
            window.showNotification(err.message || 'Terjadi kesalahan saat menyimpan', 'error');
        }
    });
}

async function deletePin(id) {
    const confirmed = await window.showConfirmation('Yakin ingin menghapus pin penghargaan ini?', {
        title: 'Hapus pin penghargaan',
        confirmText: 'Hapus',
        tone: 'danger'
    });
    if (!confirmed) return;
    
    fetch(`/gamification/badges/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            window.showNotification(data.message || 'Gagal menghapus pin', 'error');
        }
    });
}

document.getElementById('pinModal').addEventListener('click', function(e) {
    if (e.target === this) closePinModal();
});
</script>
@endsection
