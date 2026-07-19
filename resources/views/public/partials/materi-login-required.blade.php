<section class="border-t border-slate-200 p-5 sm:p-6 dark:border-slate-800" aria-labelledby="materi-login-title">
    <div class="pkg-card-soft p-5 sm:p-6">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-start">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-200">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11V7a4 4 0 10-8 0v4m-1 0h10a2 2 0 012 2v7H1v-7a2 2 0 012-2zm14 3v6m3-3h-6"/>
                </svg>
            </div>

            <div class="min-w-0 flex-1">
                <h2 id="materi-login-title" class="text-xl font-bold text-slate-950 dark:text-white">Login untuk membuka isi materi</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                    Daftar dan deskripsi materi dapat dilihat tanpa login. Pilih jenis akun Anda untuk membuka PDF, video, dan unduhan materi ini.
                </p>

                <div class="mt-5 grid gap-3 sm:grid-cols-3">
                    <a href="{{ route('siswa.login') }}" class="btn-primary justify-center text-sm">
                        Login Siswa
                    </a>
                    <a href="{{ route('ortu.login') }}" class="btn-secondary justify-center text-sm">
                        Login Orang Tua
                    </a>
                    <a href="{{ route('login') }}" class="btn-secondary justify-center text-sm">
                        Login Pamong / Admin
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
