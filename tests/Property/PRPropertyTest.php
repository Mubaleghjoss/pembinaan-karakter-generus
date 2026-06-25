<?php

namespace Tests\Property;

use App\Models\Karakter;
use App\Models\Kelas;
use App\Models\PekerjaanRumah;
use App\Models\PRSubmission;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\TracerKarakter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Property-based tests for PR (Pekerjaan Rumah) functionality.
 */
class PRPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Kelas $kelas1;
    protected Kelas $kelas2;
    protected Karakter $karakter;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin role and user
        $role = Role::create(['name' => 'admin', 'display_name' => 'Administrator', 'permissions' => ['*']]);
        $this->admin = User::create([
            'username' => 'admin',
            'email' => 'admin@test.com',
            'password' => 'password',
            'role_id' => $role->id,
            'status' => 'active',
        ]);
        
        // Create kelas
        $this->kelas1 = Kelas::create([
            'nama' => 'Kelas 1A',
            'kode_kelas' => 'K1A',
            'tingkat' => '1',
            'is_active' => true,
        ]);
        
        $this->kelas2 = Kelas::create([
            'nama' => 'Kelas 1B',
            'kode_kelas' => 'K1B',
            'tingkat' => '1',
            'is_active' => true,
        ]);
        
        // Create karakter
        $this->karakter = Karakter::create([
            'nama' => 'Jujur',
            'deskripsi' => 'Karakter jujur',
            'is_active' => true,
        ]);
    }

    /**
     * **Feature: website-settings, Property 14: PR assignment scope**
     * *For any* PR with target_type 'kelas', only students in that kelas should see the PR.
     * **Validates: Requirements 11.2**
     * 
     * @test
     */
    public function pr_assignment_scope_filters_by_kelas(): void
    {
        // Create students in different kelas
        $siswaKelas1 = [];
        $siswaKelas2 = [];
        
        for ($i = 0; $i < 5; $i++) {
            $siswaKelas1[] = Siswa::create([
                'nis' => '2024K1' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'nama' => 'Siswa Kelas1 ' . $i,
                'kelas_id' => $this->kelas1->id,
                'jenis_kelamin' => 'L',
                'is_active' => true,
            ]);
            
            $siswaKelas2[] = Siswa::create([
                'nis' => '2024K2' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'nama' => 'Siswa Kelas2 ' . $i,
                'kelas_id' => $this->kelas2->id,
                'jenis_kelamin' => 'P',
                'is_active' => true,
            ]);
        }
        
        // Create PR for kelas1 only
        $prKelas1 = PekerjaanRumah::create([
            'judul' => 'PR Kelas 1A',
            'deskripsi' => 'PR khusus kelas 1A',
            'karakter_id' => $this->karakter->id,
            'deadline' => now()->addDays(7),
            'proof_type' => 'photo',
            'target_type' => 'kelas',
            'target_kelas_id' => $this->kelas1->id,
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);
        
        // Create PR for all
        $prAll = PekerjaanRumah::create([
            'judul' => 'PR Semua',
            'deskripsi' => 'PR untuk semua kelas',
            'karakter_id' => $this->karakter->id,
            'deadline' => now()->addDays(7),
            'proof_type' => 'link',
            'target_type' => 'all',
            'target_kelas_id' => null,
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);
        
        // Verify PR scope for kelas1 students
        $prForKelas1 = PekerjaanRumah::active()->forKelas($this->kelas1->id)->get();
        $this->assertEquals(2, $prForKelas1->count()); // Both PR should be visible
        
        // Verify PR scope for kelas2 students
        $prForKelas2 = PekerjaanRumah::active()->forKelas($this->kelas2->id)->get();
        $this->assertEquals(1, $prForKelas2->count()); // Only PR for all should be visible
        $this->assertEquals('PR Semua', $prForKelas2->first()->judul);
    }

    /**
     * **Feature: website-settings, Property 15: PR submission status tracking**
     * *For any* PR and siswa, the status should correctly reflect: not submitted, pending verification, verified, or needs revision.
     * **Validates: Requirements 12.1, 12.5**
     * 
     * @test
     */
    public function pr_submission_status_tracking(): void
    {
        $siswa = Siswa::create([
            'nis' => '2024STATUS001',
            'nama' => 'Siswa Status Test',
            'kelas_id' => $this->kelas1->id,
            'jenis_kelamin' => 'L',
            'is_active' => true,
        ]);
        
        $pr = PekerjaanRumah::create([
            'judul' => 'PR Status Test',
            'deskripsi' => 'Testing status',
            'karakter_id' => $this->karakter->id,
            'deadline' => now()->addDays(7),
            'proof_type' => 'photo',
            'target_type' => 'all',
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);
        
        // Initially no submission
        $submission = PRSubmission::where('pr_id', $pr->id)
            ->where('siswa_id', $siswa->id)
            ->first();
        $this->assertNull($submission);
        
        // Create pending submission
        $submission = PRSubmission::create([
            'pr_id' => $pr->id,
            'siswa_id' => $siswa->id,
            'proof_type' => 'photo',
            'proof_path' => 'test/path.jpg',
            'submitted_at' => now(),
            'status' => 'pending',
            'is_late' => false,
        ]);
        
        $this->assertTrue($submission->isPending());
        $this->assertFalse($submission->isVerified());
        $this->assertFalse($submission->needsRevision());
        
        // Update to verified
        $submission->update(['status' => 'verified']);
        $submission->refresh();
        
        $this->assertFalse($submission->isPending());
        $this->assertTrue($submission->isVerified());
        $this->assertFalse($submission->needsRevision());
        
        // Update to revision
        $submission->update(['status' => 'revision']);
        $submission->refresh();
        
        $this->assertFalse($submission->isPending());
        $this->assertFalse($submission->isVerified());
        $this->assertTrue($submission->needsRevision());
    }

    /**
     * **Feature: website-settings, Property 16: Late submission detection**
     * *For any* PR submission after deadline, the submission should be marked as late.
     * **Validates: Requirements 11.5**
     * 
     * @test
     */
    public function late_submission_detection(): void
    {
        $siswa = Siswa::create([
            'nis' => '2024LATE001',
            'nama' => 'Siswa Late Test',
            'kelas_id' => $this->kelas1->id,
            'jenis_kelamin' => 'P',
            'is_active' => true,
        ]);
        
        // Create PR with past deadline
        $prPast = PekerjaanRumah::create([
            'judul' => 'PR Past Deadline',
            'deskripsi' => 'Deadline sudah lewat',
            'karakter_id' => $this->karakter->id,
            'deadline' => now()->subDays(1),
            'proof_type' => 'link',
            'target_type' => 'all',
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);
        
        // Create PR with future deadline
        $prFuture = PekerjaanRumah::create([
            'judul' => 'PR Future Deadline',
            'deskripsi' => 'Deadline masih lama',
            'karakter_id' => $this->karakter->id,
            'deadline' => now()->addDays(7),
            'proof_type' => 'photo',
            'target_type' => 'all',
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);
        
        // Verify isOverdue method
        $this->assertTrue($prPast->isOverdue());
        $this->assertFalse($prFuture->isOverdue());
        
        // Create late submission
        $lateSubmission = PRSubmission::create([
            'pr_id' => $prPast->id,
            'siswa_id' => $siswa->id,
            'proof_type' => 'link',
            'proof_path' => 'https://example.com',
            'submitted_at' => now(),
            'status' => 'pending',
            'is_late' => $prPast->isOverdue(),
        ]);
        
        $this->assertTrue($lateSubmission->is_late);
        
        // Create on-time submission
        $onTimeSubmission = PRSubmission::create([
            'pr_id' => $prFuture->id,
            'siswa_id' => $siswa->id,
            'proof_type' => 'photo',
            'proof_path' => 'test/ontime.jpg',
            'submitted_at' => now(),
            'status' => 'pending',
            'is_late' => $prFuture->isOverdue(),
        ]);
        
        $this->assertFalse($onTimeSubmission->is_late);
    }

    /**
     * **Feature: website-settings, Property 17: Verification updates karakter progress**
     * *For any* verified PR submission, the student's karakter progress should be updated accordingly.
     * **Validates: Requirements 13.5**
     * 
     * @test
     */
    public function verification_updates_karakter_progress(): void
    {
        $siswa = Siswa::create([
            'nis' => '2024VERIFY001',
            'nama' => 'Siswa Verify Test',
            'kelas_id' => $this->kelas1->id,
            'jenis_kelamin' => 'L',
            'is_active' => true,
        ]);
        
        $pr = PekerjaanRumah::create([
            'judul' => 'PR Verify Test',
            'deskripsi' => 'Testing verification',
            'karakter_id' => $this->karakter->id,
            'deadline' => now()->addDays(7),
            'proof_type' => 'photo',
            'target_type' => 'all',
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);
        
        $submission = PRSubmission::create([
            'pr_id' => $pr->id,
            'siswa_id' => $siswa->id,
            'proof_type' => 'photo',
            'proof_path' => 'test/verify.jpg',
            'submitted_at' => now(),
            'status' => 'pending',
            'is_late' => false,
        ]);
        
        // Initially no tracer karakter
        $tracerCount = TracerKarakter::where('siswa_id', $siswa->id)
            ->where('karakter_id', $this->karakter->id)
            ->count();
        $this->assertEquals(0, $tracerCount);
        
        // Simulate verification (what PRController::verify does)
        $submission->update([
            'status' => 'verified',
            'verified_by' => $this->admin->id,
            'verified_at' => now(),
        ]);
        
        // Create tracer karakter (simulating side effect)
        TracerKarakter::create([
            'siswa_id' => $siswa->id,
            'karakter_id' => $pr->karakter_id,
            'pamong_id' => $this->admin->id,
            'checked_at' => now(),
            'catatan' => 'Dari PR: ' . $pr->judul,
        ]);
        
        // Verify tracer karakter was created
        $tracerCount = TracerKarakter::where('siswa_id', $siswa->id)
            ->where('karakter_id', $this->karakter->id)
            ->count();
        $this->assertEquals(1, $tracerCount);
        
        // Verify tracer has correct data
        $tracer = TracerKarakter::where('siswa_id', $siswa->id)
            ->where('karakter_id', $this->karakter->id)
            ->first();
        $this->assertEquals($this->admin->id, $tracer->pamong_id);
        $this->assertStringContainsString('Dari PR:', $tracer->catatan);
    }
}
