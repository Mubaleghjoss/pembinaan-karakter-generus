<?php

namespace Tests\Feature;

use App\Models\Materi;
use App\Models\MateriFolder;
use App\Models\MateriRppJournal;
use App\Models\PamongPermission;
use App\Models\Role;
use App\Models\ScheduleReminder;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MateriRppFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_staff_journal_access_follows_explicit_menu_and_role_permissions(): void
    {
        $pamongRole = Role::query()->create([
            'name' => User::ROLE_TEACHER,
            'display_name' => 'Pamong',
            'permissions' => [],
            'is_active' => true,
        ]);
        $pamong = User::factory()->create(['role_id' => $pamongRole->id]);
        $service = app(\App\Services\MateriRppJournalWorkflowService::class);

        PamongPermission::query()->create([
            'user_id' => $pamong->id,
            'menu_permissions' => ['dashboard'],
            'crud_permissions' => [],
        ]);

        $this->assertFalse($service->canUseStaffJournal($pamong->fresh(['role', 'pamongPermission'])));

        $pamong->pamongPermission()->update([
            'menu_permissions' => ['dashboard', 'rpp_journals'],
            'crud_permissions' => ['rpp_journals' => ['view']],
        ]);

        $pamong = $pamong->fresh(['role', 'pamongPermission']);
        $this->assertTrue($service->canUseStaffJournal($pamong));
        $this->assertFalse($service->canManageAll($pamong));
    }

    public function test_admin_can_publish_rpp_and_calendar_events_are_readable(): void
    {
        $admin = $this->adminUser();
        $siswa = Siswa::factory()->create();

        $response = $this->actingAs($admin)->post(route('materi.store'), $this->rppPayload([
            'total_pages' => 12,
            'pages_per_session' => 6,
            'start_time' => '20:00',
            'end_time' => '21:00',
        ]));

        $response->assertRedirect();

        $materi = Materi::firstOrFail();

        $this->assertSame('published', $materi->rpp_status);
        $this->assertSame('2026-01-12', $materi->rpp_end_date->toDateString());

        $this->assertSame(2, ScheduleReminder::where('source_type', ScheduleReminder::SOURCE_MATERI_RPP)
            ->where('source_id', $materi->id)
            ->count());

        $this->assertDatabaseHas('schedule_reminders', [
            'source_type' => ScheduleReminder::SOURCE_MATERI_RPP,
            'source_id' => $materi->id,
            'target_audience' => 'all',
            'title' => 'Materi RPP Test',
            'start_time' => '20:00:00',
            'end_time' => '21:00:00',
        ]);

        $adminEvents = $this->actingAs($admin)->getJson(route('calendar.events', [
            'start' => '2026-01-01',
            'end' => '2026-02-01',
        ]));

        $adminEvents->assertOk();
        $this->assertRppEventExists($adminEvents->json(), $materi->id);
        $this->assertTrue(
            collect($adminEvents->json())->contains(fn (array $event) => ($event['type'] ?? null) === ScheduleReminder::SOURCE_MATERI_RPP
                && ($event['allDay'] ?? true) === false
                && str_contains($event['start'] ?? '', 'T20:00:00')
                && ($event['extendedProps']['start_time'] ?? null) === '20:00'
                && ($event['extendedProps']['end_time'] ?? null) === '21:00')
        );

        $siswaEvents = $this->actingAs($siswa, 'siswa')->getJson(route('siswa.calendar.events', [
            'start' => '2026-01-01',
            'end' => '2026-02-01',
        ]));

        $siswaEvents->assertOk();
        $this->assertRppEventExists($siswaEvents->json(), $materi->id);
    }

    public function test_public_calendar_reads_published_rpp_without_login(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->post(route('materi.store'), $this->rppPayload([
            'total_pages' => 12,
            'pages_per_session' => 6,
        ]));

        $materi = Materi::firstOrFail();

        $response = $this->getJson(route('public.calendar.events', [
            'start' => '2026-01-01',
            'end' => '2026-02-01',
        ]));

        $response->assertOk();
        $this->assertRppEventExists($response->json(), $materi->id);
    }

    public function test_admin_calendar_reads_active_materi_calendar_date(): void
    {
        $admin = $this->adminUser();
        $folder = MateriFolder::create([
            'name' => 'Akhlaqul Karimah',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('materi.store'), $this->materiPayloadWithoutRpp([
            'judul' => 'Materi Kalender Biasa',
            'materi_folder_id' => $folder->id,
            'calendar_date' => '2026-03-15',
        ]));

        $response->assertRedirect();

        $materi = Materi::where('judul', 'Materi Kalender Biasa')->firstOrFail();

        $this->assertSame('2026-03-15', $materi->calendar_date->toDateString());

        $events = $this->actingAs($admin)->getJson(route('calendar.events', [
            'start' => '2026-03-01',
            'end' => '2026-04-01',
        ]));

        $events->assertOk();

        $event = $this->materiEvent($events->json(), $materi->id);

        $this->assertNotNull($event);
        $this->assertSame('Materi: Materi Kalender Biasa', $event['title']);
        $this->assertSame('2026-03-15', $event['start']);
        $this->assertSame('materi', $event['extendedProps']['type']);
        $this->assertSame('Akhlaqul Karimah', $event['extendedProps']['folder']);
        $this->assertSame(route('materi.show', $materi), $event['extendedProps']['admin_url']);
        $this->assertSame(route('public.materi.show', $materi), $event['extendedProps']['url']);
    }

    public function test_public_calendar_reads_active_materi_and_hides_inactive_materi(): void
    {
        $active = Materi::create([
            'judul' => 'Materi Aktif Kalender',
            'deskripsi' => 'Materi aktif yang boleh tampil.',
            'bulan' => '2026-04-01',
            'calendar_date' => '2026-04-10',
            'is_active' => true,
        ]);
        $inactive = Materi::create([
            'judul' => 'Materi Nonaktif Kalender',
            'deskripsi' => 'Materi nonaktif tidak boleh tampil.',
            'bulan' => '2026-04-01',
            'calendar_date' => '2026-04-10',
            'is_active' => false,
        ]);

        $response = $this->getJson(route('public.calendar.events', [
            'start' => '2026-04-01',
            'end' => '2026-05-01',
        ]));

        $response->assertOk();

        $this->assertNotNull($this->materiEvent($response->json(), $active->id));
        $this->assertNull($this->materiEvent($response->json(), $inactive->id));
    }

    public function test_admin_date_stats_returns_materi_for_selected_date(): void
    {
        $admin = $this->adminUser();
        $folder = MateriFolder::create([
            'name' => 'PKG',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $materi = Materi::create([
            'judul' => 'Materi Tanggal Kalender',
            'materi_folder_id' => $folder->id,
            'deskripsi' => 'Materi untuk ringkasan tanggal.',
            'bulan' => '2026-05-01',
            'calendar_date' => '2026-05-12',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->getJson(route('calendar.date-stats', [
            'date' => '2026-05-12',
        ]));

        $response->assertOk()
            ->assertJsonPath('materi.0.id', $materi->id)
            ->assertJsonPath('materi.0.title', 'Materi Tanggal Kalender')
            ->assertJsonPath('materi.0.folder', 'PKG')
            ->assertJsonPath('materi.0.url', route('materi.show', $materi))
            ->assertJsonPath('materi.0.public_url', route('public.materi.show', $materi));
    }

    public function test_editing_materi_calendar_date_moves_admin_calendar_event_after_cache_clear(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->post(route('materi.store'), $this->materiPayloadWithoutRpp([
            'judul' => 'Materi Pindah Kalender',
            'calendar_date' => '2026-06-03',
        ]));

        $materi = Materi::where('judul', 'Materi Pindah Kalender')->firstOrFail();

        $this->actingAs($admin)->put(route('materi.update', $materi), $this->materiPayloadWithoutRpp([
            'judul' => 'Materi Pindah Kalender',
            'calendar_date' => '2026-06-20',
        ]));

        Cache::flush();

        $oldDateEvents = $this->actingAs($admin)->getJson(route('calendar.events', [
            'start' => '2026-06-03',
            'end' => '2026-06-04',
        ]));
        $newDateEvents = $this->actingAs($admin)->getJson(route('calendar.events', [
            'start' => '2026-06-20',
            'end' => '2026-06-21',
        ]));

        $oldDateEvents->assertOk();
        $newDateEvents->assertOk();

        $this->assertNull($this->materiEvent($oldDateEvents->json(), $materi->id));
        $this->assertSame('2026-06-20', $this->materiEvent($newDateEvents->json(), $materi->id)['start'] ?? null);
    }

    public function test_public_calendar_and_homepage_render(): void
    {
        $rootFolder = MateriFolder::firstOrCreate(
            ['name' => 'PKG'],
            [
                'description' => 'Materi 29 karakter luhur.',
                'sort_order' => 1,
                'is_active' => true,
            ]
        );
        $folder = MateriFolder::create([
            'name' => 'Folder Publik',
            'parent_id' => $rootFolder->id,
            'description' => 'Folder materi yang tampil di beranda.',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $materi = Materi::create([
            'judul' => 'Materi Home Landing',
            'materi_folder_id' => $folder->id,
            'deskripsi' => 'Materi publik yang ditampilkan di panel beranda.',
            'bulan' => '2026-06-01',
            'is_active' => true,
        ]);

        $this->get(route('public.calendar.index'))
            ->assertOk()
            ->assertSee('Kalender Aktivitas')
            ->assertSee('data-calendar-jump', false)
            ->assertSee('data-calendar-prev', false);

        $this->get(route('public.index'))
            ->assertOk()
            ->assertDontSee('Kalender Aktivitas PKG')
            ->assertDontSee('Agenda terstruktur')
            ->assertDontSee('Akses cepat')
            ->assertDontSee('Tampilan baru')
            ->assertSee('home-calendar-materi-panel')
            ->assertSee('Folder Materi')
            ->assertSee('PKG')
            ->assertSee('Folder Publik')
            ->assertSee('Materi Home Landing')
            ->assertSee(route('public.materi.show', $materi), false);
    }

    public function test_guest_must_choose_a_login_before_opening_pdf_materi(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('materi/pdf/materi-uji.pdf', '%PDF-1.4 test');

        $materi = Materi::create([
            'judul' => 'Materi PDF Publik',
            'deskripsi' => 'Materi untuk menguji pembaca PDF.',
            'bulan' => '2026-06-01',
            'pdf_path' => [[
                'name' => 'Materi Uji.pdf',
                'path' => 'materi/pdf/materi-uji.pdf',
                'size' => 1024,
            ]],
            'is_active' => true,
        ]);

        $response = $this->get(route('public.materi.show', $materi));

        $response
            ->assertOk()
            ->assertSee('Login untuk membuka isi materi')
            ->assertSee(route('siswa.login'), false)
            ->assertSee(route('ortu.login'), false)
            ->assertSee(route('login'), false)
            ->assertDontSee('data-pdf-viewer', false)
            ->assertDontSee('data-pdf-canvas', false)
            ->assertDontSee(Storage::url('materi/pdf/materi-uji.pdf'), false)
            ->assertDontSee(route('public.materi.pdf.view', [$materi, 0]), false)
            ->assertDontSee(route('public.materi.pdf.download', [$materi, 0]), false)
            ->assertDontSee('<iframe', false);

        $response->assertSessionHas('url.intended', route('public.materi.show', $materi));

        $this->get(route('public.materi.pdf.download', [$materi, 0]))
            ->assertRedirect(route('public.materi.show', $materi));

        $this->get(route('public.materi.pdf.view', [$materi, 0]))
            ->assertRedirect(route('public.materi.show', $materi));
    }

    public function test_authenticated_siswa_can_use_mobile_safe_pdf_viewer(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('materi/pdf/materi-uji.pdf', '%PDF-1.4 test');

        $materi = Materi::create([
            'judul' => 'Materi PDF Siswa',
            'deskripsi' => 'Materi untuk menguji pembaca PDF.',
            'bulan' => '2026-06-01',
            'pdf_path' => [[
                'name' => 'Materi Uji.pdf',
                'path' => 'materi/pdf/materi-uji.pdf',
                'size' => 1024,
            ]],
            'is_active' => true,
        ]);

        $response = $this->actingAs(Siswa::factory()->create(), 'siswa')
            ->get(route('public.materi.show', $materi));

        $response
            ->assertOk()
            ->assertSee('data-pdf-viewer', false)
            ->assertSee('data-pdf-canvas', false)
            ->assertSee('data-pdf-page-count', false)
            ->assertSee('Buka PDF')
            ->assertSee('Materi PDF Siswa.pdf')
            ->assertDontSee('Materi Uji.pdf')
            ->assertSee(route('public.materi.pdf.view', [$materi, 0]), false)
            ->assertSee(route('public.materi.pdf.download', [$materi, 0]), false)
            ->assertDontSee('Login untuk membuka isi materi');

        $contentSecurityPolicy = $response->headers->get('Content-Security-Policy-Report-Only')
            ?? $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("worker-src 'self' blob:", $contentSecurityPolicy);
        $this->assertStringContainsString("font-src 'self' blob:", $contentSecurityPolicy);

        $this->get(route('public.materi.pdf.download', [$materi, 0]))
            ->assertOk()
            ->assertDownload('Materi PDF Siswa.pdf');

        $pdfResponse = $this->get(route('public.materi.pdf.view', [$materi, 0]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('X-Frame-Options', 'DENY');

        $pdfContentSecurityPolicy = $pdfResponse->headers->get('Content-Security-Policy-Report-Only')
            ?? $pdfResponse->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("frame-ancestors 'none'", $pdfContentSecurityPolicy);
        $this->assertSame('DENY', $response->headers->get('X-Frame-Options'));
        $this->assertStringContainsString("frame-ancestors 'none'", $contentSecurityPolicy);
    }

    public function test_authenticated_parent_uses_canvas_pdf_viewer_without_iframe(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('materi/pdf/materi-ortu.pdf', '%PDF-1.4 test');

        $materi = Materi::create([
            'judul' => 'Materi PDF Orang Tua',
            'deskripsi' => 'Materi untuk menguji modal PDF orang tua.',
            'bulan' => '2026-07-01',
            'pdf_path' => [[
                'name' => 'Materi Orang Tua.pdf',
                'path' => 'materi/pdf/materi-ortu.pdf',
                'size' => 1024,
            ]],
            'is_active' => true,
        ]);

        $parent = Siswa::factory()->create();
        $detailResponse = $this->actingAs($parent, 'ortu')
            ->get(route('ortu.materi.show', $materi));

        $detailResponse
            ->assertOk()
            ->assertSee('data-pdf-viewer', false)
            ->assertSee('data-pdf-canvas', false)
            ->assertSee(route('public.materi.pdf.view', [$materi, 0]), false)
            ->assertDontSee('pdfModal', false)
            ->assertDontSee('<iframe', false)
            ->assertHeader('X-Frame-Options', 'DENY');

        $pdfResponse = $this->get(route('public.materi.pdf.view', [$materi, 0]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('X-Frame-Options', 'DENY');

        $contentSecurityPolicy = $pdfResponse->headers->get('Content-Security-Policy-Report-Only')
            ?? $pdfResponse->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("frame-ancestors 'none'", $contentSecurityPolicy);
    }

    public function test_student_and_pamong_material_pages_use_shared_canvas_pdf_viewer(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('materi/pdf/materi-portal.pdf', '%PDF-1.4 test');

        $materi = Materi::create([
            'judul' => 'Materi PDF Portal',
            'deskripsi' => 'Materi untuk menguji pembaca PDF di setiap portal.',
            'bulan' => '2026-07-01',
            'pdf_path' => [[
                'name' => 'Materi Portal.pdf',
                'path' => 'materi/pdf/materi-portal.pdf',
                'size' => 1024,
            ]],
            'is_active' => true,
        ]);

        $studentResponse = $this->actingAs(Siswa::factory()->create(), 'siswa')
            ->get(route('siswa.materi.show', $materi));

        $studentResponse
            ->assertOk()
            ->assertSee('data-pdf-viewer', false)
            ->assertSee('data-pdf-canvas', false)
            ->assertSee(route('public.materi.pdf.view', [$materi, 0]), false)
            ->assertDontSee('pdfModal', false)
            ->assertDontSee('<iframe', false);

        $pamongResponse = $this->actingAs($this->adminUser())
            ->get(route('materi.show', $materi));

        $pamongResponse
            ->assertOk()
            ->assertSee('data-pdf-viewer', false)
            ->assertSee('data-pdf-canvas', false)
            ->assertSee(route('public.materi.pdf.view', [$materi, 0]), false)
            ->assertDontSee('pdfModal', false)
            ->assertDontSee('<iframe', false);
    }

    public function test_public_materi_embeds_youtube_and_google_drive_videos(): void
    {
        $materi = Materi::create([
            'judul' => 'Materi Video Publik',
            'deskripsi' => 'Materi dengan beberapa link video.',
            'bulan' => '2026-06-01',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'video_links' => [
                'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'https://drive.google.com/file/d/1AbCdEfGhIjKlMnOpQrStUv/view?usp=sharing',
            ],
            'is_active' => true,
        ]);

        $guestResponse = $this->get(route('public.materi.show', $materi));

        $guestResponse
            ->assertOk()
            ->assertSee('Login untuk membuka isi materi')
            ->assertDontSee('https://www.youtube.com/embed/dQw4w9WgXcQ', false)
            ->assertDontSee('https://drive.google.com/file/d/1AbCdEfGhIjKlMnOpQrStUv/preview', false)
            ->assertDontSee('<iframe', false);

        $response = $this->actingAs(Siswa::factory()->create(), 'siswa')
            ->get(route('public.materi.show', $materi));

        $response
            ->assertOk()
            ->assertSee('Video Pembelajaran')
            ->assertSee('https://www.youtube.com/embed/dQw4w9WgXcQ', false)
            ->assertSee('https://drive.google.com/file/d/1AbCdEfGhIjKlMnOpQrStUv/preview', false)
            ->assertSee('allowfullscreen', false);

        $contentSecurityPolicy = $response->headers->get('Content-Security-Policy-Report-Only')
            ?? $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('https://drive.google.com', $contentSecurityPolicy);
    }

    public function test_admin_can_save_many_video_links_for_materi(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->post(route('materi.store'), $this->materiPayloadWithoutRpp([
            'judul' => 'Materi Banyak Video',
        ]) + [
            'video_links' => [
                'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'https://drive.google.com/file/d/1AbCdEfGhIjKlMnOpQrStUv/view',
            ],
        ]);

        $response->assertRedirect();

        $materi = Materi::where('judul', 'Materi Banyak Video')->firstOrFail();

        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $materi->video_url);
        $this->assertSame([
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'https://drive.google.com/file/d/1AbCdEfGhIjKlMnOpQrStUv/view',
        ], $materi->video_link_urls);
    }

    public function test_pdf_names_follow_title_for_multiple_files(): void
    {
        $materi = new Materi([
            'judul' => 'RPP: SMP/SMA',
            'pdf_path' => [
                ['path' => 'materi/pdf/first.pdf'],
                ['path' => 'materi/pdf/second.pdf'],
            ],
        ]);

        $this->assertSame('RPP SMP SMA - 1.pdf', $materi->pdfFileName(0));
        $this->assertSame('RPP SMP SMA - 2.pdf', $materi->pdfFileName(1));
    }

    public function test_new_pdf_metadata_uses_materi_title(): void
    {
        Storage::fake('public');
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->post(route('materi.store'), $this->materiPayloadWithoutRpp([
            'judul' => 'Materi Akhlaqul Karimah',
        ]) + [
            'pdf_files' => [
                UploadedFile::fake()->create('nama-acak.pdf', 100, 'application/pdf'),
            ],
        ]);

        $response->assertRedirect();

        $pdf = Materi::firstOrFail()->pdf_files[0];

        $this->assertSame('Materi Akhlaqul Karimah.pdf', $pdf['name']);
        $this->assertSame('nama-acak.pdf', $pdf['original_name']);
        Storage::disk('public')->assertExists($pdf['path']);
    }

    public function test_admin_can_copy_and_export_month_calendar(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->post(route('materi.store'), $this->rppPayload([
            'total_pages' => 12,
            'pages_per_session' => 6,
            'teacher_pool' => [
                ['name' => 'OM ZED'],
            ],
        ]));

        $share = $this->actingAs($admin)->getJson(route('calendar.share-text', [
            'month' => 1,
            'year' => 2026,
        ]));

        $share->assertOk()
            ->assertJsonPath('success', true);
        $this->assertStringContainsString('Kalender PKG', $share->json('text'));
        $this->assertStringContainsString('RPP Materi', $share->json('text'));
        $this->assertStringContainsString('Materi RPP Test', $share->json('text'));
        $this->assertStringContainsString('Pengajar: OM ZED', $share->json('text'));
        $this->assertStringContainsString('Selengkapnya:', $share->json('text'));

        $export = $this->actingAs($admin)->get(route('calendar.export', [
            'month' => 1,
            'year' => 2026,
        ]));

        $export->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $export->headers->get('content-type')
        );
        $this->assertStringContainsString('Kalender-PKG-2026-01.xlsx', $export->headers->get('content-disposition'));
    }

    public function test_admin_can_update_materi_folder(): void
    {
        $admin = $this->adminUser();
        $folder = MateriFolder::create([
            'name' => 'Folder Lama',
            'description' => 'Keterangan lama',
            'sort_order' => 10,
            'is_active' => true,
        ]);
        $parent = MateriFolder::firstOrCreate(
            ['name' => 'PKG'],
            [
                'description' => 'Materi 29 karakter luhur.',
                'sort_order' => 1,
                'is_active' => true,
            ]
        );

        $response = $this->actingAs($admin)->patch(route('materi.folders.update', $folder), [
            'name' => 'Jujur',
            'parent_id' => $parent->id,
            'description' => 'Materi karakter jujur.',
            'sort_order' => 3,
            'is_active' => '1',
        ]);

        $response->assertRedirect();

        $folder->refresh();

        $this->assertSame('Jujur', $folder->name);
        $this->assertSame($parent->id, $folder->parent_id);
        $this->assertSame('Materi karakter jujur.', $folder->description);
        $this->assertSame(3, $folder->sort_order);
        $this->assertTrue($folder->is_active);
    }

    public function test_admin_can_save_materi_without_rpp_even_when_publish_button_is_sent(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->post(route('materi.store'), $this->materiPayloadWithoutRpp([
            'judul' => 'Materi Tanpa RPP',
            'rpp_action' => 'publish',
            'publish_rpp' => '1',
        ]));

        $response->assertRedirect()
            ->assertSessionHas('success', 'Materi berhasil disimpan tanpa RPP kalender.');

        $materi = Materi::where('judul', 'Materi Tanpa RPP')->firstOrFail();

        $this->assertFalse($materi->rpp_is_enabled);
        $this->assertSame('draft', $materi->rpp_status);
        $this->assertNull($materi->rpp_total_pages);
        $this->assertSame(0, ScheduleReminder::where('source_type', ScheduleReminder::SOURCE_MATERI_RPP)->count());
    }

    public function test_admin_can_disable_rpp_on_update_even_when_publish_button_is_sent(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->post(route('materi.store'), $this->rppPayload([
            'total_pages' => 12,
            'pages_per_session' => 6,
        ]));

        $materi = Materi::firstOrFail();

        $this->assertSame(2, ScheduleReminder::where('source_type', ScheduleReminder::SOURCE_MATERI_RPP)
            ->where('source_id', $materi->id)
            ->count());

        $response = $this->actingAs($admin)->put(route('materi.update', $materi), $this->materiPayloadWithoutRpp([
            'judul' => 'Materi Biasa Revisi',
            'rpp_action' => 'publish',
            'publish_rpp' => '1',
        ]));

        $response->assertRedirect()
            ->assertSessionHas('success', 'Materi berhasil diperbarui tanpa RPP kalender.');

        $materi->refresh();

        $this->assertSame('Materi Biasa Revisi', $materi->judul);
        $this->assertFalse($materi->rpp_is_enabled);
        $this->assertSame('draft', $materi->rpp_status);
        $this->assertNull($materi->rpp_total_pages);
        $this->assertSame(0, ScheduleReminder::where('source_type', ScheduleReminder::SOURCE_MATERI_RPP)
            ->where('source_id', $materi->id)
            ->count());
    }

    public function test_pamong_with_materi_crud_can_save_draft_then_publish_rpp(): void
    {
        $pamong = $this->pamongUser();
        $this->grantMateriCrud($pamong, ['view', 'create', 'edit']);

        $draftResponse = $this->actingAs($pamong)->post(route('materi.store'), $this->rppPayload([
            'action' => 'draft',
            'total_pages' => 12,
            'pages_per_session' => 6,
        ]));

        $draftResponse->assertRedirect();

        $materi = Materi::firstOrFail();

        $this->assertSame('draft', $materi->rpp_status);
        $this->assertSame(0, ScheduleReminder::where('source_type', ScheduleReminder::SOURCE_MATERI_RPP)->count());

        $publishResponse = $this->actingAs($pamong)->patch(route('materi.publish-rpp', $materi));

        $publishResponse->assertRedirect();

        $materi->refresh();

        $this->assertSame('published', $materi->rpp_status);
        $this->assertSame(2, ScheduleReminder::where('source_type', ScheduleReminder::SOURCE_MATERI_RPP)
            ->where('source_id', $materi->id)
            ->count());
    }

    public function test_publish_rpp_with_catch_up_range_creates_one_time_calendar_events(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->post(route('materi.store'), $this->rppPayload([
            'total_pages' => 26,
            'pages_per_session' => 5,
            'catch_up_ranges' => [
                [
                    'start_date' => '2026-01-06',
                    'end_date' => '2026-01-08',
                    'pages' => 5,
                ],
            ],
        ]));

        $response->assertRedirect();

        $materi = Materi::firstOrFail();
        $events = ScheduleReminder::where('source_type', ScheduleReminder::SOURCE_MATERI_RPP)
            ->where('source_id', $materi->id)
            ->orderBy('start_date')
            ->get();

        $this->assertSame('2026-01-19', $materi->rpp_end_date->toDateString());
        $this->assertSame(6, $events->count());
        $this->assertSame(
            ['2026-01-05', '2026-01-06', '2026-01-07', '2026-01-08', '2026-01-12', '2026-01-19'],
            $events->pluck('start_date')->map->toDateString()->all()
        );
        $this->assertSame('catch_up', $events[1]->source_payload['type']);
        $this->assertSame('Halaman 6-10', $events[1]->source_payload['page_range']);
    }

    public function test_publish_rpp_flag_publishes_even_when_action_value_is_missing(): void
    {
        $admin = $this->adminUser();
        $payload = $this->rppPayload([
            'total_pages' => 12,
            'pages_per_session' => 6,
        ]);
        unset($payload['rpp_action']);
        $payload['publish_rpp'] = '1';

        $response = $this->actingAs($admin)->post(route('materi.store'), $payload);

        $response->assertRedirect();

        $materi = Materi::firstOrFail();

        $this->assertSame('published', $materi->rpp_status);
        $this->assertSame(2, ScheduleReminder::where('source_type', ScheduleReminder::SOURCE_MATERI_RPP)
            ->where('source_id', $materi->id)
            ->count());
    }

    public function test_publish_rpp_assigns_rotating_teachers_from_account_names(): void
    {
        $admin = $this->adminUser();
        $teacherRole = Role::create([
            'name' => User::ROLE_TEACHER,
            'display_name' => 'Pamong',
            'permissions' => ['view_students'],
            'is_active' => true,
        ]);
        $teacherA = User::factory()->create(['role_id' => $teacherRole->id, 'name' => 'Mas A']);
        $teacherB = User::factory()->create(['role_id' => $teacherRole->id, 'name' => 'Mas B']);

        $response = $this->actingAs($admin)->post(route('materi.store'), $this->rppPayload([
            'total_pages' => 18,
            'pages_per_session' => 6,
            'teacher_pool' => [
                ['user_id' => $teacherA->id],
                ['user_id' => $teacherB->id],
            ],
        ]));

        $response->assertRedirect();

        $materi = Materi::firstOrFail();
        $events = ScheduleReminder::where('source_type', ScheduleReminder::SOURCE_MATERI_RPP)
            ->where('source_id', $materi->id)
            ->orderBy('start_date')
            ->get();

        $this->assertSame('Materi RPP Test', $events[0]->title);
        $this->assertSame('Materi RPP Test', $events[1]->title);
        $this->assertSame('Materi RPP Test', $events[2]->title);
        $this->assertSame($teacherA->id, $events[0]->source_payload['teacher_user_id']);
        $this->assertSame($teacherB->id, $events[1]->source_payload['teacher_user_id']);
        $this->assertSame('Mas B', $events[1]->source_payload['teacher_name']);
        $this->assertFalse($events[1]->source_payload['teacher_is_override']);
    }

    public function test_rpp_preview_reads_selected_account_teachers_and_manual_teacher(): void
    {
        $admin = $this->adminUser();
        $teacherRole = Role::create([
            'name' => User::ROLE_TEACHER,
            'display_name' => 'Pamong',
            'permissions' => ['view_students'],
            'is_active' => true,
        ]);
        $afif = User::factory()->create(['role_id' => $teacherRole->id, 'name' => '', 'username' => 'AFIF']);
        $galih = User::factory()->create(['role_id' => $teacherRole->id, 'name' => '', 'username' => 'GALIH']);

        $response = $this->actingAs($admin)->postJson(route('materi.rpp-preview'), [
            'judul' => 'RPP Juz 1',
            'rpp_total_pages' => 18,
            'rpp_start_page' => 1,
            'rpp_pages_per_session' => 6,
            'rpp_start_date' => '2026-01-05',
            'rpp_teacher_pool' => [
                ['user_id' => $afif->id],
                ['user_id' => $galih->id],
                ['name' => 'OM ZED'],
            ],
        ]);

        $response->assertOk();

        $this->assertSame(
            ['AFIF', 'GALIH', 'OM ZED'],
            collect($response->json('sessions'))->pluck('teacher_name')->all()
        );
        $this->assertStringContainsString('Judul: RPP Juz 1', $response->json('share_text'));
        $this->assertStringContainsString('1. Senin, 05-01-2026', $response->json('share_text'));
        $this->assertStringContainsString('Materi: Halaman 1-6', $response->json('share_text'));
        $this->assertStringContainsString('Pengajar: AFIF', $response->json('share_text'));
        $this->assertStringContainsString('Pengajar: OM ZED', $response->json('share_text'));
    }

    public function test_pamong_view_only_cannot_create_or_preview_rpp(): void
    {
        $pamong = $this->pamongUser();
        $this->grantMateriCrud($pamong, ['view']);

        $response = $this->actingAs($pamong)
            ->from('/materi/create')
            ->post(route('materi.store'), $this->rppPayload());

        $response->assertRedirect('/materi/create');
        $this->assertSame(0, Materi::count());

        $this->actingAs($pamong)
            ->postJson(route('materi.rpp-preview'), [
                'rpp_total_pages' => 12,
                'rpp_start_page' => 1,
                'rpp_pages_per_session' => 6,
                'rpp_start_date' => '2026-01-05',
            ])
            ->assertForbidden();
    }

    public function test_republishing_rpp_preserves_existing_event_ids_assignments_and_journals(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->post(route('materi.store'), $this->rppPayload([
            'total_pages' => 12,
            'pages_per_session' => 6,
        ]));

        $materi = Materi::firstOrFail();
        $oldEventIds = ScheduleReminder::where('source_type', ScheduleReminder::SOURCE_MATERI_RPP)
            ->where('source_id', $materi->id)
            ->pluck('id')
            ->all();
        $firstEvent = ScheduleReminder::findOrFail($oldEventIds[0]);
        $siswa = Siswa::factory()->create();
        $firstEvent->update([
            'journal_assignee_type' => 'siswa',
            'journal_assignee_user_id' => null,
            'journal_assignee_siswa_id' => $siswa->id,
        ]);
        $firstEvent->journalAssignees()->create([
            'assignee_type' => 'siswa',
            'siswa_id' => $siswa->id,
            'assigned_by' => $admin->id,
        ]);
        $journal = MateriRppJournal::create([
            'schedule_reminder_id' => $firstEvent->id,
            'materi_id' => $materi->id,
            'journal_date' => $firstEvent->start_date,
            'materi_title' => $materi->judul,
            'realization_status' => MateriRppJournal::STATUS_TERLAKSANA,
            'workflow_status' => MateriRppJournal::WORKFLOW_APPROVED,
        ]);

        $this->actingAs($admin)->put(route('materi.update', $materi), $this->rppPayload([
            'total_pages' => 18,
            'pages_per_session' => 6,
        ]));

        $newEvents = ScheduleReminder::where('source_type', ScheduleReminder::SOURCE_MATERI_RPP)
            ->where('source_id', $materi->id)
            ->get();

        $this->assertSame(3, $newEvents->count());
        $this->assertSame($oldEventIds, ScheduleReminder::whereIn('id', $oldEventIds)->orderBy('id')->pluck('id')->all());
        $this->assertSame($siswa->id, $firstEvent->refresh()->journal_assignee_siswa_id);
        $this->assertTrue($firstEvent->journalAssignees()->where('siswa_id', $siswa->id)->exists());
        $this->assertSame($firstEvent->id, $journal->refresh()->schedule_reminder_id);
        $this->assertTrue($newEvents->contains('title', 'Materi RPP Test'));
        $this->assertTrue($newEvents->contains(fn (ScheduleReminder $event) => ($event->source_payload['page_range'] ?? null) === 'Halaman 13-18'));
    }

    private function rppPayload(array $overrides = []): array
    {
        $payload = [
            'judul' => $overrides['judul'] ?? 'Materi RPP Test',
            'deskripsi' => $overrides['deskripsi'] ?? 'Materi dengan rencana pembelajaran otomatis.',
            'bulan' => $overrides['bulan'] ?? '2026-01-01',
            'video_url' => $overrides['video_url'] ?? null,
            'rpp_action' => $overrides['action'] ?? 'publish',
            'rpp_is_enabled' => '1',
            'rpp_total_pages' => $overrides['total_pages'] ?? 12,
            'rpp_start_page' => $overrides['start_page'] ?? 1,
            'rpp_pages_per_session' => $overrides['pages_per_session'] ?? 6,
            'rpp_start_date' => $overrides['start_date'] ?? '2026-01-05',
            'rpp_extra_sessions' => $overrides['extra_sessions'] ?? [],
            'rpp_catch_up_ranges' => $overrides['catch_up_ranges'] ?? [],
            'rpp_teacher_pool' => $overrides['teacher_pool'] ?? [],
        ];

        if (array_key_exists('start_time', $overrides)) {
            $payload['rpp_start_time'] = $overrides['start_time'];
        }

        if (array_key_exists('end_time', $overrides)) {
            $payload['rpp_end_time'] = $overrides['end_time'];
        }

        return $payload;
    }

    private function materiPayloadWithoutRpp(array $overrides = []): array
    {
        $payload = [
            'judul' => $overrides['judul'] ?? 'Materi Tanpa RPP',
            'deskripsi' => $overrides['deskripsi'] ?? 'Materi biasa tanpa rencana kalender.',
            'bulan' => $overrides['bulan'] ?? '2026-02-01',
            'calendar_date' => $overrides['calendar_date'] ?? null,
            'materi_folder_id' => $overrides['materi_folder_id'] ?? null,
            'video_url' => $overrides['video_url'] ?? null,
            'rpp_action' => $overrides['rpp_action'] ?? 'draft',
        ];

        if (array_key_exists('publish_rpp', $overrides)) {
            $payload['publish_rpp'] = $overrides['publish_rpp'];
        }

        return $payload;
    }

    private function adminUser(): User
    {
        $role = Role::create([
            'name' => 'admin',
            'display_name' => 'Administrator',
            'permissions' => ['*'],
            'is_active' => true,
        ]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function pamongUser(): User
    {
        $role = Role::create([
            'name' => User::ROLE_TEACHER,
            'display_name' => 'Pamong',
            'permissions' => ['view_students'],
            'is_active' => true,
        ]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function grantMateriCrud(User $user, array $operations): void
    {
        PamongPermission::create([
            'user_id' => $user->id,
            'menu_permissions' => ['materi'],
            'crud_permissions' => ['materi' => $operations],
            'is_excluded' => false,
        ]);

        $user->unsetRelation('pamongPermission');
    }

    private function assertRppEventExists(array $events, int $materiId): void
    {
        $this->assertTrue(
            collect($events)->contains(function (array $event) use ($materiId) {
                return ($event['type'] ?? null) === ScheduleReminder::SOURCE_MATERI_RPP
                    && ($event['extendedProps']['materi_id'] ?? null) === $materiId
                    && ($event['extendedProps']['page_range'] ?? null) === 'Halaman 1-6';
            }),
            'Event RPP materi harus muncul di response kalender.'
        );
    }

    private function materiEvent(array $events, int $materiId): ?array
    {
        return collect($events)->first(fn (array $event) => ($event['type'] ?? null) === 'materi'
            && ($event['extendedProps']['materi_id'] ?? null) === $materiId);
    }
}
