<?php

namespace App\Http\Controllers;

use App\Models\Karakter;
use App\Models\Setting;
use App\Models\ShareInfo;
use App\Models\Siswa;
use App\Models\SiswaKarakterChecklist;
use App\Support\BiometricStatus;
use App\Support\TargetGrade;
use App\Services\Contracts\SiswaServiceInterface;
use App\Services\MateriRppJournalWorkflowService;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Controller for siswa dashboard.
 * 
 * **Feature: website-settings, Requirements 14.1, 14.2, 14.3**
 */
class SiswaDashboardController extends Controller
{
    public function __construct(
        protected SiswaServiceInterface $siswaService,
        protected MateriRppJournalWorkflowService $journalWorkflow
    ) {}
    /**
     * Display siswa dashboard.
     * 
     * **Validates: Requirements 14.1, 14.2, 14.3**
     */
    public function index()
    {
        $siswa = Auth::guard('siswa')->user();
        
        // Get today's daily karakter progress only.
        $today = now()->toDateString();
        $totalKarakter = Karakter::active()
            ->where('kategori', 'harian')
            ->count();
        $checkedKarakter = SiswaKarakterChecklist::where('siswa_id', $siswa->id)
            ->whereDate('checked_at', $today)
            ->whereHas('karakter', function ($query) {
                $query->active()->where('kategori', 'harian');
            })
            ->distinct('karakter_id')
            ->count('karakter_id');
        $checkedKarakter = min($checkedKarakter, $totalKarakter);
        $karakterPercentage = $totalKarakter > 0
            ? min(100, round(($checkedKarakter / $totalKarakter) * 100, 1))
            : 0;
        
        // Get recent karakter checks
        $recentKarakter = SiswaKarakterChecklist::where('siswa_id', $siswa->id)
            ->with(['karakter', 'verifier'])
            ->orderBy('checked_at', 'desc')
            ->limit(5)
            ->get();
        
        // Get assigned pamong
        $pamongList = $siswa->pamong ?? collect();
        
        // Get gamification stats
        $gamificationStats = null;
        try {
            $gamificationService = app(\App\Services\GamificationService::class);
            $gamificationStats = $gamificationService->getSiswaStats($siswa);
        } catch (\Exception $e) {
            \Log::warning('Failed to get gamification stats: ' . $e->getMessage());
        }
        
        // Share info for siswa
        $shareInfos = ShareInfo::active()
            ->forTarget('siswa')
            ->orderByDesc('created_at')
            ->get();

        // All levels for tier info modal
        $allLevels = \App\Models\Level::active()->orderBy('level')->get();

        $biometricStatus = BiometricStatus::resolve($siswa->id, 'siswa');
        $journalTasks = $this->journalWorkflow->studentTasks($siswa);

        return view('siswa.dashboard', compact(
            'siswa',
            'totalKarakter',
            'checkedKarakter',
            'karakterPercentage',
            'recentKarakter',
            'pamongList',
            'gamificationStats',
            'shareInfos',
            'allLevels',
            'biometricStatus',
            'journalTasks'
        ));
    }

    /**
     * Display siswa ID card.
     */
    public function kartu()
    {
        $siswa = Auth::guard('siswa')->user();
        $siswa->load('kelas');
        
        // Generate QR if needed
        if (!$siswa->qr_token || $siswa->qr_token_expires_at?->isPast()) {
            $this->siswaService->generateQrCode($siswa->id);
            $siswa->refresh();
        }

        // Generate QR image
        $qrCode = null;
        if ($siswa->qr_token) {
            $qrData = json_encode([
                'student_id' => $siswa->id,
                'nis' => $siswa->nis,
                'token' => $siswa->qr_token,
            ]);
            
            $result = Builder::create()
                ->writer(new SvgWriter())
                ->data($qrData)
                ->size(200)
                ->margin(10)
                ->build();
            
            $qrCode = $result->getDataUri();
        }

        // Get settings
        $settings = [
            'institution_name' => Setting::get('institution_name', 'PKG'),
            'logo' => Setting::get('logo'),
            'address' => Setting::get('address'),
        ];

        return view('siswa.kartu', compact('siswa', 'qrCode', 'settings'));
    }

    /**
     * Display print-only siswa ID card sized for KTP.
     */
    public function kartuPrint()
    {
        $siswa = Auth::guard('siswa')->user();
        $siswa->load('kelas');

        if (!$siswa->qr_token || $siswa->qr_token_expires_at?->isPast()) {
            $this->siswaService->generateQrCode($siswa->id);
            $siswa->refresh();
            $siswa->load('kelas');
        }

        $qrCode = $this->buildQrCodeDataUri($siswa);

        return view('siswa.card-print', compact('siswa', 'qrCode'));
    }

    /**
     * Display siswa profile page.
     */
    public function profile()
    {
        $siswa = Auth::guard('siswa')->user();
        $siswa->load('kelas');
        $kelompokOptions = Siswa::kelompokOptions();
        $targetGradeOptions = TargetGrade::options();
        
        return view('siswa.profile', compact('siswa', 'kelompokOptions', 'targetGradeOptions'));
    }

    /**
     * Update siswa profile photo.
     */
    public function updatePhoto(Request $request)
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ], [
            'foto.required' => 'Pilih foto terlebih dahulu.',
            'foto.image' => 'File harus berupa gambar.',
            'foto.mimes' => 'Format foto harus JPEG, PNG, atau JPG.',
            'foto.max' => 'Ukuran foto maksimal 2MB.',
        ]);

        $siswa = Auth::guard('siswa')->user();

        try {
            // Delete old photo if exists
            if ($siswa->foto_path && Storage::disk('public')->exists($siswa->foto_path)) {
                Storage::disk('public')->delete($siswa->foto_path);
            }

            // Store new photo
            $file = $request->file('foto');
            $filename = 'siswa/' . $siswa->nis . '_' . time() . '.' . $file->getClientOriginalExtension();
            
            // Resize and store image
            $image = \Intervention\Image\Laravel\Facades\Image::read($file);
            $image->cover(400, 400); // Square crop for profile
            
            Storage::disk('public')->put($filename, $image->toJpeg(85));
            
            // Update siswa record
            $siswa->foto_path = $filename;
            $siswa->save();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Foto profil berhasil diperbarui.',
                    'foto_url' => asset('storage/' . $filename),
                ]);
            }

            return redirect()->route('siswa.profile')
                ->with('success', 'Foto profil berhasil diperbarui!');
        } catch (\Exception $e) {
            \Log::error('Failed to update siswa photo: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Gagal mengupload foto. Silakan coba lagi.',
                ], 500);
            }
            
            return redirect()->route('siswa.profile')
                ->with('error', 'Gagal mengupload foto. Silakan coba lagi.');
        }
    }

    /**
     * Update siswa profile data.
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:100',
            'jenis_kelamin' => 'nullable|in:L,P',
            'tanggal_lahir' => 'nullable|date',
            'kelompok' => 'nullable|in:' . implode(',', array_keys(Siswa::kelompokOptions())),
            'target_grade_override' => 'nullable|in:' . implode(',', TargetGrade::values()),
            'phone' => 'nullable|string|max:20',
            'nama_wali' => 'nullable|string|max:100',
            'phone_wali' => 'nullable|string|max:20',
        ], [
            'nama.required' => 'Nama wajib diisi.',
            'nama.max' => 'Nama maksimal 100 karakter.',
            'jenis_kelamin.in' => 'Jenis kelamin tidak valid.',
            'tanggal_lahir.date' => 'Format tanggal lahir tidak valid.',
            'kelompok.in' => 'Kelompok tidak valid.',
            'target_grade_override.in' => 'Level kelas PKG tidak valid.',
            'phone.max' => 'Nomor HP maksimal 20 karakter.',
            'nama_wali.max' => 'Nama wali maksimal 100 karakter.',
            'phone_wali.max' => 'Nomor HP wali maksimal 20 karakter.',
        ]);

        $siswa = Auth::guard('siswa')->user();

        try {
            $siswa->update([
                'nama' => $request->nama,
                'jenis_kelamin' => $request->jenis_kelamin,
                'tanggal_lahir' => $request->tanggal_lahir,
                'kelompok' => $request->kelompok,
                'target_grade_override' => $request->input('target_grade_override') ?: null,
                'phone' => $request->phone,
                'nama_wali' => $request->nama_wali,
                'phone_wali' => $request->phone_wali,
            ]);

            return redirect()->route('siswa.profile')
                ->with('success', 'Data profil berhasil diperbarui!');
        } catch (\Exception $e) {
            \Log::error('Failed to update siswa profile: ' . $e->getMessage());
            
            return redirect()->route('siswa.profile')
                ->with('error', 'Gagal memperbarui data. Silakan coba lagi.');
        }
    }

    /**
     * Update siswa account credentials (username/password).
     */
    public function updateAccount(Request $request)
    {
        $siswa = Auth::guard('siswa')->user();
        
        $request->validate([
            'username' => 'required|string|max:50|unique:siswa,nis,' . $siswa->id,
            'current_password' => 'required|string',
            'new_password' => 'nullable|string|min:4|confirmed',
        ], [
            'username.required' => 'Username wajib diisi.',
            'username.max' => 'Username maksimal 50 karakter.',
            'username.unique' => 'Username sudah digunakan siswa lain.',
            'current_password.required' => 'Password saat ini wajib diisi.',
            'new_password.min' => 'Password baru minimal 4 karakter.',
            'new_password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        // Verify current password
        if (!Hash::check($request->current_password, $siswa->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini tidak benar.']);
        }

        try {
            $updateData = ['nis' => $request->username];
            
            // Update password if provided
            if ($request->filled('new_password')) {
                $updateData['password'] = $request->new_password;
            }
            
            $siswa->update($updateData);

            return redirect()->route('siswa.profile')
                ->with('success', 'Akun berhasil diperbarui!');
        } catch (\Exception $e) {
            \Log::error('Failed to update siswa account: ' . $e->getMessage());
            
            return redirect()->route('siswa.profile')
                ->with('error', 'Gagal memperbarui akun. Silakan coba lagi.');
        }
    }

    protected function buildQrCodeDataUri(Siswa $siswa): ?string
    {
        if (! $siswa->qr_token) {
            return null;
        }

        $qrData = json_encode([
            'student_id' => $siswa->id,
            'nis' => $siswa->nis,
            'token' => $siswa->qr_token,
        ]);

        return Builder::create()
            ->writer(new SvgWriter())
            ->data($qrData)
            ->size(200)
            ->margin(10)
            ->build()
            ->getDataUri();
    }
}
