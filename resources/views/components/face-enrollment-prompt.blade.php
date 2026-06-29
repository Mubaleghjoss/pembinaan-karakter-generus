@php
    $faceEnrollmentConfig = $faceEnrollmentConfig ?? \App\Support\PopupManager::config('face_enrollment_prompt');
    $faceEnrollmentEnabled = (bool) ($faceEnrollmentConfig['enabled'] ?? false);
    $faceEnrollmentRequired = (bool) ($faceEnrollmentConfig['required'] ?? false);
    $skipCurrentPage = request()->routeIs('face-profile.show') || request()->routeIs('siswa.face-profile.show');
@endphp

@if(
    $faceEnrollmentEnabled
    && ($faceEnrollmentEnabledForUser ?? false)
    && ($faceEnrollmentUser ?? null)
    && !($faceEnrollmentProfileExists ?? false)
    && !($profileAssignmentPending ?? false)
    && !$skipCurrentPage
)
    <div
        x-data="{ open: true }"
        x-show="open"
        class="fixed inset-0 z-[115] flex items-center justify-center overflow-y-auto bg-slate-950/75 p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="face-enrollment-title"
    >
        <div class="pkg-modal my-auto w-full max-w-lg p-5 shadow-2xl sm:p-6">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 21a8 8 0 0116 0M3 7V5a2 2 0 012-2h2m10 0h2a2 2 0 012 2v2M3 17v2a2 2 0 002 2h2m10 0h2a2 2 0 002-2v-2"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h2 id="face-enrollment-title" class="text-lg font-bold text-slate-900 dark:text-white">
                        Daftarkan Wajah Presensi
                    </h2>
                    <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">
                        Akun ini belum punya data wajah awal. Daftarkan wajah satu kali agar bisa presensi lewat scan wajah publik.
                    </p>
                </div>
            </div>

            <div class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-xs leading-5 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-200">
                Gunakan kamera dengan pencahayaan cukup. Posisikan wajah dan bahu di tengah frame sampai sistem otomatis mengambil foto.
            </div>

            @if($faceEnrollmentRequired)
                <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs leading-5 text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
                    Pendaftaran wajah sedang diwajibkan oleh admin untuk akun siswa dan pamong.
                </div>
            @endif

            <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                @unless($faceEnrollmentRequired)
                    <button type="button" class="btn-secondary justify-center" @click="open = false">
                        Nanti
                    </button>
                @endunless
                <a href="{{ $faceEnrollmentUrl }}" class="btn-primary justify-center">
                    Buka Pendaftaran Wajah
                </a>
            </div>
        </div>
    </div>
@endif
