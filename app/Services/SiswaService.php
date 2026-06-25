<?php

namespace App\Services;

use App\DTOs\CreateSiswaDTO;
use App\DTOs\UpdateSiswaDTO;
use App\Models\Siswa;
use App\Repositories\Contracts\SiswaRepositoryInterface;
use App\Services\Contracts\QrTokenServiceInterface;
use App\Services\Contracts\SiswaServiceInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

/**
 * Service untuk mengelola data Siswa
 *
 * Service ini menangani semua operasi bisnis terkait data siswa
 * termasuk CRUD, generate QR code, dan statistik.
 */
class SiswaService implements SiswaServiceInterface
{
    public function __construct(
        protected SiswaRepositoryInterface $siswaRepository,
        protected QrTokenServiceInterface $qrTokenService
    ) {}

    /**
     * Membuat siswa baru
     *
     * @param  CreateSiswaDTO  $dto  Data siswa yang akan dibuat
     * @return Siswa Siswa yang berhasil dibuat
     */
    public function create(CreateSiswaDTO $dto): Siswa
    {
        $data = [
            'nis' => $dto->nis,
            'nama' => $dto->nama,
            'jenis_kelamin' => $dto->jenisKelamin,
            'tanggal_lahir' => $dto->tanggalLahir,
            'kelompok' => $dto->kelompok,
            'kelas_id' => $dto->kelasId,
            'target_grade_override' => $dto->targetGradeOverride,
            'nama_wali' => $dto->namaWali,
            'phone_wali' => $dto->phoneWali,
            'phone' => $dto->phone ?? null,
            'email_wali' => $dto->emailWali,
            'password' => $dto->nis, // Default password = NIS (will be hashed by model mutator)
            'status' => 'active',
            'is_active' => true,
        ];

        // Handle foto upload jika ada
        if ($dto->foto) {
            $data['foto_path'] = $this->handlePhotoUpload($dto->foto, $dto->nis);
        }

        $siswa = $this->siswaRepository->create($data);

        // Generate QR token untuk siswa baru
        $this->qrTokenService->generate($siswa);

        return $siswa->fresh();
    }

    /**
     * Mengupdate data siswa
     *
     * @param  int  $id  ID siswa yang akan diupdate
     * @param  UpdateSiswaDTO  $dto  Data siswa yang akan diupdate
     * @return Siswa Siswa yang sudah diupdate
     *
     * @throws ModelNotFoundException Jika siswa tidak ditemukan
     */
    public function update(int $id, UpdateSiswaDTO $dto): Siswa
    {
        $siswa = $this->siswaRepository->findById($id);

        if (! $siswa) {
            throw new ModelNotFoundException("Siswa dengan ID {$id} tidak ditemukan");
        }

        $data = array_filter([
            'nama' => $dto->nama,
            'jenis_kelamin' => $dto->jenisKelamin,
            'tanggal_lahir' => $dto->tanggalLahir,
            'kelompok' => $dto->kelompok,
            'kelas_id' => $dto->kelasId,
            'nama_wali' => $dto->namaWali,
            'phone_wali' => $dto->phoneWali,
            'email_wali' => $dto->emailWali,
            'status' => $dto->status,
            'is_active' => $dto->isActive,
        ], fn ($value) => $value !== null);

        if ($dto->targetGradeOverrideProvided) {
            $data['target_grade_override'] = $dto->targetGradeOverride;
        }

        // Handle foto upload jika ada
        if ($dto->foto) {
            // Hapus foto lama jika ada
            if ($siswa->foto_path) {
                Storage::disk('public')->delete($siswa->foto_path);
            }
            $data['foto_path'] = $this->handlePhotoUpload($dto->foto, $siswa->nis);
        }

        return $this->siswaRepository->update($id, $data);
    }

    /**
     * Menghapus siswa
     *
     * @param  int  $id  ID siswa yang akan dihapus
     * @return bool True jika berhasil dihapus
     *
     * @throws ModelNotFoundException Jika siswa tidak ditemukan
     */
    public function delete(int $id): bool
    {
        $siswa = $this->siswaRepository->findById($id);

        if (! $siswa) {
            throw new ModelNotFoundException("Siswa dengan ID {$id} tidak ditemukan");
        }

        // Hapus foto jika ada
        if ($siswa->foto_path) {
            Storage::disk('public')->delete($siswa->foto_path);
        }

        return $this->siswaRepository->delete($id);
    }

    /**
     * Mendapatkan siswa berdasarkan ID
     *
     * @param  int  $id  ID siswa
     * @return Siswa|null Siswa atau null jika tidak ditemukan
     */
    public function findById(int $id): ?Siswa
    {
        return $this->siswaRepository->findById($id);
    }

    /**
     * Mendapatkan siswa berdasarkan NIS
     *
     * @param  string  $nis  Nomor Induk Siswa
     * @return Siswa|null Siswa atau null jika tidak ditemukan
     */
    public function findByNis(string $nis): ?Siswa
    {
        return $this->siswaRepository->findByNis($nis);
    }

    /**
     * Generate QR code untuk siswa
     *
     * @param  int  $siswaId  ID siswa
     * @return array Data QR code (token, qr_image, expires_at)
     *
     * @throws ModelNotFoundException Jika siswa tidak ditemukan
     */
    public function generateQrCode(int $siswaId): array
    {
        $siswa = $this->siswaRepository->findById($siswaId);

        if (! $siswa) {
            throw new ModelNotFoundException("Siswa dengan ID {$siswaId} tidak ditemukan");
        }

        return $this->qrTokenService->getQrData($siswa);
    }

    /**
     * Mendapatkan statistik siswa
     *
     * @return array Statistik siswa (total, aktif, per_kelas, per_jenis_kelamin)
     */
    public function getStatistics(): array
    {
        $allSiswa = $this->siswaRepository->paginate([], 1000);
        $siswaCollection = collect($allSiswa->items());

        $total = $siswaCollection->count();
        $aktif = $siswaCollection->where('is_active', true)->count();
        $nonAktif = $total - $aktif;

        // Statistik per jenis kelamin
        $perJenisKelamin = [
            'L' => $siswaCollection->where('jenis_kelamin', 'L')->count(),
            'P' => $siswaCollection->where('jenis_kelamin', 'P')->count(),
        ];

        // Statistik per kelas
        $perKelas = $siswaCollection->groupBy('kelas_id')
            ->map(fn ($group) => $group->count())
            ->toArray();

        return [
            'total' => $total,
            'aktif' => $aktif,
            'non_aktif' => $nonAktif,
            'per_jenis_kelamin' => $perJenisKelamin,
            'per_kelas' => $perKelas,
        ];
    }

    /**
     * Mendapatkan statistik kelengkapan biodata siswa aktif
     *
     * @return array Statistik kelengkapan biodata (total_lengkap, total_belum_lengkap)
     */
    public function getBiodataStatistics(): array
    {
        $allActiveSiswa = collect($this->siswaRepository->getActive());

        $totalLengkap = 0;
        $totalBelumLengkap = 0;

        foreach ($allActiveSiswa as $siswa) {
            if ($siswa->is_biodata_complete) {
                $totalLengkap++;
            } else {
                $totalBelumLengkap++;
            }
        }

        return [
            'total_lengkap' => $totalLengkap,
            'total_belum_lengkap' => $totalBelumLengkap,
        ];
    }

    /**
     * Mendapatkan daftar siswa aktif
     *
     * @return Collection Koleksi siswa aktif
     */
    public function getActive(): Collection
    {
        return $this->siswaRepository->getActive();
    }

    /**
     * Mendapatkan siswa berdasarkan kelas
     *
     * @param  int  $kelasId  ID kelas
     * @return Collection Koleksi siswa dalam kelas
     */
    public function getByKelas(int $kelasId): Collection
    {
        return $this->siswaRepository->getByKelas($kelasId);
    }

    /**
     * Mendapatkan daftar siswa dengan pagination dan filter
     *
     * @param  array  $filters  Filter (nama, nis, kelas_id, is_active, dll)
     * @param  int  $perPage  Jumlah item per halaman
     * @return LengthAwarePaginator Hasil pagination
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->siswaRepository->paginate($filters, $perPage);
    }

    /**
     * Handle upload dan kompresi foto siswa
     *
     * @param  mixed  $foto  File foto yang diupload
     * @param  string  $nis  NIS siswa untuk nama file
     * @return string Path foto yang disimpan
     */
    protected function handlePhotoUpload($foto, string $nis): string
    {
        $extension = $foto->getClientOriginalExtension() ?: 'jpg';
        $filename = 'siswa_'.$nis.'_'.time().'.'.$extension;
        $path = 'siswa/'.$filename;

        // Coba gunakan Intervention Image jika GD tersedia
        if (extension_loaded('gd')) {
            try {
                $manager = new ImageManager(new Driver());
                $image = $manager->read($foto->getPathname());
                
                // Resize dengan mempertahankan aspect ratio
                $image->scaleDown(width: 400, height: 400);
                
                // Encode ke JPEG dengan kualitas 80%
                $encoded = $image->toJpeg(80);
                
                $path = 'siswa/siswa_'.$nis.'_'.time().'.jpg';
                Storage::disk('public')->put($path, $encoded);
                
                return $path;
            } catch (\Exception $e) {
                // Fallback ke upload biasa jika Intervention gagal
                \Log::warning('Intervention Image failed, using fallback: ' . $e->getMessage());
            }
        }

        // Fallback: simpan file langsung tanpa kompresi
        $storedPath = $foto->storeAs('siswa', $filename, 'public');
        
        return $storedPath;
    }
}
