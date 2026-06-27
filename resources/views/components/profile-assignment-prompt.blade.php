@if(
    ($profileAssignmentConfig['enabled'] ?? false)
    && $profileAssignmentUser
    && $profileAssignmentNeedsConfirmation
)
    <div
        x-data="{ open: true }"
        x-show="open"
        class="fixed inset-0 z-[110] flex items-center justify-center overflow-y-auto bg-slate-950/75 p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="profile-assignment-title"
    >
        <form action="{{ $profileAssignmentUpdateUrl }}" method="POST" class="pkg-modal my-auto w-full max-w-lg p-5 shadow-2xl sm:p-6">
            @csrf
            @method('PUT')

            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 014-4h3m0 0l-3-3m3 3l-3 3M5 5h6a2 2 0 012 2v1M5 5a2 2 0 00-2 2v10a2 2 0 002 2h6"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h2 id="profile-assignment-title" class="text-lg font-bold text-slate-900 dark:text-white">
                        Perbarui Data Penempatan
                    </h2>
                    <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">
                        Pilih data terbaru agar rekap presensi dan target materi RPP tersusun dengan benar.
                    </p>
                </div>
            </div>

            <div class="mt-5 space-y-4">
                <div>
                    <label for="profile_assignment_kelompok" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">
                        Kelompok
                    </label>
                    <select id="profile_assignment_kelompok" name="kelompok" class="pkg-field w-full" required>
                        <option value="">Pilih kelompok terbaru</option>
                        @foreach($profileAssignmentGroups as $value => $label)
                            <option value="{{ $value }}" {{ old('kelompok', $profileAssignmentUser->kelompok) === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('kelompok')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>
                    @enderror
                </div>

                @if($profileAssignmentType === 'siswa')
                    <div>
                        <label for="profile_assignment_grade" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">
                            Kelas Sekolah
                        </label>
                        <select id="profile_assignment_grade" name="target_grade_override" class="pkg-field w-full" required>
                            <option value="">Pilih kelas sekolah saat ini</option>
                            @foreach($profileAssignmentGrades as $value => $label)
                                <option value="{{ $value }}" {{ old('target_grade_override', $profileAssignmentUser->target_grade_override) === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                            Pilihan ini menentukan target RPP yang tampil di akun siswa.
                        </p>
                        @error('target_grade_override')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
            </div>

            @if($profileAssignmentConfig['required'] ?? false)
                <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs leading-5 text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
                    Pembaruan ini wajib diselesaikan sebelum melanjutkan penggunaan akun.
                </div>
            @endif

            <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                @unless($profileAssignmentConfig['required'] ?? false)
                    <button type="button" class="btn-secondary justify-center" @click="open = false">
                        Nanti
                    </button>
                @endunless
                <button type="submit" class="btn-primary justify-center">
                    Simpan Pembaruan
                </button>
            </div>
        </form>
    </div>
@endif
