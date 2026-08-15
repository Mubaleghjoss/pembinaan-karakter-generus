<?php

namespace Tests\Feature;

use App\Models\PamongPermission;
use App\Models\PamongSiswa;
use App\Models\QuranProgressSubmission;
use App\Models\QuranReadingCycle;
use App\Models\QuranReadingEntry;
use App\Models\QuranReadingScan;
use App\Models\QuranReadingSheet;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use App\Services\QuranKhatamService;
use App\Services\QuranReadingScanService;
use App\Support\QuranCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use setasign\Fpdi\Fpdi;
use Tests\TestCase;

class QuranReadingFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_quran_gallery_picker_does_not_force_the_camera(): void
    {
        config()->set('quran-reading.scan_enabled', true);

        $this->get(route('public.scanner', ['mode' => 'quran']))
            ->assertOk()
            ->assertSee('Pilih dari Galeri')
            ->assertSee('data-quran-scan-file', false)
            ->assertDontSee('capture="environment"', false);
    }

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
        $this->assertStringContainsString("filename*=utf-8''Nur%20%C3%81isyah%20Putri%20-%20Lembar%20Bacaan%20Al-Quran%20Bulanan.pdf", (string) $sheetResponse->headers->get('content-disposition'));
        $this->assertSame(1, $this->pdfPageCount($sheetResponse->getContent()));

        $this->assertDatabaseHas('quran_reading_sheets', [
            'siswa_id' => $siswa->id,
            'sheet_type' => 'monthly',
            'row_count' => 31,
            'template_version' => 4,
        ]);

        $sheet = QuranReadingSheet::latest('id')->firstOrFail();
        $html = view('quran-reading.pdf.document', [
            'pages' => [[
                'type' => 'monthly',
                'sheet' => $sheet,
                'siswa' => $siswa->load('kelas'),
                'pamongNames' => 'Pamong Penguji',
                'qrDataUri' => 'data:image/png;base64,aW1hZ2U=',
            ]],
            'logoDataUri' => null,
            'verseImageDataUri' => 'data:image/png;base64,aW1hZ2U=',
            'catalog' => QuranCatalog::class,
        ])->render();
        $this->assertStringContainsString('class="document-title">Lembar Bacaan Al-Qur\'an Bulanan', $html);
        $this->assertStringContainsString('class="student-name">Nur Áisyah Putri', $html);
        $this->assertStringContainsString('class="verse-image"', $html);
        $this->assertStringContainsString('Nur Áisyah Putri', $html);
        $this->assertStringContainsString('*********3456', $html);
        $this->assertStringNotContainsString('PKG2607123456', $html);
        $this->assertSame(31, substr_count($html, '<td class="no">'));
        $this->assertStringContainsString('Pamong Penguji', $html);
        $this->assertStringContainsString('وَرَتِّلِ الْقُرْاٰنَ تَرْتِيْلًاۗ', $html);
    }

    public function test_admin_can_download_three_blank_documents_without_creating_sheet_records(): void
    {
        $admin = $this->admin();

        $monthly = $this->actingAs($admin)->get(route('quran.blank.monthly'));
        $monthly->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertSame(1, $this->pdfPageCount($monthly->getContent()));

        $reference = $this->actingAs($admin)->get(route('quran.blank.reference'));
        $reference->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertSame(1, $this->pdfPageCount($reference->getContent()));

        $duplex = $this->actingAs($admin)->get(route('quran.blank.duplex'));
        $duplex->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertSame(2, $this->pdfPageCount($duplex->getContent()));
        $this->assertSame(0, QuranReadingSheet::count());

        $documents = app(\App\Services\QuranReadingDocumentService::class);
        $html = view('quran-reading.pdf.document', [
            'pages' => [$documents->blankMonthlyPage(), $documents->blankReferencePage()],
            'catalog' => QuranCatalog::class,
            'logoDataUri' => null,
            'verseImageDataUri' => null,
        ])->render();
        $this->assertSame(31, substr_count($html, '<td class="no">'));
        $this->assertSame(114, substr_count($html, 'class="check-box"'));
        $this->assertStringContainsString('Untuk pencatatan manual — tidak dipindai.', $html);
        $this->assertStringNotContainsString('qrDataUri', $html);
        $this->assertStringNotContainsString('ID lembar:', $html);
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
        $scan->refresh();
        $this->assertNull($scan->original_path);
        $this->assertNotNull($scan->files_purged_at);
        $this->actingAs($admin)->get(route('quran.scan.image', $scan))->assertStatus(410);
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

    public function test_monthly_sheet_qr_accepts_row_thirty_one_and_rejects_row_thirty_two(): void
    {
        config()->set('quran-reading.scan_enabled', true);
        Storage::fake('local');
        $siswa = Siswa::factory()->create();
        $admin = $this->admin();
        $token = bin2hex(random_bytes(16));
        $sheet = QuranReadingSheet::create([
            'siswa_id' => $siswa->id,
            'public_id' => (string) Str::uuid(),
            'token_hash' => hash('sha256', $token),
            'status' => 'active',
            'sheet_type' => 'monthly',
            'row_count' => 31,
            'template_version' => 4,
            'generated_by' => $admin->id,
        ]);
        $payload = app(QuranReadingScanService::class)->payload($sheet, $token);
        $this->assertStringStartsWith('PKGQMB:', $payload);

        $this->actingAs($admin)->post(route('quran.scan.upload'), [
            'sheet_payload' => $payload,
            'scan_image' => UploadedFile::fake()->image('bulanan.jpg', 1800, 1200),
        ])->assertRedirect();
        $scan = QuranReadingScan::firstOrFail();

        $this->actingAs($admin)->post(route('quran.scan.confirm.store', $scan), [
            'rows' => [31 => $this->entryPayload(['row_number' => 31])],
        ])->assertRedirect();
        $this->assertDatabaseHas('quran_reading_entries', [
            'sheet_id' => $sheet->id,
            'sheet_row_number' => 31,
            'status' => QuranReadingEntry::STATUS_VERIFIED,
        ]);

        $this->actingAs($admin)->post(route('quran.scan.upload'), [
            'sheet_payload' => $payload,
            'scan_image' => UploadedFile::fake()->image('bulanan-ulang.jpg', 1800, 1200),
        ])->assertRedirect();
        $secondScan = QuranReadingScan::latest('id')->firstOrFail();
        $this->actingAs($admin)
            ->from(route('quran.scan.confirm', $secondScan))
            ->post(route('quran.scan.confirm.store', $secondScan), [
                'rows' => [32 => $this->entryPayload(['row_number' => 32])],
            ])
            ->assertRedirect(route('quran.scan.confirm', $secondScan))
            ->assertSessionHasErrors('rows.32.row_number');
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
            'rows' => [
                1 => $this->entryPayload(['row_number' => 1]),
                2 => $this->entryPayload(['row_number' => 2, 'page_start' => 4, 'page_end' => 5]),
            ],
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

        $entries = QuranReadingEntry::where('scan_id', $scan->id)->orderBy('sheet_row_number')->get();
        $this->actingAs($admin)->patch(route('quran.verify', $entries[0]))->assertRedirect();
        $scan->refresh();
        Storage::disk('local')->assertExists($scan->original_path);
        $this->assertNull($scan->files_purged_at);

        $this->actingAs($admin)->patch(route('quran.reject', $entries[1]), [
            'verification_notes' => 'Angka pada baris kedua tidak sesuai.',
        ])->assertRedirect();
        $scan->refresh();
        $this->assertNull($scan->original_path);
        $this->assertNotNull($scan->files_purged_at);
        $this->actingAs($admin)->get(route('quran.scan.image', $scan))->assertStatus(410);
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
            ->assertSee('"page_start":1', false)
            ->assertSee('data-quran-confirm-rows', false)
            ->assertSee('Tambah Baris');

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

    public function test_cleanup_command_purges_unconfirmed_scans_older_than_one_day(): void
    {
        config()->set('quran-reading.scan_enabled', true);
        Storage::fake('local');
        $siswa = Siswa::factory()->create();
        $scan = QuranReadingScan::create([
            'siswa_id' => $siswa->id,
            'uploaded_by_type' => 'public',
            'uploaded_by_id' => null,
            'original_path' => 'quran-reading-scans/expired-original.jpg',
            'processed_path' => 'quran-reading-scans/expired-processed.jpg',
            'status' => 'awaiting_confirmation',
        ]);
        Storage::disk('local')->put($scan->original_path, 'original');
        Storage::disk('local')->put($scan->processed_path, 'processed');
        $scan->forceFill([
            'created_at' => now()->subHours(25),
            'updated_at' => now()->subHours(25),
        ])->saveQuietly();

        $this->artisan('quran-scans:cleanup')->assertExitCode(0);

        $scan->refresh();
        $this->assertSame('expired', $scan->status);
        $this->assertNull($scan->original_path);
        $this->assertNull($scan->processed_path);
        $this->assertNotNull($scan->files_purged_at);
        Storage::disk('local')->assertMissing('quran-reading-scans/expired-original.jpg');
        Storage::disk('local')->assertMissing('quran-reading-scans/expired-processed.jpg');
        $this->actingAs($this->admin())->get(route('quran.scan.image', $scan))->assertStatus(410);
    }

    public function test_failed_storage_cleanup_keeps_paths_for_the_next_retry(): void
    {
        $siswa = Siswa::factory()->create();
        $scan = QuranReadingScan::create([
            'siswa_id' => $siswa->id,
            'uploaded_by_type' => 'user',
            'uploaded_by_id' => $this->admin()->id,
            'original_path' => 'quran-reading-scans/retry.jpg',
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
        $disk = Mockery::mock();
        $disk->shouldReceive('exists')->once()->with($scan->original_path)->andReturnTrue();
        $disk->shouldReceive('delete')->once()->with($scan->original_path)->andReturnFalse();
        Storage::shouldReceive('disk')->twice()->with('local')->andReturn($disk);

        $this->assertFalse(app(QuranReadingScanService::class)->purgeFiles($scan));

        $scan->refresh();
        $this->assertSame('quran-reading-scans/retry.jpg', $scan->original_path);
        $this->assertNull($scan->files_purged_at);
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

    public function test_admin_can_download_bulk_monthly_reference_and_duplex_documents_for_selected_students(): void
    {
        $admin = $this->admin();
        $students = Siswa::factory()->count(2)->create();

        $monthly = $this->actingAs($admin)->post(route('quran.bulk-sheets'), [
            'document_type' => 'monthly',
            'selection_mode' => 'selected',
            'selected_ids' => $students->pluck('id')->all(),
        ]);
        $monthly->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertSame(2, $this->pdfPageCount($monthly->getContent()));
        $this->assertSame(2, QuranReadingSheet::where('sheet_type', 'monthly')->count());
        $this->assertSame(2, QuranReadingSheet::where('sheet_type', 'monthly')->distinct('public_id')->count('public_id'));

        $reference = $this->actingAs($admin)->post(route('quran.bulk-sheets'), [
            'document_type' => 'surah_reference',
            'selection_mode' => 'selected',
            'selected_ids' => $students->pluck('id')->all(),
        ]);
        $reference->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertSame(2, $this->pdfPageCount($reference->getContent()));
        $this->assertSame(2, QuranReadingSheet::where('sheet_type', 'monthly')->count());

        $duplex = $this->actingAs($admin)->post(route('quran.bulk-sheets'), [
            'document_type' => 'duplex',
            'selection_mode' => 'selected',
            'selected_ids' => $students->pluck('id')->all(),
        ]);
        $duplex->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertSame(4, $this->pdfPageCount($duplex->getContent()));
        $this->assertSame(4, QuranReadingSheet::where('sheet_type', 'monthly')->count());

        $legacyAlias = $this->actingAs($admin)->post(route('quran.bulk-sheets'), [
            'document_type' => 'weekly',
            'selection_mode' => 'selected',
            'selected_ids' => [$students->first()->id],
        ]);
        $legacyAlias->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertSame(1, $this->pdfPageCount($legacyAlias->getContent()));
        $this->assertSame(5, QuranReadingSheet::where('sheet_type', 'monthly')->count());
    }

    public function test_bulk_duplex_pdf_handles_forty_five_students_with_a_256_mb_limit(): void
    {
        $admin = $this->admin();
        $students = Siswa::factory()->count(45)->create();

        $response = $this->actingAs($admin)->post(route('quran.bulk-sheets'), [
            'document_type' => 'duplex',
            'selection_mode' => 'selected',
            'selected_ids' => $students->pluck('id')->all(),
        ]);

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertSame(90, $this->pdfPageCount($response->getContent()));
        $this->assertSame(45, QuranReadingSheet::where('sheet_type', 'monthly')->count());
        $this->assertSame([], glob(storage_path('app/private/quran-pdf-temp/*')) ?: []);
    }

    public function test_surah_reference_has_114_surahs_in_three_columns_and_masked_nis_without_qr(): void
    {
        $siswa = Siswa::factory()->create(['nama' => 'Generus Peta', 'nis' => 'PKG12345678']);
        $html = view('quran-reading.pdf.document', [
            'pages' => [['type' => 'reference', 'siswa' => $siswa, 'pamongNames' => 'Pamong Peta']],
            'catalog' => QuranCatalog::class,
            'logoDataUri' => null,
        ])->render();

        $this->assertSame(114, substr_count($html, 'class="check-box"'));
        $this->assertSame(3, substr_count($html, 'class="column"'));
        $this->assertStringContainsString('*******5678', $html);
        $this->assertStringNotContainsString('PKG12345678', $html);
        $this->assertStringContainsString('>114</td>', $html);
        $this->assertStringContainsString('An-Nas', $html);
        $this->assertStringNotContainsString('qrDataUri', $html);
    }

    public function test_map_scan_by_student_is_pending_then_verification_updates_progress_and_purges_image(): void
    {
        config()->set('quran-reading.scan_enabled', true);
        Storage::fake('local');
        $siswa = Siswa::factory()->create();
        $cycle = app(QuranKhatamService::class)->activeCycle($siswa);
        $token = bin2hex(random_bytes(16));
        $sheet = QuranReadingSheet::create([
            'siswa_id' => $siswa->id, 'public_id' => (string) Str::uuid(),
            'token_hash' => hash('sha256', $token), 'status' => 'active', 'row_count' => 114,
            'template_version' => 1, 'sheet_type' => 'surah_map', 'cycle_id' => $cycle->id,
            'metadata' => ['baseline_completed_surahs' => []],
        ]);
        $payload = app(QuranReadingScanService::class)->payload($sheet, $token);

        $this->actingAs($siswa, 'siswa')->post(route('siswa.quran.scan.upload'), [
            'sheet_payload' => $payload,
            'scan_image' => UploadedFile::fake()->image('peta.jpg', 1600, 1000),
            'ocr_suggestion' => json_encode(['type' => 'surah_map', 'completed_surahs' => [1, 2], 'ambiguous_surahs' => [3]]),
        ])->assertRedirect();
        $scan = QuranReadingScan::firstOrFail();

        $this->actingAs($siswa, 'siswa')->get(route('siswa.quran.scan.confirm', $scan))
            ->assertOk()->assertSee('Periksa Peta Khatam')->assertSee('Al-Fatihah');
        $this->actingAs($siswa, 'siswa')->post(route('siswa.quran.scan.confirm.store', $scan), [
            'completed_surahs' => [1, 2], 'active_surah' => 3, 'active_ayah' => 10,
            'marked_on' => now()->toDateString(),
        ])->assertRedirect(route('siswa.quran.index', ['tab' => 'khatam']).'#khatam');

        $submission = QuranProgressSubmission::firstOrFail();
        $this->assertSame(QuranProgressSubmission::STATUS_PENDING, $submission->status);
        Storage::disk('local')->assertExists($scan->fresh()->original_path);

        $admin = $this->admin();
        $this->actingAs($admin)->patch(route('quran.progress.verify', $submission))->assertRedirect();
        $this->assertDatabaseHas('quran_surah_progress', ['cycle_id' => $cycle->id, 'surah_number' => 1, 'last_ayah' => 7]);
        $this->assertDatabaseHas('quran_surah_progress', ['cycle_id' => $cycle->id, 'surah_number' => 3, 'last_ayah' => 10]);
        $this->assertNotNull($scan->fresh()->files_purged_at);
    }

    public function test_completed_khatam_cycle_creates_a_new_cycle_without_erasing_history(): void
    {
        $siswa = Siswa::factory()->create();
        $admin = $this->admin();
        $service = app(QuranKhatamService::class);
        $first = $service->activeCycle($siswa, $admin->id);
        $submission = QuranProgressSubmission::create([
            'siswa_id' => $siswa->id,
            'cycle_id' => $first->id,
            'marked_on' => now()->toDateString(),
            'completed_surahs' => range(1, 114),
            'status' => QuranProgressSubmission::STATUS_PENDING,
            'submitted_by_type' => 'user',
            'submitted_by_id' => $admin->id,
        ]);

        $service->applySubmission($submission, $admin->id);
        $this->assertSame(QuranReadingCycle::STATUS_COMPLETED, $first->fresh()->status);
        $this->assertSame(114, $first->progress()->whereNotNull('completed_at')->count());

        $second = $service->activeCycle($siswa, $admin->id);
        $this->assertSame(2, $second->cycle_number);
        $this->assertSame(QuranReadingCycle::STATUS_ACTIVE, $second->status);
        $this->assertSame(0, $second->progress()->count());
        $this->assertSame(2, $siswa->quranReadingCycles()->count());
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

    private function pdfPageCount(string $contents): int
    {
        $path = tempnam(sys_get_temp_dir(), 'pkg-quran-pdf-');
        file_put_contents($path, $contents);

        try {
            return (new Fpdi)->setSourceFile($path);
        } finally {
            @unlink($path);
        }
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
