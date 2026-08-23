@extends('layouts.public')

@section('title', 'Pendaftaran Berhasil - ' . ($theme->app_name ?? 'PKG'))

@section('content')
<div class="py-8 sm:py-12" x-data="{ copied: '' }">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <section class="pkg-panel-lg overflow-hidden">
            <div class="bg-emerald-600 px-5 py-6 text-center text-white sm:px-8">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-white/20">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/></svg>
                </div>
                <h1 class="mt-3 text-2xl font-black">{{ $isNewAccount ? 'Pendaftaran Berhasil' : 'Biodata & Surat Berhasil Diperbarui' }}</h1>
                <p class="mt-1 text-sm text-emerald-50">Akun Generus dan Orang Tua siap digunakan. Simpan &amp; bagikan informasi di bawah ini.</p>
            </div>

            <div class="space-y-6 p-5 sm:p-8">
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-100">
                    <p class="font-bold">Penting</p>
                    <p class="mt-1">Password akun Generus &amp; Orang Tua telah diselaraskan ke <span class="font-bold">NIS</span> agar keduanya pasti bisa masuk. Setelah berhasil login, sangat disarankan mengganti password masing-masing.</p>
                </div>

                @if(!empty($accountInfo))
                @php $st = $accountInfo['student']; $pr = $accountInfo['parent']; @endphp

                {{-- Kartu Akun Generus / Anak --}}
                <div class="pkg-card-soft rounded-2xl p-4 sm:p-5">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-xs font-bold uppercase tracking-wide text-blue-600 dark:text-blue-300">Akun Generus (Anak)</p>
                        <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-[11px] font-bold text-blue-700 dark:bg-blue-900/40 dark:text-blue-200">{{ $st['nama'] }}</span>
                    </div>
                    <dl class="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                        <div class="rounded-xl bg-white/70 p-3 dark:bg-gray-800/50">
                            <dt class="text-gray-500 dark:text-gray-400">Login (NIS)</dt>
                            <dd class="mt-0.5 flex items-center justify-between gap-2">
                                <span class="font-mono font-bold text-gray-900 dark:text-white">{{ $st['login'] }}</span>
                                <button type="button" class="text-xs font-semibold text-blue-600 hover:underline dark:text-blue-300"
                                    @click="navigator.clipboard.writeText(@js($st['login'])); copied='st-login'; setTimeout(()=>copied='',1500)">
                                    <span x-show="copied!=='st-login'">Salin</span><span x-show="copied==='st-login'" x-cloak>Tersalin ✓</span>
                                </button>
                            </dd>
                        </div>
                        <div class="rounded-xl bg-white/70 p-3 dark:bg-gray-800/50">
                            <dt class="text-gray-500 dark:text-gray-400">Password</dt>
                            <dd class="mt-0.5 flex items-center justify-between gap-2">
                                <span class="font-mono font-bold text-gray-900 dark:text-white">{{ $st['password'] }}</span>
                                <button type="button" class="text-xs font-semibold text-blue-600 hover:underline dark:text-blue-300"
                                    @click="navigator.clipboard.writeText(@js($st['password'])); copied='st-pass'; setTimeout(()=>copied='',1500)">
                                    <span x-show="copied!=='st-pass'">Salin</span><span x-show="copied==='st-pass'" x-cloak>Tersalin ✓</span>
                                </button>
                            </dd>
                        </div>
                    </dl>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ $st['login_url'] }}" target="_blank" rel="noopener" class="pkg-btn-primary inline-flex px-4 py-2 text-sm">Buka Login Siswa</a>
                        @if($st['wa'])
                            <a href="{{ $st['wa'] }}?text={{ $st['wa_text'] }}" target="_blank" rel="noopener" class="btn-success inline-flex items-center gap-1.5 px-4 py-2 text-sm">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                Kirim ke WA Anak
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Kartu Akun Orang Tua --}}
                <div class="pkg-card-soft rounded-2xl p-4 sm:p-5">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-xs font-bold uppercase tracking-wide text-teal-600 dark:text-teal-300">Akun Orang Tua</p>
                        @if($pr['nama'])<span class="rounded-full bg-teal-100 px-2.5 py-0.5 text-[11px] font-bold text-teal-700 dark:bg-teal-900/40 dark:text-teal-200">{{ $pr['nama'] }}</span>@endif
                    </div>
                    <dl class="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                        <div class="rounded-xl bg-white/70 p-3 dark:bg-gray-800/50">
                            <dt class="text-gray-500 dark:text-gray-400">Username</dt>
                            <dd class="mt-0.5 flex items-center justify-between gap-2">
                                <span class="font-mono font-bold text-gray-900 dark:text-white">{{ $pr['username'] }}</span>
                                <button type="button" class="text-xs font-semibold text-teal-600 hover:underline dark:text-teal-300"
                                    @click="navigator.clipboard.writeText(@js($pr['username'])); copied='pr-user'; setTimeout(()=>copied='',1500)">
                                    <span x-show="copied!=='pr-user'">Salin</span><span x-show="copied==='pr-user'" x-cloak>Tersalin ✓</span>
                                </button>
                            </dd>
                        </div>
                        <div class="rounded-xl bg-white/70 p-3 dark:bg-gray-800/50">
                            <dt class="text-gray-500 dark:text-gray-400">Password</dt>
                            <dd class="mt-0.5 flex items-center justify-between gap-2">
                                <span class="font-mono font-bold text-gray-900 dark:text-white">{{ $pr['password'] }}</span>
                                <button type="button" class="text-xs font-semibold text-teal-600 hover:underline dark:text-teal-300"
                                    @click="navigator.clipboard.writeText(@js($pr['password'])); copied='pr-pass'; setTimeout(()=>copied='',1500)">
                                    <span x-show="copied!=='pr-pass'">Salin</span><span x-show="copied==='pr-pass'" x-cloak>Tersalin ✓</span>
                                </button>
                            </dd>
                        </div>
                    </dl>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ $pr['login_url'] }}" target="_blank" rel="noopener" class="btn-success inline-flex px-4 py-2 text-sm">Buka Login Orang Tua</a>
                        @if($pr['wa'])
                            <a href="{{ $pr['wa'] }}?text={{ $pr['wa_text'] }}" target="_blank" rel="noopener" class="btn-success inline-flex items-center gap-1.5 px-4 py-2 text-sm">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                Kirim ke WA Orang Tua
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Cara login & manfaat akun --}}
                <div class="rounded-2xl border border-indigo-100 bg-indigo-50/80 p-4 text-sm leading-7 text-slate-700 dark:border-indigo-900/40 dark:bg-indigo-950/30 dark:text-slate-200">
                    <p class="font-bold text-indigo-700 dark:text-indigo-200">Cara Login</p>
                    <ul class="mt-1 list-disc space-y-1 pl-5">
                        <li><span class="font-semibold">Anak:</span> buka halaman Login Siswa, masukkan NIS sebagai login &amp; password, lalu ganti password.</li>
                        <li><span class="font-semibold">Orang Tua:</span> buka halaman Login Orang Tua, masukkan username &amp; password (NIS), lalu ganti password.</li>
                    </ul>
                    <p class="mt-3 font-bold text-indigo-700 dark:text-indigo-200">Manfaat Akun Orang Tua</p>
                    <p class="mt-1">Dengan akun ini, Orang Tua dapat memantau <span class="font-semibold">Tugas PKG</span> ananda — sehingga bisa turut mengingatkan dan membantu melancarkan program PKG bersama-sama.</p>
                </div>
                @endif

                <a href="{{ route('public.generus-registration.short.pdf', ['registration' => $registration, 'downloadToken' => $downloadToken]) }}" class="btn-secondary flex w-full items-center justify-center gap-2 px-5 py-3 font-bold">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16V4m0 12-4-4m4 4 4-4M5 20h14"/></svg>
                    Unduh PDF Surat Pernyataan
                </a>
            </div>
        </section>
    </div>
</div>
@endsection
