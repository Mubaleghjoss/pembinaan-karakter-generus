<div class="pkg-panel">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Pengaturan Tingkat Kelas</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Atur pilihan tingkat/level kelas yang tersedia</p>
    </div>

    <form action="{{ route('settings.update.kelas') }}" method="POST" class="p-6 space-y-6">
        @csrf
        @method('PUT')

        <div>
            <label for="tingkat_list" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Daftar Tingkat
            </label>
            <input type="text" 
                   id="tingkat_list" 
                   name="tingkat_list" 
                   value="{{ old('tingkat_list', $tingkatList) }}"
                   class="w-full px-4 py-2 pkg-field"
                   placeholder="X,XI,XII atau 7,8,9">
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Pisahkan dengan koma. Contoh: <code class="rounded bg-gray-100 px-1 dark:bg-gray-700">X,XI,XII</code> untuk SMA atau <code class="rounded bg-gray-100 px-1 dark:bg-gray-700">7,8,9</code> untuk SMP.
            </p>
            @error('tingkat_list')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Preview -->
        <div class="pkg-card-soft p-4">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Preview Pilihan Tingkat:</h4>
            <div class="flex flex-wrap gap-2" id="tingkat-preview">
                @foreach(explode(',', $tingkatList) as $tingkat)
                    @if(trim($tingkat))
                    <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full text-sm">
                        Kelas {{ trim($tingkat) }}
                    </span>
                    @endif
                @endforeach
            </div>
        </div>

        <!-- Preset Buttons -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Preset Cepat:</label>
            <div class="flex flex-wrap gap-2">
                <button type="button" onclick="setPreset('X,XI,XII')" 
                        class="btn-secondary text-sm !px-3 !py-1.5">
                    SMA (X, XI, XII)
                </button>
                <button type="button" onclick="setPreset('7,8,9')" 
                        class="btn-secondary text-sm !px-3 !py-1.5">
                    SMP (7, 8, 9)
                </button>
                <button type="button" onclick="setPreset('1,2,3,4,5,6')" 
                        class="btn-secondary text-sm !px-3 !py-1.5">
                    SD (1-6)
                </button>
                <button type="button" onclick="setPreset('10,11,12')" 
                        class="btn-secondary text-sm !px-3 !py-1.5">
                    SMK (10, 11, 12)
                </button>
                <button type="button" onclick="setPreset('A,B,C,D')" 
                        class="btn-secondary text-sm !px-3 !py-1.5">
                    Kelompok (A, B, C, D)
                </button>
            </div>
        </div>

        <div class="pkg-page-actions justify-end">
            <button type="submit" class="btn-primary">
                Simpan Pengaturan
            </button>
        </div>
    </form>
</div>

<script>
function setPreset(value) {
    document.getElementById('tingkat_list').value = value;
    updatePreview(value);
}

function updatePreview(value) {
    const preview = document.getElementById('tingkat-preview');
    const items = value.split(',').filter(v => v.trim());
    preview.innerHTML = items.map(item => 
        `<span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full text-sm">Kelas ${item.trim()}</span>`
    ).join('');
}

document.getElementById('tingkat_list').addEventListener('input', function(e) {
    updatePreview(e.target.value);
});
</script>

