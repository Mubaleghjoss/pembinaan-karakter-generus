{{-- Siswa Modals --}}

<!-- Account Info Modal (shown after creating new student) -->
<div id="account-info-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="account-info-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom pkg-modal text-left overflow-hidden transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
            <div class="bg-green-600 px-4 py-3">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-white mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="text-lg font-medium text-white">Siswa Berhasil Ditambahkan!</h3>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Berikut adalah informasi akun siswa untuk login:</p>
                
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-3">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Nama</p>
                        <p class="font-semibold text-gray-900 dark:text-white" id="account-info-nama">-</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Username (NIS)</p>
                        <p class="font-mono font-semibold text-blue-600 dark:text-blue-400 text-lg" id="account-info-username">-</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Password</p>
                        <p class="font-mono font-semibold text-green-600 dark:text-green-400 text-lg" id="account-info-password">-</p>
                    </div>
                    <div class="pt-2 border-t border-gray-200 dark:border-gray-600">
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">URL Login</p>
                        <p class="font-mono text-sm text-gray-700 dark:text-gray-300">{{ url('/siswa/login') }}</p>
                    </div>
                </div>
                
                <div class="mt-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3">
                    <p class="text-sm text-amber-700 dark:text-amber-300">
                        <strong>Penting:</strong> Catat atau salin informasi ini. Password default adalah NIS siswa.
                    </p>
                </div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 flex flex-col sm:flex-row-reverse gap-2">
                <button type="button" onclick="closeAccountInfoModal()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto sm:text-sm">
                    Selesai
                </button>
                <button type="button" onclick="copyAccountInfo()" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto sm:text-sm dark:bg-gray-600 dark:text-gray-200 dark:border-gray-500 dark:hover:bg-gray-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    Salin Info
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Student Modal -->
<div id="student-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeStudentModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom pkg-modal text-left overflow-hidden transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="student-form" onsubmit="saveStudent(event)">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4" id="modal-title-text">Tambah Siswa</h3>
                    <input type="hidden" id="student-id">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">NIS</label>
                            <input type="text" id="nis" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama</label>
                            <input type="text" id="nama" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Jenis Kelamin</label>
                            <select id="jenis_kelamin" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Pilih Jenis Kelamin</option>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kelas</label>
                            <select id="kelas_id" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Pilih Kelas</option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" id="kelas-pamong-info"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kelompok</label>
                            <select id="kelompok" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Pilih Kelompok</option>
                                @foreach(($kelompokOptions ?? []) as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Simpan
                    </button>
                    <button type="button" onclick="closeStudentModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-600 dark:text-gray-200 dark:border-gray-500 dark:hover:bg-gray-500">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div id="import-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="import-modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeImportModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom pkg-modal text-left overflow-hidden transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="import-form" onsubmit="importStudents(event)" enctype="multipart/form-data">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Import Data Siswa</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">File Excel</label>
                            <input type="file" id="import-file" name="file" accept=".xlsx,.xls,.csv" required class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900 dark:file:text-blue-200">
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            <p>Format file: .xlsx, .xls, atau .csv</p>
                            <p>Kolom yang diperlukan: NIS, Nama, Jenis Kelamin, Kelas</p>
                            <p class="mt-1 text-xs">Setiap kelas bisa memiliki beberapa pamong</p>
                            <a href="/siswa/template-import" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">Unduh Template</a>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Import
                    </button>
                    <button type="button" onclick="closeImportModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-600 dark:text-gray-200 dark:border-gray-500 dark:hover:bg-gray-500">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Reset Password Modal -->
<div id="bulk-reset-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="bulk-reset-modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeBulkResetModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom pkg-modal text-left overflow-hidden transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="bulk-reset-form" onsubmit="bulkResetPassword(event)">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Reset Password Massal</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pilih Kelas</label>
                            <select id="bulk-reset-kelas" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Semua Kelas</option>
                            </select>
                        </div>
                        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                            <div class="flex">
                                <svg class="h-5 w-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                <div class="ml-3">
                                    <p class="text-sm text-amber-700 dark:text-amber-300">
                                        Password akan direset ke NIS masing-masing siswa.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-amber-600 text-base font-medium text-white hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Reset Password
                    </button>
                    <button type="button" onclick="closeBulkResetModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-600 dark:text-gray-200 dark:border-gray-500 dark:hover:bg-gray-500">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Store kelas data for pamong info display
let kelasData = [];

// Modal Functions
function openAddModal() {
    document.getElementById('modal-title-text').textContent = 'Tambah Siswa';
    document.getElementById('student-id').value = '';
    document.getElementById('student-form').reset();
    document.getElementById('kelompok').value = '';
    document.getElementById('kelas-pamong-info').textContent = '';
    loadKelasForModal();
    document.getElementById('student-modal').classList.remove('hidden');
}

function closeStudentModal() {
    document.getElementById('student-modal').classList.add('hidden');
}

function editStudent(student) {
    document.getElementById('modal-title-text').textContent = 'Edit Siswa';
    document.getElementById('student-id').value = student.id;
    document.getElementById('nis').value = student.nis;
    document.getElementById('nama').value = student.nama;
    document.getElementById('jenis_kelamin').value = student.jenis_kelamin;
    document.getElementById('kelompok').value = student.kelompok || '';
    loadKelasForModal(student.kelas_id);
    document.getElementById('student-modal').classList.remove('hidden');
}

async function loadKelasForModal(selectedId = null) {
    try {
        const response = await fetch('/kelas-list', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
            }
        });
        const data = await response.json();
        kelasData = data.data || data;
        
        const select = document.getElementById('kelas_id');
        select.innerHTML = '<option value="">Pilih Kelas</option>';
        kelasData.forEach(kelas => {
            const pamongNames = (kelas.pamong || []).map(p => p.nama).join(', ');
            const option = document.createElement('option');
            option.value = kelas.id;
            option.textContent = kelas.nama + ' - ' + kelas.jumlah_siswa + ' siswa';
            option.dataset.pamong = pamongNames || 'Belum ada pamong';
            if (selectedId && kelas.id == selectedId) {
                option.selected = true;
            }
            select.appendChild(option);
        });
        
        // Update pamong info on change
        select.addEventListener('change', updatePamongInfo);
        if (selectedId) {
            updatePamongInfo();
        }
    } catch (error) {
        console.error('Error loading kelas:', error);
    }
}

function updatePamongInfo() {
    const select = document.getElementById('kelas_id');
    const infoEl = document.getElementById('kelas-pamong-info');
    const selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption && selectedOption.dataset.pamong) {
        infoEl.textContent = 'Pamong: ' + selectedOption.dataset.pamong;
    } else {
        infoEl.textContent = '';
    }
}

async function saveStudent(event) {
    event.preventDefault();
    
    const id = document.getElementById('student-id').value;
    const url = id ? `/siswa/${id}` : '/siswa';
    const method = id ? 'PUT' : 'POST';
    const isNew = !id;
    
    const nis = document.getElementById('nis').value;
    const nama = document.getElementById('nama').value;
    
    const data = {
        nis: nis,
        nama: nama,
        jenis_kelamin: document.getElementById('jenis_kelamin').value,
        kelas_id: document.getElementById('kelas_id').value,
        kelompok: document.getElementById('kelompok').value
    };
    
    try {
        const response = await fetch(url, {
            method: method,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        if (response.ok) {
            closeStudentModal();
            
            // Show account info for new student
            if (isNew) {
                showAccountInfo(nama, nis, nis);
            } else {
                location.reload();
            }
        } else {
            window.showNotification(result.message || 'Gagal menyimpan data', 'error');
        }
    } catch (error) {
        console.error('Error saving student:', error);
        window.showNotification('Terjadi kesalahan saat menyimpan data', 'error');
    }
}

function showAccountInfo(nama, username, password) {
    document.getElementById('account-info-nama').textContent = nama;
    document.getElementById('account-info-username').textContent = username;
    document.getElementById('account-info-password').textContent = password;
    document.getElementById('account-info-modal').classList.remove('hidden');
}

async function deleteStudent(student) {
    const confirmed = await window.showConfirmation(`Hapus siswa ${student.nama}?`, {
        title: 'Hapus siswa',
        confirmText: 'Hapus',
        tone: 'danger'
    });
    if (!confirmed) return;
    
    try {
        const response = await fetch(`/siswa/${student.id}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
            }
        });
        
        if (response.ok) {
            location.reload();
        } else {
            const result = await response.json();
            window.showNotification(result.message || 'Gagal menghapus data', 'error');
        }
    } catch (error) {
        console.error('Error deleting student:', error);
        window.showNotification('Terjadi kesalahan saat menghapus data', 'error');
    }
}

// Import Modal
function openImportModal() {
    document.getElementById('import-modal').classList.remove('hidden');
}

function closeImportModal() {
    document.getElementById('import-modal').classList.add('hidden');
}

async function importStudents(event) {
    event.preventDefault();
    
    const formData = new FormData();
    formData.append('file', document.getElementById('import-file').files[0]);
    
    try {
        const response = await fetch('/siswa/import', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
            },
            body: formData
        });
        
        const result = await response.json();
        if (response.ok) {
            closeImportModal();
            window.showNotification(result.message || 'Import berhasil', 'success');
            location.reload();
        } else {
            window.showNotification(result.message || 'Gagal import data', 'error');
        }
    } catch (error) {
        console.error('Error importing:', error);
        window.showNotification('Terjadi kesalahan saat impor data', 'error');
    }
}

// Bulk Reset Modal
function openBulkResetModal() {
    loadKelasForBulkReset();
    document.getElementById('bulk-reset-modal').classList.remove('hidden');
}

function closeBulkResetModal() {
    document.getElementById('bulk-reset-modal').classList.add('hidden');
}

async function loadKelasForBulkReset() {
    try {
        const response = await fetch('/kelas-list', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
            }
        });
        const data = await response.json();
        const classes = data.data || data;
        
        const select = document.getElementById('bulk-reset-kelas');
        select.innerHTML = '<option value="">Semua Kelas</option>';
        classes.forEach(kelas => {
            const option = document.createElement('option');
            option.value = kelas.id;
            option.textContent = kelas.nama;
            select.appendChild(option);
        });
    } catch (error) {
        console.error('Error loading classes:', error);
    }
}

async function bulkResetPassword(event) {
    event.preventDefault();
    
    const kelasId = document.getElementById('bulk-reset-kelas').value;
    
    const confirmed = await window.showConfirmation('Yakin ingin reset password? Password akan direset ke NIS masing-masing siswa.', {
        title: 'Reset password massal',
        confirmText: 'Reset',
        tone: 'warning'
    });
    if (!confirmed) return;
    
    try {
        const response = await fetch('/siswa/bulk-reset-password', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
            },
            body: JSON.stringify({ kelas_id: kelasId })
        });
        
        const result = await response.json();
        if (response.ok) {
            closeBulkResetModal();
            window.showNotification(result.message || 'Password berhasil direset', 'success');
        } else {
            window.showNotification(result.message || 'Gagal reset password', 'error');
        }
    } catch (error) {
        console.error('Error resetting passwords:', error);
        window.showNotification('Terjadi kesalahan saat reset password', 'error');
    }
}

function closeAccountInfoModal() {
    document.getElementById('account-info-modal').classList.add('hidden');
    location.reload();
}

function copyAccountInfo() {
    const nama = document.getElementById('account-info-nama').textContent;
    const username = document.getElementById('account-info-username').textContent;
    const password = document.getElementById('account-info-password').textContent;
    
    const text = `Nama: ${nama}\nUsername: ${username}\nPassword: ${password}\nLogin di: ${window.location.origin}/siswa/login`;
    
    navigator.clipboard.writeText(text).then(() => {
        window.showNotification('Info akun berhasil disalin', 'success');
    }).catch(() => {
        prompt('Salin info akun:', text);
    });
}

// Expose functions globally
window.openAddModal = openAddModal;
window.openImportModal = openImportModal;
window.openBulkResetModal = openBulkResetModal;
window.editStudent = editStudent;
window.deleteStudent = deleteStudent;
window.closeAccountInfoModal = closeAccountInfoModal;
window.copyAccountInfo = copyAccountInfo;
</script>
@endpush


{{--
Biodata modal duplicate disabled; active modal is included inside siswaManager scope.
<div x-ignore
     x-cloak
     @click.self="showBiodataModal = false"
     class="hidden fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 items-center justify-center p-4"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
    <div class="pkg-modal max-w-4xl w-full max-h-[90vh] overflow-hidden"
         @click.stop
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-90"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-90">
        
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-purple-600 to-blue-600">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Biodata Siswa</h3>
                        <p class="text-sm text-purple-100" x-text="biodataStudent?.nama"></p>
                    </div>
                </div>
                <button @click="showBiodataModal = false" class="text-white hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Content -->
        <div class="px-6 py-4 max-h-[calc(90vh-180px)] overflow-y-auto">
            <!-- Photo Section -->
            <div class="flex justify-center mb-6">
                <div class="relative">
                    <template x-if="biodataStudent?.foto_url">
                        <img :src="biodataStudent.foto_url" :alt="biodataStudent.nama" class="w-32 h-32 rounded-full object-cover border-4 border-purple-200 dark:border-purple-800">
                    </template>
                    <template x-if="!biodataStudent?.foto_url">
                        <div class="w-32 h-32 rounded-full bg-gradient-to-br from-purple-500 to-blue-600 flex items-center justify-center border-4 border-purple-200 dark:border-purple-800">
                            <span class="text-white font-bold text-4xl" x-text="biodataStudent?.nama?.charAt(0).toUpperCase()"></span>
                        </div>
                    </template>
                </div>
            </div>
            
            <!-- Form -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Data Pribadi -->
                <div class="col-span-2">
                    <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Data Pribadi
                    </h4>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nama Lengkap</label>
                    <input type="text" 
                           x-model="biodataForm.nama"
                           :disabled="!biodataEditing"
                           class="w-full pkg-field disabled:bg-gray-100 dark:disabled:bg-gray-800 disabled:cursor-not-allowed">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">NIS</label>
                    <input type="text" 
                           x-model="biodataForm.nis"
                           :disabled="!biodataEditing"
                           class="w-full pkg-field disabled:bg-gray-100 dark:disabled:bg-gray-800 disabled:cursor-not-allowed">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Jenis Kelamin</label>
                    <select x-model="biodataForm.jenis_kelamin"
                            :disabled="!biodataEditing"
                            class="w-full pkg-field disabled:bg-gray-100 dark:disabled:bg-gray-800 disabled:cursor-not-allowed">
                        <option value="">Pilih Jenis Kelamin</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tanggal Lahir</label>
                    <input type="date" 
                           x-model="biodataForm.tanggal_lahir"
                           :disabled="!biodataEditing"
                           class="w-full pkg-field disabled:bg-gray-100 dark:disabled:bg-gray-800 disabled:cursor-not-allowed">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">No. Telepon</label>
                    <input type="text" 
                           x-model="biodataForm.phone"
                           :disabled="!biodataEditing"
                           placeholder="08xxxxxxxxxx"
                           class="w-full pkg-field disabled:bg-gray-100 dark:disabled:bg-gray-800 disabled:cursor-not-allowed">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kelas</label>
                    <select x-model="biodataForm.kelas_id"
                            :disabled="!biodataEditing"
                            class="w-full pkg-field disabled:bg-gray-100 dark:disabled:bg-gray-800 disabled:cursor-not-allowed">
                        <option value="">Pilih Kelas</option>
                        <template x-for="kelas in classes" :key="kelas.id">
                            <option :value="kelas.id" x-text="kelas.nama"></option>
                        </template>
                    </select>
                </div>
                
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kelompok</label>
                    <select x-model="biodataForm.kelompok"
                            :disabled="!biodataEditing"
                            class="w-full pkg-field disabled:bg-gray-100 dark:disabled:bg-gray-800 disabled:cursor-not-allowed">
                        <option value="">Pilih Kelompok</option>
                        @foreach(($kelompokOptions ?? []) as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                
                <!-- Data Wali -->
                <div class="col-span-2 mt-4">
                    <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        Data Wali/Orang Tua
                    </h4>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nama Wali</label>
                    <input type="text" 
                           x-model="biodataForm.nama_wali"
                           :disabled="!biodataEditing"
                           class="w-full pkg-field disabled:bg-gray-100 dark:disabled:bg-gray-800 disabled:cursor-not-allowed">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">No. Telepon Wali</label>
                    <input type="text" 
                           x-model="biodataForm.phone_wali"
                           :disabled="!biodataEditing"
                           placeholder="08xxxxxxxxxx"
                           class="w-full pkg-field disabled:bg-gray-100 dark:disabled:bg-gray-800 disabled:cursor-not-allowed">
                </div>
                
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email Wali</label>
                    <input type="email" 
                           x-model="biodataForm.email_wali"
                           :disabled="!biodataEditing"
                           placeholder="email@example.com"
                           class="w-full pkg-field disabled:bg-gray-100 dark:disabled:bg-gray-800 disabled:cursor-not-allowed">
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    <template x-if="biodataStudent?.last_login_at">
                        <span>Terakhir login: <span x-text="new Date(biodataStudent.last_login_at).toLocaleString('id-ID')"></span></span>
                    </template>
                    <template x-if="!biodataStudent?.last_login_at">
                        <span class="italic">Belum pernah login</span>
                    </template>
                </div>
                <div class="flex gap-2">
                    <template x-if="!biodataEditing">
                        @if(auth()->user()->hasPamongCrudPermission('siswa', 'edit'))
                        <button @click="enableBiodataEdit()" 
                                class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Edit Biodata
                        </button>
                        @endif
                        <button @click="showBiodataModal = false" 
                                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition">
                            Tutup
                        </button>
                    </template>
                    <template x-if="biodataEditing">
                        <button @click="saveBiodata()" 
                                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Simpan
                        </button>
                        <button @click="cancelBiodataEdit()" 
                                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition">
                            Batal
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

--}}

