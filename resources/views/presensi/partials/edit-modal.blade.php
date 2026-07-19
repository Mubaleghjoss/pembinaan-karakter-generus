<div
    x-cloak
    x-show="editModal.open"
    @keydown.escape.window="closeEditPresensi()"
    class="fixed inset-0 z-[80] flex items-end justify-center bg-slate-950/60 p-0 backdrop-blur-sm sm:items-center sm:p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="edit-presensi-title"
>
    <div class="pkg-modal max-h-[92svh] w-full max-w-lg overflow-y-auto rounded-t-3xl sm:rounded-3xl" @click.outside="closeEditPresensi()">
        <form @submit.prevent="updatePresensi()">
            <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-4 py-4 dark:border-gray-700 sm:px-6">
                <div>
                    <h2 id="edit-presensi-title" class="text-lg font-bold text-gray-900 dark:text-white">Koreksi Presensi</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="editModal.siswa_nama"></p>
                </div>
                <button type="button" @click="closeEditPresensi()" class="inline-flex h-10 w-10 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800" aria-label="Tutup">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="grid gap-4 p-4 sm:grid-cols-2 sm:p-6">
                <div>
                    <label for="edit-presensi-tanggal" class="form-label">Tanggal</label>
                    <input id="edit-presensi-tanggal" type="date" x-model="editModal.tanggal" class="pkg-field w-full" required>
                </div>
                <div>
                    <label for="edit-presensi-status" class="form-label">Status Kehadiran</label>
                    <select id="edit-presensi-status" x-model="editModal.status" data-edit-presensi-status class="pkg-field w-full" required>
                        <option value="hadir">Hadir</option>
                        <option value="terlambat">Terlambat</option>
                        <option value="izin">Izin</option>
                        <option value="sakit">Sakit</option>
                        <option value="alpha">Tidak Hadir (Alpha)</option>
                    </select>
                </div>
                <div>
                    <label for="edit-presensi-masuk" class="form-label">Jam Masuk</label>
                    <input id="edit-presensi-masuk" type="time" x-model="editModal.jam_masuk" class="pkg-field w-full">
                </div>
                <div>
                    <label for="edit-presensi-keluar" class="form-label">Jam Keluar</label>
                    <input id="edit-presensi-keluar" type="time" x-model="editModal.jam_keluar" class="pkg-field w-full">
                </div>
                <div class="sm:col-span-2">
                    <label for="edit-presensi-keterangan" class="form-label">Keterangan</label>
                    <textarea id="edit-presensi-keterangan" x-model="editModal.keterangan" rows="3" maxlength="500" class="pkg-field w-full" placeholder="Alasan koreksi atau catatan tambahan"></textarea>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-2 border-t border-gray-200 px-4 py-4 dark:border-gray-700 sm:flex-row sm:justify-end sm:px-6">
                <button type="button" @click="closeEditPresensi()" class="btn-secondary px-4 py-2.5">Batal</button>
                <button type="submit" :disabled="editModal.saving" class="btn-success px-4 py-2.5 disabled:cursor-not-allowed disabled:opacity-60">
                    <span x-show="!editModal.saving">Simpan Koreksi</span>
                    <span x-show="editModal.saving">Menyimpan...</span>
                </button>
            </div>
        </form>
    </div>
</div>
