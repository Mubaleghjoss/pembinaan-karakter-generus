@extends('layouts.app')

@section('title', 'Tugas PKG Harian - PKG')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-6">
        <a href="{{ route('dashboard') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 flex items-center gap-1 mb-4">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Dashboard
        </a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Tugas PKG Harian</h1>
        <p class="mt-1 text-gray-600 dark:text-gray-300">Daftar tugas PKG yang sedang aktif dan tersedia</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="pkg-card-soft rounded-2xl p-5">
            <div class="flex items-center gap-3">
                <div class="bg-blue-100 dark:bg-blue-900/30 p-3 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-300">Total Checklist</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>
        <div class="pkg-card-soft rounded-2xl p-5">
            <div class="flex items-center gap-3">
                <div class="bg-green-100 dark:bg-green-900/30 p-3 rounded-lg">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-300">Terverifikasi</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['verified'] }}</p>
                </div>
            </div>
        </div>
        <div class="pkg-card-soft rounded-2xl p-5">
            <div class="flex items-center gap-3">
                <div class="bg-yellow-100 dark:bg-yellow-900/30 p-3 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-300">Menunggu Verifikasi</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['unverified'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Karakter Lists by Category -->
    @if($harianList->count() > 0)
    <div class="pkg-card mb-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-2">
                <span class="text-sm font-semibold text-cyan-700 dark:text-cyan-300">P</span>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Karakter Harian</h2>
                <span class="pkg-status-badge pkg-status-info">{{ $harianList->count() }}</span>
            </div>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($harianList as $k)
                <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:shadow-md transition bg-gradient-to-br from-blue-50 to-white dark:from-blue-900/10 dark:to-gray-800">
                    <div class="flex items-start justify-between mb-2">
                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ $k->nama }}</h3>
                        <span class="pkg-status-badge pkg-status-warning">+{{ $k->poin }} poin</span>
                    </div>
                    @if($k->deskripsi)
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ $k->deskripsi }}</p>
                    @endif
                    @if($k->formatted_period)
                    <p class="text-xs text-gray-500 dark:text-gray-400">Periode: {{ $k->formatted_period }}</p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    @if($mingguanList->count() > 0)
    <div class="pkg-card mb-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-2">
                <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">T</span>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Karakter Mingguan</h2>
                <span class="pkg-status-badge pkg-status-neutral">{{ $mingguanList->count() }}</span>
            </div>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($mingguanList as $k)
                <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:shadow-md transition bg-gradient-to-br from-purple-50 to-white dark:from-purple-900/10 dark:to-gray-800">
                    <div class="flex items-start justify-between mb-2">
                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ $k->nama }}</h3>
                        <span class="pkg-status-badge pkg-status-warning">+{{ $k->poin }} poin</span>
                    </div>
                    @if($k->deskripsi)
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ $k->deskripsi }}</p>
                    @endif
                    @if($k->formatted_period)
                    <p class="text-xs text-gray-500 dark:text-gray-400">Periode: {{ $k->formatted_period }}</p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    @if($bulananList->count() > 0)
    <div class="pkg-card mb-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-2">
                <span class="text-sm font-semibold text-violet-700 dark:text-violet-300">H</span>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Karakter Bulanan</h2>
                <span class="pkg-status-badge pkg-status-success">{{ $bulananList->count() }}</span>
            </div>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($bulananList as $k)
                <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:shadow-md transition bg-gradient-to-br from-emerald-50 to-white dark:from-emerald-900/10 dark:to-gray-800">
                    <div class="flex items-start justify-between mb-2">
                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ $k->nama }}</h3>
                        <span class="pkg-status-badge pkg-status-warning">+{{ $k->poin }} poin</span>
                    </div>
                    @if($k->deskripsi)
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ $k->deskripsi }}</p>
                    @endif
                    @if($k->formatted_period)
                    <p class="text-xs text-gray-500 dark:text-gray-400">Periode: {{ $k->formatted_period }}</p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    @if($harianList->count() === 0 && $mingguanList->count() === 0 && $bulananList->count() === 0)
    <div class="pkg-panel mb-6">
        <div class="pkg-empty-state py-12">
            <svg class="pkg-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h3 class="pkg-empty-title">Tidak ada tugas PKG aktif</h3>
            <p class="pkg-empty-copy">Tugas PKG akan muncul di sini sesuai jadwal dan status aktif yang diatur admin.</p>
        </div>
    </div>
    @endif

    <!-- Checklist / Verification Section -->
    <div class="pkg-card">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Data Checklist Siswa</h2>
                <!-- Quick Filters -->
                <form method="GET" action="{{ route('karakter-harian.index') }}" class="flex flex-wrap gap-2">
                    <select name="status" class="text-sm px-3 py-1.5 pkg-field">
                        <option value="">Semua Status</option>
                        <option value="unverified" {{ request('status') === 'unverified' ? 'selected' : '' }}>Menunggu</option>
                        <option value="verified" {{ request('status') === 'verified' ? 'selected' : '' }}>Terverifikasi</option>
                    </select>
                    <select name="kategori" class="text-sm px-3 py-1.5 pkg-field">
                        <option value="all">Semua Kategori</option>
                        <option value="harian" {{ request('kategori') === 'harian' ? 'selected' : '' }}>Harian</option>
                        <option value="mingguan" {{ request('kategori') === 'mingguan' ? 'selected' : '' }}>Mingguan</option>
                        <option value="bulanan" {{ request('kategori') === 'bulanan' ? 'selected' : '' }}>Bulanan</option>
                    </select>
                    <button type="submit" class="pkg-btn-primary text-sm px-4 py-1.5">Filter</button>
                </form>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Siswa</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Karakter</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($checklists as $checklist)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $checklist->siswa->nama }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-300">{{ $checklist->siswa->nis }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $checklist->karakter->nama }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-300">
                                <span class="pkg-status-badge pkg-status-neutral">{{ $checklist->karakter->kategori_label }}</span>
                            </div>

                            @if($checklist->student_note)
                            <div class="mt-1">
                                <div class="text-xs bg-blue-50 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 px-2 py-1 rounded border border-blue-200 dark:border-blue-700 block">
                                    <span class="font-bold">Catatan Siswa:</span> {{ Str::limit($checklist->student_note, 100) }}
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
                                oleh {{ $checklist->verifier->username ?? '-' }}
                            </div>
                            @else
                            <span class="pkg-status-badge pkg-status-warning">
                                Menunggu
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if(!$checklist->isVerified())
                            <button onclick="openVerifyModal({{ $checklist->id }}, '{{ addslashes($checklist->siswa->nama) }}', '{{ addslashes($checklist->karakter->nama) }}')" class="btn-success mr-3 rounded-lg px-3 py-1.5 text-xs text-white">
                                Verifikasi
                            </button>
                            @else
                            <button onclick="openUnverifyModal({{ $checklist->id }}, '{{ addslashes($checklist->siswa->nama) }}', '{{ addslashes($checklist->karakter->nama) }}', {{ $checklist->karakter->poin ?? 10 }})" class="pkg-btn-secondary mr-3 px-3 py-1.5 text-xs">
                                Batal Verifikasi
                            </button>
                            @endif
                            <button onclick="openDeleteModal({{ $checklist->id }}, '{{ addslashes($checklist->siswa->nama) }}', '{{ addslashes($checklist->karakter->nama) }}', {{ $checklist->isVerified() ? 'true' : 'false' }}, {{ $checklist->karakter->poin ?? 10 }})" class="btn-danger rounded-lg px-3 py-1.5 text-xs text-white font-medium">
                                Tolak Verifikasi
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-0">
                            <div class="pkg-empty-state py-8">
                                <h3 class="pkg-empty-title">Tidak ada data checklist</h3>
                                <p class="pkg-empty-copy">Belum ada checklist yang cocok dengan filter saat ini.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($checklists->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $checklists->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Verify Modal -->
<div id="verifyModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="pkg-modal p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Verifikasi Karakter</h3>
        <form id="verifyForm" method="POST">
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
                <button type="button" onclick="closeKarakterHarianModal('verifyModal')" class="pkg-btn-secondary px-4 py-2">Batal</button>
                <button type="submit" class="btn-success rounded-lg px-4 py-2 text-white">Verifikasi</button>
            </div>
        </form>
    </div>
</div>

<!-- Unverify Modal -->
<div id="unverifyModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="pkg-modal p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-yellow-600 dark:text-yellow-400 mb-4">Batal Verifikasi</h3>
        <form id="unverifyForm" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Siswa: <strong id="unverifySiswaName"></strong><br>
                    Karakter: <strong id="unverifyKarakterName"></strong>
                </p>
                <div class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                    <p class="text-sm text-red-700 dark:text-red-300">
                        <strong>Poin akan dikurangi <span id="unverifyPoints"></span> poin</strong> dari siswa ini.
                    </p>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Alasan Pembatalan <span class="text-red-500">*</span></label>
                <textarea name="reason" id="unverifyReason" rows="3" required minlength="5" class="w-full px-3 py-2 pkg-field" placeholder="Masukkan alasan pembatalan verifikasi..."></textarea>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeKarakterHarianModal('unverifyModal')" class="pkg-btn-secondary px-4 py-2">Batal</button>
                <button type="submit" class="pkg-btn-secondary px-4 py-2">Ya, Batalkan Verifikasi</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="pkg-modal p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-red-600 dark:text-red-400 mb-4">Tolak Verifikasi Tugas</h3>
        <form id="deleteForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="mb-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Siswa: <strong id="deleteSiswaName"></strong><br>
                    Karakter: <strong id="deleteKarakterName"></strong>
                </p>
                <div id="deletePointsWarning" class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg hidden">
                    <p class="text-sm text-red-700 dark:text-red-300">
                        <strong>Data sudah terverifikasi.</strong> Poin sebesar <span id="deletePoints"></span> poin akan dikurangi dari siswa.
                    </p>
                </div>
                <div class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <p class="text-xs text-blue-700 dark:text-blue-300">
                        Data yang dihapus bisa di-<strong>restore</strong> dari halaman <strong>Detail Riwayat Siswa</strong>.
                    </p>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Alasan Penolakan <span class="text-red-500">*</span></label>
                <textarea name="reason" id="deleteReason" rows="3" required minlength="5" class="w-full px-3 py-2 pkg-field" placeholder="Masukkan alasan penolakan tugas..."></textarea>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeKarakterHarianModal('deleteModal')" class="pkg-btn-secondary px-4 py-2">Batal</button>
                <button type="submit" class="btn-danger rounded-lg px-4 py-2 text-white">Ya, Tolak Verifikasi</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
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

function closeKarakterHarianModal(modalId) {
    const modal = document.getElementById(modalId);

    if (!modal) {
        return;
    }

    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Close modals on backdrop click
['verifyModal', 'unverifyModal', 'deleteModal'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(e) {
        if (e.target === this) closeKarakterHarianModal(id);
    });
});
</script>
@endpush
@endsection

