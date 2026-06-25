@extends('layouts.app')

@section('title', 'Generate Kartu QR Pamong')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Generate Kartu QR Pamong</h1>
            <p class="pkg-page-subheading">Pilih pamong untuk dicetak kartu QR-nya.</p>
        </div>
    </div>

    <form action="{{ route('pamong.qr.generate.post') }}" method="POST" target="_blank">
        @csrf
        
        <!-- Selection Card -->
        <div class="pkg-card p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Pilih Pamong</h3>
                <label class="flex items-center">
                    <input type="checkbox" id="selectAll" class="pkg-check">
                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Pilih Semua</span>
                </label>
            </div>

            @if($pamongList->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-h-96 overflow-y-auto">
                @foreach($pamongList as $pamong)
                <label class="flex items-center rounded-lg bg-gray-50 p-3 cursor-pointer hover:bg-gray-100 transition dark:bg-gray-700 dark:hover:bg-gray-600">
                    <input type="checkbox" name="pamong_ids[]" value="{{ $pamong->id }}" class="pamong-checkbox pkg-check">
                    <div class="ml-3 flex items-center">
                        <div class="h-8 w-8 rounded-full bg-green-600 flex items-center justify-center mr-3">
                            <span class="text-white text-sm font-medium">{{ strtoupper(substr($pamong->username, 0, 1)) }}</span>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $pamong->name ?? $pamong->username }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $pamong->email }}</div>
                        </div>
                    </div>
                </label>
                @endforeach
            </div>
            @else
            <div class="pkg-empty-state py-8">
                <svg class="pkg-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <p class="pkg-empty-title">Tidak ada pamong aktif</p>
                <p class="pkg-empty-copy">Belum ada pamong aktif yang bisa dicetak kartunya.</p>
            </div>
            @endif
        </div>

        <!-- Selected Count -->
        <div class="flex items-center justify-between">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                <span id="selectedCount">0</span> pamong dipilih
            </p>
            <div class="pkg-page-actions">
                <a href="{{ route('pamong.index') }}" class="btn-secondary text-sm !px-4 !py-2">
                    Kembali
                </a>
                <button type="submit" class="btn-primary text-sm">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Cetak Kartu
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.pamong-checkbox');
    const selectedCount = document.getElementById('selectedCount');

    function updateCount() {
        const checked = document.querySelectorAll('.pamong-checkbox:checked').length;
        selectedCount.textContent = checked;
    }

    selectAll.addEventListener('change', function() {
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateCount();
    });

    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            selectAll.checked = document.querySelectorAll('.pamong-checkbox:checked').length === checkboxes.length;
            updateCount();
        });
    });
});
</script>
@endpush
@endsection

