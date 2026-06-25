@extends('layouts.app')

@section('title', 'Assign Siswa - ' . $pamong->username)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Assign Siswa ke Pamong</h1>
            <p class="pkg-page-subheading">{{ $pamong->username }} | {{ $pamong->email }}</p>
        </div>
    </div>

    <div class="pkg-card p-6">
        <form action="{{ route('pamong.assign', $pamong) }}" method="POST">
            @csrf

            <!-- Filter by Kelas -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Filter Kelas</label>
                <select id="kelas-filter" class="w-full px-4 py-2 pkg-field">
                    <option value="">Semua Kelas</option>
                    @foreach($kelas as $k)
                        <option value="{{ $k->id }}">{{ $k->nama }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Student Selection -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Pilih Siswa</label>
                <div class="max-h-96 overflow-y-auto rounded-lg border border-gray-300 dark:border-gray-600">
                    @foreach($kelas as $k)
                    <div class="kelas-group" data-kelas="{{ $k->id }}">
                        <div class="bg-gray-100 dark:bg-gray-700 px-4 py-2 font-medium text-gray-700 dark:text-gray-300 sticky top-0">
                            {{ $k->nama }}
                        </div>
                        @foreach($k->siswa as $siswa)
                        <label class="flex items-center px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                            <input type="checkbox" name="siswa_ids[]" value="{{ $siswa->id }}" 
                                {{ in_array($siswa->id, $assignedIds) ? 'checked' : '' }}
                                class="pkg-check">
                            <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ $siswa->nama }} <span class="text-gray-400">({{ $siswa->nis }})</span>
                            </span>
                        </label>
                        @endforeach
                    </div>
                    @endforeach
                </div>
                @error('siswa_ids')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    <span id="selected-count">{{ count($assignedIds) }}</span> siswa dipilih
                </div>
                <div class="pkg-page-actions">
                    <a href="{{ route('pamong.show', $pamong) }}" class="btn-secondary">
                        Batal
                    </a>
                    <button type="submit" class="btn-primary">
                        Simpan Penugasan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const kelasFilter = document.getElementById('kelas-filter');
    const kelasGroups = document.querySelectorAll('.kelas-group');
    const checkboxes = document.querySelectorAll('input[name="siswa_ids[]"]');
    const selectedCount = document.getElementById('selected-count');

    // Filter by kelas
    kelasFilter.addEventListener('change', function() {
        const selectedKelas = this.value;
        kelasGroups.forEach(group => {
            if (!selectedKelas || group.dataset.kelas === selectedKelas) {
                group.style.display = 'block';
            } else {
                group.style.display = 'none';
            }
        });
    });

    // Update selected count
    function updateCount() {
        const count = document.querySelectorAll('input[name="siswa_ids[]"]:checked').length;
        selectedCount.textContent = count;
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateCount);
    });
});
</script>
@endpush
@endsection

