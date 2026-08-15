@extends('layouts.app')

@section('title', 'Atur Binaan - ' . ($pamong->name ?: $pamong->username))

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading text-balance">Atur Generus Binaan</h1>
            <p class="pkg-page-subheading text-pretty">{{ $pamong->name ?: $pamong->username }} · Pilih Generus dari berbagai kelas sekolah.</p>
        </div>
    </div>

    <div class="pkg-card p-6">
        <form action="{{ route('pamong.assign', $pamong) }}" method="POST">
            @csrf

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Filter Kelas Sekolah</label>
                <select id="grade-filter" class="w-full pkg-field">
                    <option value="">Semua Kelas Sekolah</option>
                    @foreach($gradeOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                    <option value="unconfirmed">Belum dikonfirmasi</option>
                </select>
            </div>

            <!-- Student Selection -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Pilih Siswa</label>
                <div class="max-h-96 overflow-y-auto rounded-lg border border-gray-300 dark:border-gray-600">
                    @foreach($gradeGroups as $grade => $students)
                    <div class="grade-group" data-grade="{{ $grade }}">
                        <div class="bg-gray-100 dark:bg-gray-700 px-4 py-2 font-medium text-gray-700 dark:text-gray-300 sticky top-0">
                            {{ $gradeOptions[$grade] ?? 'Belum dikonfirmasi' }}
                        </div>
                        @foreach($students as $siswa)
                        <label class="flex min-h-11 items-center px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                            <input type="checkbox" name="siswa_ids[]" value="{{ $siswa->id }}" 
                                {{ in_array($siswa->id, $assignedIds) ? 'checked' : '' }}
                                class="pkg-check">
                            <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                                <span class="font-medium">{{ $siswa->nama }}</span> <span class="text-gray-400 tabular-nums">({{ $siswa->nis }})</span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $siswa->kelompok_label }} · {{ $siswa->pamong_assignments_count }} Pamong aktif</span>
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
    const gradeFilter = document.getElementById('grade-filter');
    const gradeGroups = document.querySelectorAll('.grade-group');
    const checkboxes = document.querySelectorAll('input[name="siswa_ids[]"]');
    const selectedCount = document.getElementById('selected-count');

    gradeFilter.addEventListener('change', function() {
        const selectedGrade = this.value;
        gradeGroups.forEach(group => {
            if (!selectedGrade || group.dataset.grade === selectedGrade) {
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

