<?php

namespace Tests\Feature;

use App\Models\GenerusRegistration;
use App\Models\GenerusRegistrationInvite;
use App\Models\Siswa;
use App\Support\ParticipantProfileOptions;
use App\Support\TargetGrade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

class GenerusPrivateRegistrationFeatureTest extends TestCase
{
    use RefreshDatabase;

    private int $studentSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    public function test_short_page_requires_valid_access_code(): void
    {
        [$invite, $code] = $this->invite();

        $this->get('/daftarpkg')->assertOk()->assertSee('Kode Akses');
        $this->post(route('public.generus-registration.short.unlock'), ['access_code' => 'SALAH99'])
            ->assertSessionHasErrors('access_code');

        $this->post(route('public.generus-registration.short.unlock'), ['access_code' => strtolower($code)])
            ->assertRedirect(route('public.generus-registration.short.index'));
        $this->get('/daftarpkg')
            ->assertOk()
            ->assertSee('Generus Baru')
            ->assertSee('Sudah Terdaftar');

        $invite->update(['expires_at' => now()->subMinute()]);
        $this->withSession([])->post(route('public.generus-registration.short.unlock'), ['access_code' => $code])
            ->assertSessionHasErrors('access_code');
    }

    public function test_access_session_expires_after_sixty_minutes_and_full_invite_is_rejected(): void
    {
        [$invite, $code] = $this->invite();
        $this->unlock($code);

        $this->withSession(['generus_registration.unlocked_at' => now()->subMinutes(61)->timestamp])
            ->get('/daftarpkg')
            ->assertOk()
            ->assertSee('Kode Akses');
        $this->post(route('public.generus-registration.short.store'), $this->payload())
            ->assertRedirect(route('public.generus-registration.short.index'))
            ->assertSessionHasErrors('access_code');

        $invite->update(['used_count' => $invite->max_uses]);
        $this->post(route('public.generus-registration.short.unlock'), ['access_code' => $code])
            ->assertSessionHasErrors('access_code');
    }

    public function test_search_is_protected_redacted_and_only_returns_active_students(): void
    {
        [$invite, $code] = $this->invite();
        $active = $this->student(['nama' => 'Angga Aktif', 'phone' => '081234567890']);
        $this->student(['nama' => 'Angga Nonaktif', 'status' => 'inactive', 'is_active' => false]);

        $this->getJson(route('public.generus-registration.short.search', ['q' => 'Angga']))
            ->assertForbidden();
        $this->unlock($code);

        $response = $this->getJson(route('public.generus-registration.short.search', ['q' => 'Angga']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama', $active->nama)
            ->assertJsonMissing(['phone' => '081234567890']);

        $this->assertArrayNotHasKey('phone', $response->json('data.0'));
        $this->assertArrayNotHasKey('birth_date', $response->json('data.0'));
        $this->assertStringStartsWith('***', $response->json('data.0.nis_masked'));
        $this->getJson(route('public.generus-registration.short.search', ['q' => '%%%']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');
    }

    public function test_existing_student_can_be_verified_by_student_or_parent_account(): void
    {
        [$invite, $code] = $this->invite();
        $siswa = $this->student();
        $this->unlock($code);
        $token = $this->selectionToken($siswa->nama);

        $this->postJson(route('public.generus-registration.short.verify'), [
            'selection_token' => $token,
            'login_type' => 'siswa',
            'username' => $siswa->nis,
            'password' => 'siswa-secret',
        ])->assertOk()->assertJsonPath('student.student_name', $siswa->nama);

        $this->postJson(route('public.generus-registration.short.verify'), [
            'selection_token' => $token,
            'login_type' => 'ortu',
            'username' => $siswa->ortu_username,
            'password' => 'ortu-secret',
        ])->assertOk();

        $this->postJson(route('public.generus-registration.short.verify'), [
            'selection_token' => $token,
            'login_type' => 'siswa',
            'username' => $siswa->nis,
            'password' => 'salah',
        ])->assertUnprocessable()->assertJsonValidationErrors('credentials');
    }

    public function test_invalid_or_different_student_selection_cannot_be_used_for_update(): void
    {
        [$invite, $code] = $this->invite();
        $first = $this->student(['nama' => 'Angga Pertama']);
        $second = $this->student(['nama' => 'Angga Kedua']);
        $this->unlock($code);

        $this->postJson(route('public.generus-registration.short.verify'), [
            'selection_token' => 'token-yang-diubah',
            'login_type' => 'siswa',
            'username' => $first->nis,
            'password' => 'siswa-secret',
        ])->assertUnprocessable()->assertJsonValidationErrors('selection_token');

        $firstToken = $this->selectionToken('Angga Pertama');
        $this->verifyStudent($first, $firstToken);
        $secondToken = $this->selectionToken('Angga Kedua');

        $this->post(route('public.generus-registration.short.store'), $this->payload([
            'registration_mode' => 'existing',
            'selected_student_token' => $secondToken,
            'student_name' => $second->nama,
        ]))->assertSessionHasErrors('credentials');

        $this->assertDatabaseCount('generus_registrations', 0);
    }

    public function test_existing_student_updates_shared_biodata_without_resetting_credentials_or_duplicates(): void
    {
        Storage::fake('local');
        [$invite, $code] = $this->invite();
        $siswa = $this->student();
        $studentCount = Siswa::count();
        $this->unlock($code);
        $token = $this->selectionToken($siswa->nama);
        $this->verifyStudent($siswa, $token);

        $response = $this->post(route('public.generus-registration.short.store'), $this->payload([
            'registration_mode' => 'existing',
            'selected_student_token' => $token,
            'parent_name' => 'Orang Tua Diperbarui',
            'student_name' => 'Angga Diperbarui',
        ]));

        $response->assertRedirect();
        $this->get($response->headers->get('Location').'?account=created')
            ->assertOk()
            ->assertDontSee('Password awal')
            ->assertSee('Username dan password lama tetap sama');
        $this->assertSame($studentCount, Siswa::count());
        $this->assertDatabaseCount('generus_registrations', 1);
        $siswa->refresh();
        $this->assertSame('Angga Diperbarui', $siswa->nama);
        $this->assertSame('Orang Tua Diperbarui', $siswa->nama_wali);
        $this->assertTrue(Hash::check('siswa-secret', $siswa->password));
        $this->assertTrue(Hash::check('ortu-secret', $siswa->ortu_password));
        $this->assertSame(1, $invite->fresh()->used_count);

        $registration = GenerusRegistration::firstOrFail();
        $oldParentPath = $registration->parent_signature_path;
        Storage::disk('local')->assertExists($oldParentPath);

        $this->verifyStudent($siswa, $this->selectionToken('Angga Diperbarui'));
        $this->post(route('public.generus-registration.short.store'), $this->payload([
            'registration_mode' => 'existing',
            'selected_student_token' => $this->selectionToken('Angga Diperbarui'),
            'student_name' => 'Angga Diperbarui Lagi',
        ]))->assertRedirect();

        $this->assertDatabaseCount('generus_registrations', 1);
        $this->assertSame(1, $invite->fresh()->used_count);
        Storage::disk('local')->assertMissing($oldParentPath);
    }

    public function test_new_registration_still_creates_student_parent_accounts_and_pdf(): void
    {
        Storage::fake('local');
        [$invite, $code] = $this->invite();
        $this->unlock($code);

        $response = $this->post(route('public.generus-registration.short.store'), $this->payload());
        $response->assertRedirect();

        $registration = GenerusRegistration::query()->with('siswa')->firstOrFail();
        $siswa = $registration->siswa;
        $this->assertSame($siswa->nis, $siswa->ortu_username);
        $this->assertTrue(Hash::check($siswa->nis, $siswa->password));
        $this->assertTrue(Hash::check($siswa->nis, $siswa->ortu_password));
        $this->assertSame(1, $invite->fresh()->used_count);
        $resultUrl = $response->headers->get('Location');
        $this->get($resultUrl)->assertOk()->assertSee($siswa->nis);
        $this->get(str($resultUrl)->before('?').'/pdf')
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_statement_is_available_only_to_matching_student_and_parent_profiles(): void
    {
        Storage::fake('local');
        [$invite, $code] = $this->invite();
        $siswa = $this->student();
        $this->unlock($code);
        $token = $this->selectionToken($siswa->nama);
        $this->verifyStudent($siswa, $token);
        $this->post(route('public.generus-registration.short.store'), $this->payload([
            'registration_mode' => 'existing',
            'selected_student_token' => $token,
            'student_name' => $siswa->nama,
        ]))->assertRedirect();

        $this->actingAs($siswa->fresh(), 'siswa')
            ->get(route('siswa.profile'))
            ->assertOk()->assertSee('Surat Pernyataan PKG')->assertSee('Sudah lengkap');
        $this->get(route('siswa.profile.statement.preview'))
            ->assertOk()->assertHeader('content-type', 'application/pdf');

        $this->actingAs($siswa->fresh(), 'ortu')
            ->get(route('ortu.settings'))
            ->assertOk()->assertSee('Surat Pernyataan PKG')->assertSee('Unduh PDF');
        $this->get(route('ortu.settings.statement.download'))
            ->assertOk()->assertHeader('content-type', 'application/pdf');

        $other = $this->student(['nama' => 'Siswa Lain']);
        $this->actingAs($other, 'siswa')
            ->get(route('siswa.profile.statement.preview'))
            ->assertNotFound();
    }

    public function test_legacy_private_link_redirects_to_short_page_and_is_not_in_navigation(): void
    {
        [$invite, $token] = $this->invite('LEGACYTOKEN123456');

        $this->get(route('public.generus-registration.show', ['token' => $token]))
            ->assertRedirect(route('public.generus-registration.short.index'));
        $this->followingRedirects()
            ->get(route('public.generus-registration.show', ['token' => $token]))
            ->assertSee('Generus Baru');

        $navigation = file_get_contents(resource_path('views/layouts/public.blade.php'));
        $this->assertStringNotContainsString('daftarpkg', $navigation);
        $this->assertStringNotContainsString('generus-registration', $navigation);
    }

    private function unlock(string $code): void
    {
        $this->post(route('public.generus-registration.short.unlock'), ['access_code' => $code])
            ->assertRedirect(route('public.generus-registration.short.index'));
    }

    private function selectionToken(string $name): string
    {
        return $this->getJson(route('public.generus-registration.short.search', ['q' => $name]))
            ->assertOk()
            ->json('data.0.selection_token');
    }

    private function verifyStudent(Siswa $siswa, string $token): void
    {
        $this->postJson(route('public.generus-registration.short.verify'), [
            'selection_token' => $token,
            'login_type' => 'siswa',
            'username' => $siswa->nis,
            'password' => 'siswa-secret',
        ])->assertOk();
    }

    private function student(array $attributes = []): Siswa
    {
        $this->studentSequence++;
        $sequence = str_pad((string) $this->studentSequence, 3, '0', STR_PAD_LEFT);

        return Siswa::factory()->create(array_merge([
            'nis' => '1002003'.$sequence,
            'password' => 'siswa-secret',
            'nama' => 'Angga Terdaftar',
            'kelompok' => ParticipantProfileOptions::PANUNGGANGAN_UTARA,
            'phone' => '081299991111',
            'tempat_lahir' => 'Tangerang',
            'target_grade_override' => TargetGrade::SMA_10,
            'ortu_username' => 'ortu-angga-'.$sequence,
            'ortu_password' => 'ortu-secret',
        ], $attributes));
    }

    private function invite(string $code = 'PKG8CODE'): array
    {
        $invite = GenerusRegistrationInvite::query()->create([
            'label' => 'Undangan Pengujian',
            'token_hash' => hash('sha256', $code),
            'max_uses' => 50,
            'used_count' => 0,
            'expires_at' => now()->addDays(180),
            'is_active' => true,
        ]);

        return [$invite, $code];
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'registration_mode' => 'new',
            'parent_name' => 'Bapak Penguji',
            'parent_phone' => '081234567890',
            'student_name' => 'Generus Baru',
            'student_phone' => '081322223333',
            'kelompok' => ParticipantProfileOptions::PANUNGGANGAN_UTARA,
            'birth_place' => 'Tangerang',
            'birth_date' => '2010-04-12',
            'school_grade' => TargetGrade::SMA_10,
            'parent_signature' => $this->signature(),
            'student_signature' => $this->signature(),
            'statement_accepted' => '1',
        ], $overrides);
    }

    private function signature(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
    }
}
