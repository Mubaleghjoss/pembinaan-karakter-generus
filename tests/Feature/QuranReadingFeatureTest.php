<?php

namespace Tests\Feature;

use App\Models\QuranReadingEntry;
use App\Models\QuranReadingScan;
use App\Models\QuranReadingSheet;
use App\Models\PamongPermission;
use App\Models\PamongSiswa;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class QuranReadingFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_submission_is_pending_and_admin_can_verify_it(): void
    {
        $siswa = Siswa::factory()->create();

        $this->actingAs($siswa, 'siswa')
            ->post(route('siswa.quran.store'), $this->entryPayload())
            ->assertRedirect()
            ->assertSessionHas('success');

        $entry = QuranReadingEntry::firstOrFail();
        $this->assertSame(QuranReadingEntry::STATUS_PENDING, $entry->status);
        $this->assertSame('siswa', $entry->submitted_by_type);

        $admin = $this->admin();
        $this->actingAs($admin)
            ->patch(route('quran.verify', $entry), ['verification_notes' => 'Bacaan sesuai.'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $entry->refresh();
        $this->assertSame(QuranReadingEntry::STATUS_VERIFIED, $entry->status);
        $this->assertSame($admin->id, $entry->verified_by);

        $this->actingAs($admin)
            ->put(route('quran.update', $entry), $this->entryPayload(['page_end' => 4]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(4, $entry->fresh()->page_end);
        $this->actingAs($admin)
            ->patch(route('quran.verify', $entry))
            ->assertStatus(409);
    }

    public function test_student_cannot_update_another_students_entry_or_verified_entry(): void
    {
        $owner = Siswa::factory()->create();
        $other = Siswa::factory()->create();
        $entry = QuranReadingEntry::create($this->entryPayload() + [
            'siswa_id' => $owner->id,
            'source' => 'manual',
            'submitted_by_type' => 'siswa',
            'submitted_by_id' => $owner->id,
            'status' => QuranReadingEntry::STATUS_PENDING,
        ]);

        $this->actingAs($other, 'siswa')
            ->put(route('siswa.quran.update', $entry), $this->entryPayload(['page_end' => 4]))
            ->assertForbidden();

        $entry->update(['status' => QuranReadingEntry::STATUS_VERIFIED]);
        $this->actingAs($owner, 'siswa')
            ->put(route('siswa.quran.update', $entry), $this->entryPayload(['page_end' => 4]))
            ->assertStatus(409);
    }

    public function test_parent_only_sees_verified_entries_for_their_child(): void
    {
        $siswa = Siswa::factory()->create();
        QuranReadingEntry::create($this->entryPayload(['notes' => 'Catatan Terverifikasi']) + [
            'siswa_id' => $siswa->id,
            'source' => 'manual',
            'submitted_by_type' => 'user',
            'submitted_by_id' => 1,
            'status' => QuranReadingEntry::STATUS_VERIFIED,
        ]);
        QuranReadingEntry::create($this->entryPayload(['notes' => 'Catatan Rahasia Pending']) + [
            'siswa_id' => $siswa->id,
            'source' => 'manual',
            'submitted_by_type' => 'siswa',
            'submitted_by_id' => $siswa->id,
            'status' => QuranReadingEntry::STATUS_PENDING,
        ]);

        $this->actingAs($siswa, 'ortu')
            ->get(route('ortu.quran.index'))
            ->assertOk()
            ->assertSee('Catatan Terverifikasi')
            ->assertDontSee('Catatan Rahasia Pending');
    }

    public function test_invalid_ayah_for_selected_surah_is_rejected(): void
    {
        $siswa = Siswa::factory()->create();

        $this->actingAs($siswa, 'siswa')
            ->from(route('siswa.quran.index'))
            ->post(route('siswa.quran.store'), $this->entryPayload([
                'surah_start' => 1,
                'ayah_start' => 8,
            ]))
            ->assertSessionHasErrors('ayah_start');
    }

    public function test_admin_can_download_private_report_and_structured_sheet(): void
    {
        $siswa = Siswa::factory()->create([
            'nama' => 'Nur Áisyah Putri',
            'nis' => 'PKG2607123456',
        ]);
        $admin = $this->admin();
        QuranReadingEntry::create($this->entryPayload() + [
            'siswa_id' => $siswa->id,
            'source' => 'manual',
            'submitted_by_type' => 'user',
            'submitted_by_id' => $admin->id,
            'status' => QuranReadingEntry::STATUS_VERIFIED,
            'verified_by' => $admin->id,
            'verified_at' => now(),
        ]);

        $report = $this->actingAs($admin)->get(route('quran.report', $siswa));
        $report->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString("filename*=utf-8''Nur%20%C3%81isyah%20Putri%20-%20Laporan%20Bacaan%20Al-Quran.pdf", (string) $report->headers->get('content-disposition'));

        $sheetResponse = $this->actingAs($admin)->get(route('quran.sheet', $siswa));
        $sheetResponse->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString("filename*=utf-8''Nur%20%C3%81isyah%20Putri%20-%20Lembar%20Lanjutan%20Bacaan%20Al-Quran.pdf", (string) $sheetResponse->headers->get('content-disposition'));

        $this->assertDatabaseHas('quran_reading_sheets', ['siswa_id' => $siswa->id, 'row_count' => 12]);

        $sheet = QuranReadingSheet::latest('id')->firstOrFail();
        $html = view('quran-reading.pdf.sheet', [
            'sheet' => $sheet,
            'siswa' => $siswa->load('kelas'),
            'qrDataUri' => 'data:image/svg+xml;base64,PHN2Zz48L3N2Zz4=',
            'catalog' => \App\Support\QuranCatalog::class,
        ])->render();
        $this->assertStringContainsString('Nur Áisyah Putri', $html);
        $this->assertStringContainsString('*********3456', $html);
        $this->assertStringNotContainsString('PKG2607123456', $html);
    }

    public function test_student_and_operational_tracer_use_permission_aware_deep_link_tabs(): void
    {
        config()->set('quran-reading.scan_enabled', true);
        $siswa = Siswa::factory()->create(['nama' => 'Generus Tab']);

        $this->actingAs($siswa, 'siswa')
            ->get(route('siswa.quran.index', ['tab' => 'scan']))
            ->assertOk()
            ->assertSee('Riwayat')
            ->assertSee('Catat Bacaan')
            ->assertSee('Scan Lembar')
            ->assertSee('data-quran-scan-form', false);

        $this->actingAs($siswa, 'siswa')
            ->get(route('siswa.quran.index', ['tab' => 'tidak-valid']))
            ->assertOk()
            ->assertSee("activeTab: 'rekap'", false);

        $this->actingAs($siswa, 'siswa')
            ->get(route('siswa.quran.scan'))
            ->assertRedirect(route('siswa.quran.index', ['tab' => 'scan']).'#scan');

        $admin = $this->admin();
        $this->actingAs($admin)
            ->get(route('quran.index', ['tab' => 'scan', 'siswa_id' => $siswa->id]))
            ->assertOk()
            ->assertSee('Rekap &amp; Verifikasi', false)
            ->assertSee('Input Manual')
            ->assertSee('Scan Lembar')
            ->assertSee('Generus Tab');

        $this->actingAs($admin)
            ->get(route('quran.scan', $siswa))
            ->assertRedirect(route('quran.index', ['tab' => 'scan', 'siswa_id' => $siswa->id]).'#scan');
    }

    public function test_structured_scan_requires_valid_sheet_qr_and_confirms_rows_idempotently(): void
    {
        config()->set('quran-reading.scan_enabled', true);
        Storage::fake('local');
        $siswa = Siswa::factory()->create();
        $admin = $this->admin();
        $token = Str::random(48);
        $sheet = QuranReadingSheet::create([
            'siswa_id' => $siswa->id,
            'public_id' => (string) Str::uuid(),
            'token_hash' => hash('sha256', $token),
            'status' => 'active',
            'row_count' => 12,
            'generated_by' => $admin->id,
        ]);

        $upload = $this->actingAs($admin)->post(route('quran.scan.upload'), [
            'sheet_payload' => 'PKGQURAN:'.$sheet->public_id.':'.$token,
            'scan_image' => UploadedFile::fake()->image('lembar.jpg', 800, 1200),
        ]);

        $scan = QuranReadingScan::firstOrFail();
        $upload->assertRedirect(route('quran.scan.confirm', $scan));
        Storage::disk('local')->assertExists($scan->original_path);

        $this->actingAs($admin)->get(route('quran.scan.confirm', $scan))->assertOk();
        $this->actingAs($admin)->post(route('quran.scan.confirm.store', $scan), [
            'rows' => [1 => $this->entryPayload(['row_number' => 1])],
        ])->assertRedirect(route('quran.index', ['tab' => 'rekap', 'siswa_id' => $siswa->id]).'#rekap');

        $this->assertDatabaseHas('quran_reading_entries', [
            'sheet_id' => $sheet->id,
            'sheet_row_number' => 1,
            'status' => QuranReadingEntry::STATUS_VERIFIED,
        ]);
        $this->assertSame(1, QuranReadingEntry::where('sheet_id', $sheet->id)->count());

        $this->actingAs($admin)->post(route('quran.scan.confirm.store', $scan), [
            'rows' => [1 => $this->entryPayload(['row_number' => 1])],
        ])->assertStatus(409);
        $this->assertSame(1, QuranReadingEntry::where('sheet_id', $sheet->id)->count());

        $secondUpload = $this->actingAs($admin)->post(route('quran.scan.upload'), [
            'sheet_payload' => 'PKGQURAN:'.$sheet->public_id.':'.$token,
            'scan_image' => UploadedFile::fake()->image('lembar-ulang.jpg', 800, 1200),
        ]);
        $secondScan = QuranReadingScan::latest('id')->firstOrFail();
        $secondUpload->assertRedirect(route('quran.scan.confirm', $secondScan));
        $this->actingAs($admin)->post(route('quran.scan.confirm.store', $secondScan), [
            'rows' => [1 => $this->entryPayload(['row_number' => 1, 'page_end' => 9])],
        ])->assertRedirect();
        $this->assertSame(3, QuranReadingEntry::where('sheet_id', $sheet->id)->firstOrFail()->page_end);
        $this->assertSame(1, QuranReadingEntry::where('sheet_id', $sheet->id)->count());
    }

    public function test_student_scan_is_pending_and_private_image_cannot_be_opened_by_another_student(): void
    {
        config()->set('quran-reading.scan_enabled', true);
        Storage::fake('local');
        $siswa = Siswa::factory()->create();
        $other = Siswa::factory()->create();
        $token = Str::random(48);
        $sheet = QuranReadingSheet::create([
            'siswa_id' => $siswa->id,
            'public_id' => (string) Str::uuid(),
            'token_hash' => hash('sha256', $token),
            'status' => 'active',
            'row_count' => 12,
        ]);

        $this->actingAs($siswa, 'siswa')->post(route('siswa.quran.scan.upload'), [
            'sheet_payload' => 'PKGQURAN:'.$sheet->public_id.':'.$token,
            'scan_image' => UploadedFile::fake()->image('lembar-siswa.jpg', 800, 1200),
        ])->assertRedirect();

        $scan = QuranReadingScan::firstOrFail();
        $this->actingAs($other, 'siswa')->get(route('siswa.quran.scan.image', $scan))->assertForbidden();

        $this->actingAs($siswa, 'siswa')->post(route('siswa.quran.scan.confirm.store', $scan), [
            'rows' => [1 => $this->entryPayload(['row_number' => 1])],
        ])->assertRedirect(route('siswa.quran.index', ['tab' => 'rekap']).'#rekap');

        $this->assertDatabaseHas('quran_reading_entries', [
            'siswa_id' => $siswa->id,
            'sheet_id' => $sheet->id,
            'status' => QuranReadingEntry::STATUS_PENDING,
        ]);

        $admin = $this->admin();
        $this->actingAs($admin)
            ->get(route('quran.scan.image', $scan))
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private');
        $this->actingAs($admin)
            ->get(route('quran.index'))
            ->assertOk()
            ->assertSee(route('quran.scan.image', $scan), false);
    }

    public function test_public_scanner_accepts_compact_sheet_qr_and_creates_pending_entry_after_confirmation(): void
    {
        config()->set('quran-reading.scan_enabled', true);
        Storage::fake('local');
        $siswa = Siswa::factory()->create(['nama' => 'Generus Scan Publik']);
        $token = bin2hex(random_bytes(16));
        $publicId = (string) Str::uuid();
        $sheet = QuranReadingSheet::create([
            'siswa_id' => $siswa->id,
            'public_id' => $publicId,
            'token_hash' => hash('sha256', $token),
            'status' => 'active',
            'row_count' => 12,
            'template_version' => 2,
        ]);
        $payload = 'PKGQ:'.strtoupper(str_replace('-', '', $publicId)).':'.strtoupper($token);

        $this->get(route('public.scanner', ['mode' => 'quran']))
            ->assertOk()
            ->assertSee('data-public-scan-mode="quran"', false)
            ->assertSee('data-quran-scan-root', false);

        $upload = $this->post(route('public.quran.scan.upload'), [
            'sheet_payload' => $payload,
            'scan_image' => UploadedFile::fake()->image('lembar-publik.jpg', 1200, 1800),
            'ocr_suggestion' => json_encode([[
                'row_number' => 1,
                'reading_date' => now()->toDateString(),
                'page_start' => 1,
                'page_end' => 2,
                'surah_start' => 1,
                'ayah_start' => 1,
                'surah_end' => 1,
                'ayah_end' => 7,
                'confidence' => ['page_start' => 91],
            ]]),
        ]);

        $scan = QuranReadingScan::firstOrFail();
        $this->assertNull($scan->uploaded_by_id);
        $upload->assertRedirect(route('public.quran.scan.confirm', $scan));
        $this->get(route('public.quran.scan.confirm', $scan))
            ->assertOk()
            ->assertSee('Generus Scan Publik')
            ->assertSee('value="1"', false);

        $this->post(route('public.quran.scan.confirm.store', $scan), [
            'rows' => [1 => $this->entryPayload(['row_number' => 1])],
        ])->assertRedirect(route('public.scanner', ['mode' => 'quran']).'#quran');

        $this->assertDatabaseHas('quran_reading_entries', [
            'sheet_id' => $sheet->id,
            'sheet_row_number' => 1,
            'submitted_by_type' => 'public',
            'submitted_by_id' => null,
            'status' => QuranReadingEntry::STATUS_PENDING,
        ]);

        $this->actingAs($this->admin())
            ->get(route('quran.scan.image', $scan))
            ->assertOk();
    }

    public function test_public_scan_confirmation_is_bound_to_the_uploading_session(): void
    {
        config()->set('quran-reading.scan_enabled', true);
        Storage::fake('local');
        $siswa = Siswa::factory()->create();
        $scan = QuranReadingScan::create([
            'siswa_id' => $siswa->id,
            'uploaded_by_type' => 'public',
            'uploaded_by_id' => null,
            'original_path' => 'quran-reading-scans/private.jpg',
            'status' => 'awaiting_confirmation',
        ]);
        Storage::disk('local')->put($scan->original_path, 'private');

        $this->get(route('public.quran.scan.confirm', $scan))->assertForbidden();
        $this->get(route('public.quran.scan.image', $scan))->assertForbidden();
    }

    public function test_scan_rejects_oversized_uploads(): void
    {
        config()->set('quran-reading.scan_enabled', true);
        Storage::fake('local');
        $owner = Siswa::factory()->create();
        $admin = $this->admin();
        $token = Str::random(48);
        $sheet = QuranReadingSheet::create([
            'siswa_id' => $owner->id,
            'public_id' => (string) Str::uuid(),
            'token_hash' => hash('sha256', $token),
            'status' => 'active',
            'row_count' => 12,
        ]);

        $this->actingAs($admin)->from(route('quran.index', ['tab' => 'scan', 'siswa_id' => $owner->id]))
            ->post(route('quran.scan.upload'), [
                'sheet_payload' => 'PKGQURAN:'.$sheet->public_id.':'.$token,
                'scan_image' => UploadedFile::fake()->create('terlalu-besar.jpg', 8193, 'image/jpeg'),
            ])->assertSessionHasErrors('scan_image');
    }

    public function test_scanner_is_hidden_until_the_photo_pilot_is_enabled(): void
    {
        $siswa = Siswa::factory()->create();

        $this->actingAs($siswa, 'siswa')
            ->get(route('siswa.quran.scan'))
            ->assertNotFound();
    }

    public function test_pamong_only_sees_assigned_students_and_guru_isolated_from_operational_tracer(): void
    {
        $assigned = Siswa::factory()->create(['nama' => 'Generus Binaan']);
        $outside = Siswa::factory()->create(['nama' => 'Generus Luar']);
        $teacherRole = Role::query()->firstOrCreate(['name' => User::ROLE_TEACHER], [
            'display_name' => 'Pamong', 'permissions' => [], 'is_active' => true,
        ]);
        $pamong = User::factory()->create(['role_id' => $teacherRole->id]);
        PamongPermission::create([
            'user_id' => $pamong->id,
            'menu_permissions' => ['dashboard', 'tracer_bacaan_quran'],
            'crud_permissions' => ['tracer_bacaan_quran' => ['view', 'create', 'verify', 'export']],
        ]);
        PamongSiswa::create(['pamong_id' => $pamong->id, 'siswa_id' => $assigned->id]);

        $this->actingAs($pamong)->get(route('quran.index'))
            ->assertOk()
            ->assertSee('Generus Binaan')
            ->assertDontSee('Generus Luar');

        $this->actingAs($pamong)->get(route('quran.report', $outside))->assertForbidden();

        $guruRole = Role::query()->firstOrCreate(['name' => User::ROLE_GURU], [
            'display_name' => 'Guru', 'permissions' => [], 'is_active' => true,
        ]);
        $guru = User::factory()->create(['role_id' => $guruRole->id]);
        $this->actingAs($guru)->get(route('quran.index'))->assertRedirect();
    }

    public function test_view_only_pamong_does_not_see_input_or_scan_tabs(): void
    {
        config()->set('quran-reading.scan_enabled', true);
        $assigned = Siswa::factory()->create();
        $teacherRole = Role::query()->firstOrCreate(['name' => User::ROLE_TEACHER], [
            'display_name' => 'Pamong', 'permissions' => [], 'is_active' => true,
        ]);
        $pamong = User::factory()->create(['role_id' => $teacherRole->id]);
        PamongPermission::create([
            'user_id' => $pamong->id,
            'menu_permissions' => ['dashboard', 'tracer_bacaan_quran'],
            'crud_permissions' => ['tracer_bacaan_quran' => ['view']],
        ]);
        PamongSiswa::create(['pamong_id' => $pamong->id, 'siswa_id' => $assigned->id]);

        $this->actingAs($pamong)->get(route('quran.index', ['tab' => 'scan', 'siswa_id' => $assigned->id]))
            ->assertOk()
            ->assertSee('Rekap &amp; Verifikasi', false)
            ->assertDontSee('Input Manual')
            ->assertDontSee('Scan Lembar');

        $this->actingAs($pamong)->get(route('quran.scan', $assigned))->assertRedirect();
    }

    private function entryPayload(array $overrides = []): array
    {
        return array_merge([
            'reading_date' => now()->toDateString(),
            'page_start' => 1,
            'page_end' => 3,
            'surah_start' => 1,
            'ayah_start' => 1,
            'surah_end' => 2,
            'ayah_end' => 5,
            'mushaf_label' => 'Mushaf Madinah',
            'notes' => 'Latihan tartil',
        ], $overrides);
    }

    private function admin(): User
    {
        $role = Role::query()->firstOrCreate(['name' => User::ROLE_ADMIN], [
            'display_name' => 'Administrator',
            'permissions' => ['*'],
            'is_active' => true,
        ]);

        return User::factory()->create(['role_id' => $role->id]);
    }
}
