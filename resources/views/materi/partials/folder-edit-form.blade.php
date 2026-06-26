@php
    $currentFolder = $currentFolder ?? $folder;
    $folderOptions = $folderOptions ?? collect();
@endphp

<form method="POST" action="{{ route('materi.folders.update', $currentFolder) }}" class="mt-3 space-y-3 rounded-lg border border-gray-200 p-3 dark:border-gray-700">
    @csrf
    @method('PATCH')
    <div>
        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Nama</label>
        <input type="text" name="name" value="{{ old('name', $currentFolder->name) }}" class="w-full pkg-field text-sm" required>
    </div>
    <div>
        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Folder Induk</label>
        <select name="parent_id" class="w-full pkg-field text-sm">
            <option value="">Folder Utama</option>
            @foreach($folderOptions as $option)
                @continue((int) $option->id === (int) $currentFolder->id)
                <option value="{{ $option->id }}" @selected((int) old('parent_id', $currentFolder->parent_id) === (int) $option->id)>
                    {{ $option->display_name ?? $option->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Keterangan</label>
        <textarea name="description" rows="2" class="w-full pkg-field text-sm">{{ old('description', $currentFolder->description) }}</textarea>
    </div>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Urutan</label>
            <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $currentFolder->sort_order) }}" class="w-full pkg-field text-sm">
        </div>
        <label class="mt-6 inline-flex items-center gap-2 text-xs font-semibold text-gray-700 dark:text-gray-300">
            <input type="checkbox" name="is_active" value="1" class="pkg-check rounded" @checked(old('is_active', $currentFolder->is_active))>
            Aktif
        </label>
    </div>
    <button type="submit" class="btn-primary w-full justify-center text-xs">Simpan Folder</button>
</form>
