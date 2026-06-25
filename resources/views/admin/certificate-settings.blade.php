@extends('layouts.app')

@section('title', 'Pengaturan Reward - Level ' . $level->level)

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="rewardSettings()">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Pengaturan Reward Level</h1>
            <p class="mt-1 text-gray-600 dark:text-gray-300">Upload template dan konfigurasi reward untuk setiap level</p>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-lg text-sm">
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="mb-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-3 rounded-lg text-sm">
        {{ session('error') }}
    </div>
    @endif

    <!-- Level Selector -->
    <div class="mb-6 pkg-panel p-4">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300 mr-2">Pilih Level:</span>
            @foreach($levels as $lvl)
            <a href="{{ route('admin.certificate.settings', $lvl->id) }}"
               class="px-4 py-2 rounded-lg text-sm font-medium transition-colors
                      {{ $lvl->id === $level->id ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                Level {{ $lvl->level }} - {{ $lvl->nama }}
            </a>
            @endforeach
        </div>
    </div>

    <!-- Current Level Info -->
    <div class="mb-6 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl p-5 text-white">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-white/20 rounded-full flex items-center justify-center text-3xl">
                {{ $level->badge_icon_url ?? 'LVL' }}
            </div>
            <div>
                <h2 class="text-xl font-bold">Level {{ $level->level }} - {{ $level->nama }}</h2>
                <p class="text-indigo-200 text-sm mt-1">
                    Poin: {{ number_format($level->min_points) }}{{ $level->max_points ? ' - ' . number_format($level->max_points) : '+' }}
                    | Benefits: {{ $level->benefits ? implode(', ', $level->benefits) : '-' }}
                </p>
            </div>
        </div>
    </div>

    <!-- Reward Types Grid -->
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($rewardTypes as $reward)
        @php $template = $reward['template']; @endphp
        <div class="pkg-panel overflow-hidden">
            <!-- Reward Header -->
            <div class="px-4 py-3 border-b dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-xl">{{ $reward['icon'] }}</span>
                    <h3 class="font-semibold text-gray-800 dark:text-white text-sm">{{ $reward['label'] }}</h3>
                </div>
                @if($reward['has_template'])
                <span class="px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-xs rounded-full font-medium">Aktif</span>
                @else
                <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-xs rounded-full font-medium">Belum Upload</span>
                @endif
            </div>

            <div class="p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">{{ $reward['desc'] }}</p>

                <!-- Template Preview -->
                @if($reward['has_template'])
                <div class="mb-3 border dark:border-gray-600 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-900">
                    <img src="{{ $template->template_url }}" alt="Template {{ $reward['label'] }}" 
                         class="w-full h-32 object-contain">
                </div>
                @endif

                <!-- Upload Form -->
                <form method="POST" action="{{ route('admin.certificate.upload-template', ['level' => $level->id, 'rewardType' => $reward['type']]) }}" 
                      enctype="multipart/form-data" class="space-y-3">
                    @csrf

                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $reward['has_template'] ? 'Ganti Template' : 'Upload Template' }} (PNG/JPG, max 5MB)
                        </label>
                        <input type="file" name="template" accept="image/png,image/jpeg" {{ $reward['has_template'] ? '' : 'required' }}
                            class="w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/30 dark:file:text-indigo-300">
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Posisi Y (%)</label>
                            <input type="number" name="name_y" value="{{ $template->name_y ?? 50 }}" min="0" max="100"
                                class="w-full px-2 py-1.5 text-xs border dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Font Size</label>
                            <input type="number" name="font_size" value="{{ $template->font_size ?? 36 }}" min="12" max="120"
                                class="w-full px-2 py-1.5 text-xs border dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Warna</label>
                            <input type="color" name="font_color" value="{{ $template->font_color ?? '#000000' }}"
                                class="w-full h-[30px] rounded-md border dark:border-gray-600 cursor-pointer">
                        </div>
                    </div>

                    <button type="submit" class="w-full px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded-lg transition-colors">
                        Simpan Template
                    </button>
                </form>

                <!-- Preview Button -->
                @if($reward['has_template'])
                <div class="mt-2 flex gap-2">
                    <a href="{{ route('admin.certificate.preview', ['level' => $level->id, 'rewardType' => $reward['type'], 'name' => $longestName]) }}"
                       target="_blank" class="flex-1 text-center px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        Preview
                    </a>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <!-- Info Box -->
    <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-xl p-4">
        <h3 class="font-semibold text-blue-800 dark:text-blue-200 text-sm">Tips Upload Template</h3>
        <ul class="mt-2 text-sm text-blue-700 dark:text-blue-300 space-y-1">
            <li>- Upload gambar A4 (landscape/portrait) dengan area kosong di tengah untuk nama siswa</li>
            <li>- <strong>Posisi Y</strong>: 0% = atas, 50% = tengah, 100% = bawah</li>
            <li>- Nama siswa akan otomatis di-center horizontal pada posisi Y yang dipilih</li>
            <li>- Preview menggunakan nama siswa terpanjang: <strong>{{ $longestName }}</strong></li>
            <li>- Siswa dapat download dalam format <strong>PNG</strong> (gambar) atau <strong>PDF</strong></li>
        </ul>
    </div>
</div>

<script>
function rewardSettings() {
    return {};
}
</script>
@endsection