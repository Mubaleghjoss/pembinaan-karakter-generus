@extends('layouts.ortu')

@section('title', 'Tugas PKG Anak')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Tugas PKG {{ $siswa->nama }}</h1>
            <p class="pkg-page-subheading">Pantau dan beri komentar pada tugas karakter anak.</p>
        </div>
    </div>

    @if($siswa->isGraduated())
        <div class="pkg-card-soft mb-6 border border-sky-200 p-4 dark:border-sky-900">
            <p class="font-semibold text-sky-900 dark:text-sky-100">Mode baca Alumni</p>
            <p class="mt-1 text-sm text-sky-800/80 dark:text-sky-200/80">Riwayat tugas tetap tersedia. Komentar baru dari akun Orang Tua dinonaktifkan setelah Generus menjadi Alumni.</p>
        </div>
    @endif

    @if(isset($pendingTasks) && $pendingTasks->count() > 0)
    <div class="mb-6 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-2xl p-4">
        <div class="flex items-start gap-3">
            <div class="p-2 bg-orange-100 dark:bg-orange-800 rounded-lg text-orange-600 dark:text-orange-200">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h2 class="text-lg font-bold text-orange-800 dark:text-orange-200 mb-1">
                    Mohon bantuan orang tua untuk mengingatkan tugas PKG yang belum dikerjakan.
                </h2>
                <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($pendingTasks as $task)
                    <div class="bg-white dark:bg-gray-800 p-3 rounded-lg border border-orange-100 dark:border-orange-900/50 shadow-sm flex justify-between items-center group hover:border-orange-300 transition-colors">
                        <div>
                            <p class="font-semibold text-gray-800 dark:text-gray-200 text-sm group-hover:text-orange-700 dark:group-hover:text-orange-300">
                                {{ $task->nama }}
                            </p>
                            <span class="text-xs text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full mt-1 inline-block">
                                {{ $task->kategori_label }}
                            </span>
                        </div>
                        <span class="text-xs font-bold text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-900/40 px-2 py-1 rounded-md">
                            +{{ $task->poin }} Poin
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-bold text-gray-800 dark:text-gray-200">Riwayat Tugas</h2>
    </div>

    @if($checklists->isEmpty())
    <div class="pkg-panel pkg-empty-state">
        <svg class="pkg-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z"/>
        </svg>
        <h3 class="pkg-empty-title">Belum Ada Riwayat Tugas</h3>
        <p class="pkg-empty-copy">Anak belum menyelesaikan tugas apa pun.</p>
    </div>
    @else
    <div class="space-y-4">
        @foreach($checklists as $checklist)
        <div class="pkg-panel overflow-hidden" x-data="{ showComment: false }">
            <div class="p-5">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-2">
                            @if($checklist->verified_at)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300">Terverifikasi</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900/40 text-yellow-800 dark:text-yellow-300">Sudah dinyatakan selesai oleh anak</span>
                            @endif
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mt-2">{{ $checklist->karakter->nama ?? 'Tugas' }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Kategori: {{ $checklist->karakter->kategori_label ?? '-' }} | Dikerjakan: {{ $checklist->created_at->format('d M Y') }}
                        </p>
                        @if($checklist->verified_at)
                        <p class="text-sm text-green-600 dark:text-green-400 mt-1">
                            Diverifikasi: {{ $checklist->verified_at->format('d M Y H:i') }}
                            oleh {{ $checklist->verifier->username ?? 'Pamong' }}
                        </p>
                        @endif

                        @if($checklist->student_note)
                        <div class="mt-2 p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-800">
                            <p class="text-xs text-blue-600 dark:text-blue-300 font-medium">Bukti atau catatan anak:</p>
                            <p class="text-sm text-gray-700 dark:text-gray-300 italic">"{{ $checklist->student_note }}"</p>
                        </div>
                        @endif
                    </div>
                </div>
                @unless($siswa->isGraduated())
                <div class="mt-4">
                    <button @click="showComment = !showComment" class="btn-secondary w-full sm:w-auto">
                        Beri komentar atau penyaksian
                    </button>
                </div>
                @endunless

                @if(isset($comments[$checklist->id]) && $comments[$checklist->id]->count() > 0)
                <div class="mt-4 space-y-2">
                    @foreach($comments[$checklist->id] as $comment)
                    <div class="bg-teal-50 dark:bg-teal-900/30 rounded-lg p-3 border border-teal-200 dark:border-teal-800">
                        <p class="text-sm text-teal-800 dark:text-teal-200">{{ $comment->comment }}</p>
                        <p class="text-xs text-teal-500 dark:text-teal-400 mt-1">{{ $comment->created_at->format('d M Y H:i') }}</p>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            @unless($siswa->isGraduated())
            <div x-show="showComment" x-transition class="border-t border-gray-200 dark:border-gray-600 p-5 bg-gray-50 dark:bg-gray-800/70">
                <form action="{{ route('ortu.tugas.comment', $checklist->id) }}" method="POST">
                    @csrf
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Tambah Komentar Ortu</label>
                    <textarea
                        name="comment"
                        rows="3"
                        required
                        minlength="3"
                        maxlength="1000"
                        class="w-full pkg-field text-sm"
                        placeholder="Contoh: Saya ortu sudah menyaksikan dan mendampingi anak dalam mengerjakan tugas ini..."></textarea>
                    <div class="mt-3 flex items-center justify-end gap-3">
                        <button type="button" @click="showComment = false" class="btn-secondary">
                            Batal
                        </button>
                        <button type="submit" class="btn-primary">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            Kirim Komentar
                        </button>
                    </div>
                </form>
            </div>
            @endunless
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
