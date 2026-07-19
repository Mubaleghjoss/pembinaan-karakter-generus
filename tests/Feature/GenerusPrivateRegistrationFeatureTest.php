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
use Tests\TestCase;

class GenerusPrivateRegistrationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_private_link_creates_student_and_parent_accounts_with_signed_statement(): void
    {
        Storage::fake('local');
        [$invite, $token] = $this->invite();

        $this->get(route('public.generus-registration.show', ['token' => $token]))
            ->assertOk()
            ->assertSee('Pendaftaran Generus PKG Baru')
            ->assertSee('TTD Orang Tua')
            ->assertSee('TTD Generus');

        $response = $this->post(route('public.generus-registration.store', ['token' => $token]), [
            'parent_name' => 'Bapak Penguji',
            'parent_phone' => '0812 3456 7890',
            'student_name' => 'Generus Baru',
            'student_phone' => '+62 813-2222-3333',
            'kelompok' => ParticipantProfileOptions::PANUNGGANGAN_UTARA,
            'birth_place' => 'Tangerang',
            'birth_date' => '2010-04-12',
            'school_grade' => TargetGrade::SMA_10,
            'parent_signature' => $this->signature(),
            'student_signature' => $this->signature(),
            'statement_accepted' => '1',
        ]);

        $response->assertRedirect();

        $registration = GenerusRegistration::query()->with('siswa')->firstOrFail();
        $siswa = $registration->siswa;

        $this->assertSame('Generus Baru', $siswa->nama);
        $this->assertSame('Bapak Penguji', $siswa->nama_wali);
        $this->assertSame('081234567890', $siswa->phone_wali);
        $this->assertSame('081322223333', $siswa->phone);
        $this->assertSame('Tangerang', $siswa->tempat_lahir);
        $this->assertSame(TargetGrade::SMA_10, $siswa->target_grade_override);
        $this->assertSame($siswa->nis, $siswa->ortu_username);
        $this->assertTrue(Hash::check($siswa->nis, $siswa->password));
        $this->assertTrue(Hash::check($siswa->nis, $siswa->ortu_password));
        $this->assertSame(1, $invite->fresh()->used_count);
        Storage::disk('local')->assertExists($registration->parent_signature_path);
        Storage::disk('local')->assertExists($registration->student_signature_path);

        $resultUrl = $response->headers->get('Location');
        $this->get($resultUrl)
            ->assertOk()
            ->assertSee('Pendaftaran Berhasil')
            ->assertSee($siswa->nis);

        $this->get($resultUrl.'/pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_invalid_or_expired_private_link_is_not_accessible(): void
    {
        [$invite, $token] = $this->invite();
        $invite->update(['expires_at' => now()->subMinute()]);

        $this->get(route('public.generus-registration.show', ['token' => $token]))->assertNotFound();
        $this->get(route('public.generus-registration.show', ['token' => 'token-tidak-valid']))->assertNotFound();
    }

    public function test_registration_link_is_not_listed_in_public_navigation(): void
    {
        $navigation = file_get_contents(resource_path('views/layouts/public.blade.php'));

        $this->assertStringNotContainsString('pendaftaran-generus', $navigation);
        $this->assertStringNotContainsString('generus-registration', $navigation);
    }

    private function invite(): array
    {
        $token = 'private-registration-token-for-testing';
        $invite = GenerusRegistrationInvite::query()->create([
            'label' => 'Undangan Pengujian',
            'token_hash' => hash('sha256', $token),
            'max_uses' => 3,
            'used_count' => 0,
            'expires_at' => now()->addDay(),
            'is_active' => true,
        ]);

        return [$invite, $token];
    }

    private function signature(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
    }
}
