@extends('layouts.app')

@section('title', 'Manajemen Kelas')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <nav class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                <a href="{{ route('dashboard') }}" class="hover:text-gray-700 dark:hover:text-gray-200">Dashboard</a>
                <span class="mx-2">/</span>
                <span class="text-gray-900 dark:text-white">Kelas</span>
            </nav>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Manajemen Kelas</h1>
            <p class="mt-1 text-gray-600 dark:text-gray-400">Kelola kelas dan pamong yang mengajar (1 kelas bisa memiliki beberapa pamong)</p>
        </div>
        <button onclick="openAddModal()" 
                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Tambah Kelas
        </button>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="pkg-panel p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Kelas</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalKelas }}</p>
                </div>
            </div>
        </div>

        <div class="pkg-panel p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Kelas Aktif</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $kelasAktif }}</p>
                </div>
            </div>
        </div>

        <div class="pkg-panel p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Pamong</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalPamong }}</p>
                </div>
            </div>
        </div>

        <div class="pkg-panel p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Siswa</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalSiswa }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Kelas List -->
    <div class="pkg-panel">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Daftar Kelas</h2>
            <div class="flex gap-2">
                <input type="text" id="search-input" placeholder="Cari kelas atau pamong..." 
                       class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white"
                       onkeyup="filterKelas()">
            </div>
        </div>

        @if($kelasList->count() > 0)
        <div class="overflow-x-auto pkg-mobile-table">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kelas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pamong</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Siswa</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody id="kelas-tbody" class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($kelasList as $kelas)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 kelas-row" 
                        data-nama="{{ strtolower($kelas->nama) }}" 
                        data-pamong="{{ strtolower($kelas->pamong->pluck('name')->implode(' ') . ' ' . $kelas->pamong->pluck('username')->implode(' ')) }}">
                        <td data-label="Kelas" class="px-6 py-4 pkg-mobile-main">
                            <div class="font-medium text-gray-900 dark:text-white">{{ $kelas->nama }}</div>
                            <div class="text-sm text-gray-500">{{ $kelas->kode_kelas }}</div>
                        </td>
                        <td data-label="Pamong" class="px-6 py-4">
                            <div class="flex flex-wrap gap-1">
                                @forelse($kelas->pamong as $pamong)
                                    <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded text-xs">
                                        {{ $pamong->name ?? $pamong->username }}
                                        @if($pamong->pivot->role && $pamong->pivot->role !== 'pengajar')
                                            <span class="text-blue-600 dark:text-blue-400">({{ $pamong->pivot->role }})</span>
                                        @endif
                                    </span>
                                @empty
                                    <span class="text-gray-400 text-sm">Belum ada pamong</span>
                                @endforelse
                            </div>
                        </td>
                        <td data-label="Siswa" class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                {{ $kelas->siswa_count }} / {{ $kelas->kapasitas }}
                            </span>
                        </td>
                        <td data-label="Status" class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $kelas->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' }}">
                                {{ $kelas->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td data-label="Aksi" class="px-6 py-4 text-right pkg-mobile-actions">
                            <button onclick="editKelas({{ $kelas->id }})" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 mr-2" title="Edit">
                                <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button onclick="deleteKelas({{ $kelas->id }})" class="text-red-600 hover:text-red-900 dark:text-red-400" title="Hapus">
                                <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="px-6 py-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <p class="mt-2 text-gray-500 dark:text-gray-400">Belum ada kelas</p>
            <button onclick="openAddModal()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Tambah Kelas Pertama
            </button>
        </div>
        @endif
    </div>
</div>


<!-- Add/Edit Modal -->
<div id="kelas-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75" onclick="closeModal()"></div>
        <div class="relative pkg-modal max-w-lg w-full">
            <form id="kelas-form" onsubmit="saveKelas(event)">
                <input type="hidden" id="kelas-id">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 id="modal-title" class="text-lg font-semibold text-gray-900 dark:text-white">Tambah Kelas</h3>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Kelas</label>
                        <input type="text" id="nama" required 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                               placeholder="Contoh: Kelas 1A">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kapasitas</label>
                        <input type="number" id="kapasitas" value="30" min="1" max="100"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pamong (Pilih 1-5)</label>
                        <select id="pamong_ids" multiple size="5"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                            @foreach($pamongList as $pamong)
                            <option value="{{ $pamong->id }}">{{ $pamong->name ?? $pamong->username }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Tahan Ctrl/Cmd untuk memilih lebih dari satu pamong</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Deskripsi</label>
                        <textarea id="deskripsi" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                                  placeholder="Deskripsi kelas (opsional)"></textarea>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" id="is_active" checked
                               class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="is_active" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Status Aktif</label>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                    <button type="button" onclick="closeModal()" 
                            class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Filter kelas by search
function filterKelas() {
    const search = document.getElementById('search-input').value.toLowerCase();
    const rows = document.querySelectorAll('.kelas-row');
    
    rows.forEach(row => {
        const nama = row.dataset.nama || '';
        const pamong = row.dataset.pamong || '';
        
        if (nama.includes(search) || pamong.includes(search)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function openAddModal() {
    document.getElementById('modal-title').textContent = 'Tambah Kelas';
    document.getElementById('kelas-id').value = '';
    document.getElementById('kelas-form').reset();
    document.getElementById('is_active').checked = true;
    // Clear pamong selection
    const pamongSelect = document.getElementById('pamong_ids');
    Array.from(pamongSelect.options).forEach(opt => opt.selected = false);
    document.getElementById('kelas-modal').classList.remove('hidden');
}

// Store kelas data for editing
const kelasData = @json($kelasList->keyBy('id'));

function editKelas(id) {
    const kelas = kelasData[id];
    if (!kelas) {
        window.showNotification('Data kelas tidak ditemukan', 'error');
        return;
    }
    
    document.getElementById('modal-title').textContent = 'Edit Kelas';
    document.getElementById('kelas-id').value = kelas.id;
    document.getElementById('nama').value = kelas.nama;
    document.getElementById('kapasitas').value = kelas.kapasitas;
    document.getElementById('deskripsi').value = kelas.deskripsi || '';
    document.getElementById('is_active').checked = kelas.is_active;
    
    // Set selected pamong (multi-select)
    const pamongSelect = document.getElementById('pamong_ids');
    Array.from(pamongSelect.options).forEach(opt => {
        opt.selected = (kelas.pamong || []).some(p => p.id == opt.value);
    });
    
    document.getElementById('kelas-modal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('kelas-modal').classList.add('hidden');
}

async function saveKelas(event) {
    event.preventDefault();
    
    const id = document.getElementById('kelas-id').value;
    const pamongSelect = document.getElementById('pamong_ids');
    const selectedPamong = Array.from(pamongSelect.selectedOptions).map(opt => parseInt(opt.value));
    
    const data = {
        nama: document.getElementById('nama').value,
        kapasitas: parseInt(document.getElementById('kapasitas').value),
        deskripsi: document.getElementById('deskripsi').value,
        is_active: document.getElementById('is_active').checked,
        pamong_ids: selectedPamong,
    };
    
    try {
        const url = id ? `/kelas/${id}` : '/kelas';
        const method = id ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify(data),
        });
        
        const result = await response.json();
        
        if (result.success) {
            closeModal();
            window.location.reload();
        } else {
            window.showNotification(result.message || 'Gagal menyimpan kelas', 'error');
        }
    } catch (error) {
        console.error('Error saving kelas:', error);
        window.showNotification('Gagal menyimpan kelas', 'error');
    }
}

async function deleteKelas(id) {
    const confirmed = await window.showConfirmation('Yakin ingin menghapus kelas ini?', {
        title: 'Hapus kelas',
        confirmText: 'Hapus',
        tone: 'danger'
    });
    if (!confirmed) return;
    
    try {
        const response = await fetch(`/kelas/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json',
            },
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.location.reload();
        } else {
            window.showNotification(result.message || 'Gagal menghapus kelas', 'error');
        }
    } catch (error) {
        console.error('Error deleting kelas:', error);
        window.showNotification('Gagal menghapus kelas', 'error');
    }
}
</script>
@endsection

