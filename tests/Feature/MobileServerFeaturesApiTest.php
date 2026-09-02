<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\MobileServerFeaturesController;
use App\Models\Chat;
use App\Models\ChatGroup;
use App\Models\FaceProfile;
use App\Models\GenerusRegistration;
use App\Models\Karakter;
use App\Models\LaporanPenyaksian;
use App\Models\MateriRppJournal;
use App\Models\MateriTarget;
use App\Models\QuranReadingCycle;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\SiswaKarakterChecklist;
use App\Models\User;
use App\Models\WebAuthnCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileServerFeaturesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_server_features_reads_real_database_counts_for_staff(): void
    {
        $admin = $this->admin();
        $siswa = Siswa::factory()->create(['nama' => 'Rafi Data Riil']);

        Chat::query()->create([
            'sender_user_id' => $admin->id,
            'receiver_siswa_id' => $siswa->id,
            'message' => 'Pesan uji API mobile',
            'message_type' => 'text',
        ]);
        ChatGroup::query()->create(['name' => 'Grup Binaan Uji', 'created_by' => $admin->id, 'type' => 'custom', 'is_active' => true]);
        $siswa->updatePushSubscription('https://push.example.test/mobile-feature', str_repeat('p', 65), str_repeat('a', 22), 'aes128gcm');
        WebAuthnCredential::query()->create([
            'user_id' => $admin->id,
            'user_type' => 'admin',
            'credential_id' => 'credential-admin-uji',
            'device_name' => 'Android Uji',
        ]);
        MateriTarget::query()->create(['category' => MateriTarget::CATEGORY_HAFALAN, 'target_grade' => 'SMP_7', 'semester' => 1, 'title' => 'Target Hafalan Uji', 'is_active' => true]);
        MateriRppJournal::query()->create(['journal_date' => '2026-09-01', 'materi_title' => 'Jurnal Uji', 'teacher_name' => 'Pamong Uji', 'realization_status' => MateriRppJournal::STATUS_TERLAKSANA, 'workflow_status' => MateriRppJournal::WORKFLOW_APPROVED]);
        FaceProfile::query()->create(['subject_type' => FaceProfile::SUBJECT_SISWA, 'subject_id' => $siswa->id, 'descriptor_payload' => encrypt(json_encode([0.1, 0.2])), 'status' => FaceProfile::STATUS_ACTIVE, 'enrolled_by_user_id' => $admin->id]);
        Karakter::query()->create(['nama' => 'Karakter Reward Uji', 'kategori' => 'harian', 'poin' => 10, 'is_active' => true]);
        SiswaKarakterChecklist::query()->create(['siswa_id' => $siswa->id, 'karakter_id' => Karakter::query()->first()->id, 'checked_at' => now(), 'verified_at' => now(), 'verified_by' => $admin->id]);
        QuranReadingCycle::query()->create(['siswa_id' => $siswa->id, 'cycle_number' => 1, 'status' => QuranReadingCycle::STATUS_ACTIVE, 'started_at' => '2026-09-01']);
        LaporanPenyaksian::query()->create(['nama_pelapor' => 'Pelapor Uji', 'phone_pelapor' => '0800000000', 'nama_generus' => 'Generus Uji', 'karakter_belum_optimal' => 'Amanah', 'tanggal_kejadian' => '2026-09-01', 'deskripsi_kejadian' => 'Laporan uji', 'status' => 'pending']);
        GenerusRegistration::query()->create(['public_id' => '11111111-1111-4111-8111-111111111111', 'download_token_hash' => hash('sha256', 'uji'), 'parent_name' => 'Ortu Uji', 'parent_phone' => '0800000001', 'student_name' => 'Siswa Daftar Uji', 'student_phone' => '0800000002', 'kelompok' => 'Kelompok Uji', 'birth_place' => 'Kota', 'birth_date' => '2010-01-01', 'school_grade' => 'SMP_7', 'parent_signature_path' => 'sign/ortu.png', 'student_signature_path' => 'sign/siswa.png', 'submitted_at' => now()]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/mobile/fitur-server')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total_fitur', 10)
            ->assertJsonPath('data.0.kode', 'chat')
            ->assertJsonPath('data.0.total', 2)
            ->assertJsonPath('data.1.kode', 'push_notification')
            ->assertJsonPath('data.1.total', 1)
            ->assertJsonPath('data.9.kode', 'pendaftaran_generus')
            ->assertJsonPath('data.9.total', 1)
            ->assertJsonStructure([
                'data' => [[
                    'kode', 'judul', 'ringkasan', 'status', 'total', 'updated_at', 'endpoint', 'items',
                ]],
            ]);
    }

    public function test_mobile_server_features_detail_returns_actionable_targets_for_staff(): void
    {
        $admin = $this->admin();
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/mobile/fitur-server?fitur=chat')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.fitur', 'chat')
            ->assertJsonPath('meta.actor', 'staff')
            ->assertJsonPath('data.kode', 'chat')
            ->assertJsonStructure([
                'data' => ['kode', 'judul', 'ringkasan', 'status', 'total', 'endpoint', 'items', 'aksi' => [['label', 'tipe', 'target', 'url', 'butuh_sesi_web']]],
            ]);

        $targets = collect($response->json('data.aksi'))->pluck('target')->all();
        $this->assertContains('/pamong-chat', $targets);
        $this->assertContains('/group-chat', $targets);
    }

    public function test_mobile_server_features_detail_scopes_actions_per_actor(): void
    {
        $siswa = Siswa::factory()->create(['nama' => 'Rafi Aksi']);

        Sanctum::actingAs($siswa, ['siswa']);
        $siswaTargets = collect(
            $this->getJson('/api/v1/mobile/fitur-server?fitur=chat')
                ->assertOk()
                ->assertJsonPath('meta.actor', 'siswa')
                ->json('data.aksi')
        )->pluck('target')->all();
        $this->assertContains('/siswa/chat', $siswaTargets);
        $this->assertNotContains('/pamong-chat', $siswaTargets);

        Sanctum::actingAs($siswa, ['ortu']);
        $ortuTargets = collect(
            $this->getJson('/api/v1/mobile/fitur-server?fitur=chat')
                ->assertOk()
                ->assertJsonPath('meta.actor', 'ortu')
                ->json('data.aksi')
        )->pluck('target')->all();
        $this->assertSame(['/ortu/chat'], $ortuTargets);
    }

    public function test_mobile_server_features_detail_covers_every_feature_code(): void
    {
        Sanctum::actingAs($this->admin());

        foreach (MobileServerFeaturesController::KODE as $kode) {
            $response = $this->getJson('/api/v1/mobile/fitur-server?fitur='.$kode)
                ->assertOk()
                ->assertJsonPath('data.kode', $kode);

            $this->assertNotEmpty(
                $response->json('data.aksi'),
                'Fitur '.$kode.' tidak punya aksi yang bisa dibuka aplikasi.'
            );
        }
    }

    public function test_mobile_server_features_detail_rejects_unknown_feature(): void
    {
        Sanctum::actingAs($this->admin());

        $this->getJson('/api/v1/mobile/fitur-server?fitur=tidak-ada')
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_mobile_server_features_requires_authenticated_mobile_token(): void
    {
        $this->getJson('/api/v1/mobile/fitur-server')->assertUnauthorized();
    }

    private function admin(): User
    {
        $role = Role::query()->firstOrCreate(['name' => User::ROLE_ADMIN], ['display_name' => 'Administrator', 'permissions' => ['*'], 'is_active' => true]);

        return User::factory()->create(['role_id' => $role->id, 'status' => 'active']);
    }
}
