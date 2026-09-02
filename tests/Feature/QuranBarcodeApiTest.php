<?php

namespace Tests\Feature;

use App\Models\PamongSiswa;
use App\Models\QuranReadingEntry;
use App\Models\QuranReadingSheet;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use App\Services\QuranReadingScanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QuranBarcodeApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('cache.default', 'array');
        Cache::flush();
    }

    public function test_student_identifies_own_sheet_with_masked_identity_and_no_store_cache(): void
    {
        $student = Siswa::factory()->create(['nis' => 'PKG260712345']);
        [, $payload] = $this->barcodeSheet($student);
        Sanctum::actingAs($student, ['siswa']);

        $response = $this->postJson('/api/v1/quran/barcode/identify', ['sheet_payload' => $payload])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.student.name', $student->nama)
            ->assertJsonPath('data.student.masked_nis', '•••••••••345')
            ->assertJsonStructure(['data' => ['flow_id', 'expires_at', 'student' => ['name', 'masked_nis', 'school_grade', 'group']]])
            ->assertJsonMissingPath('data.student.id')
            ->assertJsonMissingPath('data.sheet_payload');

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{40}$/', $response->json('data.flow_id'));
        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_student_cannot_identify_another_students_sheet_without_identity_leak(): void
    {
        $owner = Siswa::factory()->create(['nama' => 'Pemilik Rahasia']);
        $other = Siswa::factory()->create();
        [, $payload] = $this->barcodeSheet($owner);
        Sanctum::actingAs($other, ['siswa']);

        $this->postJson('/api/v1/quran/barcode/identify', ['sheet_payload' => $payload])
            ->assertForbidden()
            ->assertJsonMissing(['name' => 'Pemilik Rahasia']);
    }

    public function test_pamong_can_identify_assigned_student_but_not_outside_scope(): void
    {
        $pamong = $this->teacher();
        $assigned = Siswa::factory()->create();
        $outside = Siswa::factory()->create(['nama' => 'Di Luar Binaan']);
        PamongSiswa::query()->create(['pamong_id' => $pamong->id, 'siswa_id' => $assigned->id]);
        [, $assignedPayload] = $this->barcodeSheet($assigned);
        [, $outsidePayload] = $this->barcodeSheet($outside);
        Sanctum::actingAs($pamong);

        $this->postJson('/api/v1/quran/barcode/identify', ['sheet_payload' => $assignedPayload])
            ->assertOk()
            ->assertJsonPath('data.student.name', $assigned->nama);

        $this->postJson('/api/v1/quran/barcode/identify', ['sheet_payload' => $outsidePayload])
            ->assertForbidden()
            ->assertJsonMissing(['name' => 'Di Luar Binaan']);
    }

    public function test_parent_is_forbidden_from_identify_and_store(): void
    {
        $student = Siswa::factory()->create();
        [, $payload] = $this->barcodeSheet($student);
        Sanctum::actingAs($student, ['ortu']);

        $this->postJson('/api/v1/quran/barcode/identify', ['sheet_payload' => $payload])->assertForbidden();
        $this->postJson('/api/v1/quran/barcode/store', [
            'flow_id' => Str::random(40),
            'surah_start' => 1,
            'ayah_start' => 1,
            'ayah_end' => 7,
        ])->assertForbidden();
    }

    public function test_student_store_is_pending_uses_flow_owner_and_is_idempotent(): void
    {
        $student = Siswa::factory()->create();
        $attacker = Siswa::factory()->create();
        [, $payload] = $this->barcodeSheet($student);
        Sanctum::actingAs($student, ['siswa']);
        $flowId = $this->postJson('/api/v1/quran/barcode/identify', ['sheet_payload' => $payload])
            ->assertOk()->json('data.flow_id');
        $request = [
            'flow_id' => $flowId,
            'siswa_id' => $attacker->id,
            'surah_start' => 2,
            'ayah_start' => 5,
            'ayah_end' => 12,
            'page_start' => 2,
            'page_end' => 3,
            'notes' => 'Setelah Maghrib',
        ];

        $first = $this->postJson('/api/v1/quran/barcode/store', $request)
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', QuranReadingEntry::STATUS_PENDING);
        $second = $this->postJson('/api/v1/quran/barcode/store', $request)
            ->assertOk()
            ->assertJsonPath('data.entry_id', $first->json('data.entry_id'));

        $this->assertSame(1, QuranReadingEntry::count());
        $entry = QuranReadingEntry::firstOrFail();
        $this->assertSame($student->id, $entry->siswa_id);
        $this->assertSame('siswa', $entry->submitted_by_type);
        $this->assertNull($entry->verified_by);
    }

    public function test_pamong_store_is_verified_and_records_verifier(): void
    {
        $pamong = $this->teacher();
        $student = Siswa::factory()->create();
        PamongSiswa::query()->create(['pamong_id' => $pamong->id, 'siswa_id' => $student->id]);
        [, $payload] = $this->barcodeSheet($student);
        Sanctum::actingAs($pamong);
        $flowId = $this->postJson('/api/v1/quran/barcode/identify', ['sheet_payload' => $payload])
            ->assertOk()->json('data.flow_id');

        $this->postJson('/api/v1/quran/barcode/store', [
            'flow_id' => $flowId,
            'surah_start' => 2,
            'ayah_start' => 286,
            'surah_end' => 3,
            'ayah_end' => 10,
        ])->assertCreated()->assertJsonPath('data.status', QuranReadingEntry::STATUS_VERIFIED);

        $entry = QuranReadingEntry::firstOrFail();
        $this->assertSame($pamong->id, $entry->verified_by);
        $this->assertNotNull($entry->verified_at);
        $this->assertSame('user', $entry->submitted_by_type);
    }

    public function test_store_rejects_invalid_expired_and_actor_mismatched_flows(): void
    {
        $student = Siswa::factory()->create();
        $other = Siswa::factory()->create();
        [, $payload] = $this->barcodeSheet($student);
        Sanctum::actingAs($student, ['siswa']);
        $flowId = $this->postJson('/api/v1/quran/barcode/identify', ['sheet_payload' => $payload])
            ->assertOk()->json('data.flow_id');

        $this->postJson('/api/v1/quran/barcode/store', [
            'flow_id' => $flowId,
            'surah_start' => 1,
            'ayah_start' => 1,
            'ayah_end' => 8,
            'page_start' => 4,
            'page_end' => 3,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['ayah_end', 'page_end']);

        Sanctum::actingAs($other, ['siswa']);
        $this->postJson('/api/v1/quran/barcode/store', [
            'flow_id' => $flowId,
            'surah_start' => 1,
            'ayah_start' => 1,
            'ayah_end' => 7,
        ])->assertUnprocessable()->assertJsonValidationErrors('flow_id');

        $this->travel(31)->minutes();
        Sanctum::actingAs($student, ['siswa']);
        $this->postJson('/api/v1/quran/barcode/store', [
            'flow_id' => $flowId,
            'surah_start' => 1,
            'ayah_start' => 1,
            'ayah_end' => 7,
        ])->assertUnprocessable()->assertJsonValidationErrors('flow_id');
    }

    private function barcodeSheet(Siswa $student): array
    {
        $token = bin2hex(random_bytes(16));
        $sheet = QuranReadingSheet::query()->create([
            'siswa_id' => $student->id,
            'public_id' => (string) Str::uuid(),
            'token_hash' => hash('sha256', $token),
            'status' => 'active',
            'sheet_type' => 'monthly',
            'row_count' => 31,
            'template_version' => 4,
        ]);

        return [$sheet, app(QuranReadingScanService::class)->payload($sheet, $token)];
    }

    private function teacher(): User
    {
        $role = Role::query()->firstOrCreate(['name' => User::ROLE_TEACHER], [
            'display_name' => 'Pamong',
            'permissions' => ['view_students'],
            'is_active' => true,
        ]);

        return User::factory()->create(['role_id' => $role->id, 'status' => 'active']);
    }
}
