<?php

namespace Tests\Unit;

use Tests\TestCase;

class AdminMobileDisclosureRegressionTest extends TestCase
{
    public function test_batch_two_identity_and_access_views_use_collapsed_mobile_disclosures(): void
    {
        $files = [
            'resources/views/users/index.blade.php' => 'user-mobile-details-{{ $user->id }}',
            'resources/views/siswa/accounts.blade.php' => 'account-mobile-details-{{ $siswa->id }}',
            'resources/views/admin/ortu/index.blade.php' => 'ortu-mobile-details-{{ $s->id }}',
            'resources/views/pamong/partials/data.blade.php' => 'pamong-data-details-${pamong.id}',
            'resources/views/pamong/partials/akun.blade.php' => 'pamong-account-details-${pamong.id}',
            'resources/views/pamong/partials/permissions.blade.php' => 'pamong-permissions-details-${pamong.id}',
            'resources/views/pamong/partials/qr.blade.php' => 'pamong-qr-details-${pamong.id}',
            'resources/views/pamong/partials/bidang.blade.php' => 'bidang-mobile-details-{{ $team->id }}',
            'resources/views/pamong/permissions-index.blade.php' => 'permissions-index-details-{{ $p->id }}',
        ];

        foreach ($files as $path => $control) {
            $view = file_get_contents(base_path($path));

            $this->assertNotFalse($view, "Unable to read {$path}");
            $this->assertStringContainsString($control, $view, "{$path} needs an accessible disclosure control");
            $this->assertStringContainsString('x-cloak x-show="detailsOpen"', $view, "{$path} must collapse secondary content by default");
        }
    }

    public function test_reset_credentials_are_not_an_always_visible_mobile_summary(): void
    {
        $ortu = file_get_contents(base_path('resources/views/admin/ortu/index.blade.php'));
        $pamong = file_get_contents(base_path('resources/views/pamong/show.blade.php'));

        $this->assertStringContainsString('id="ortu-mobile-details-{{ $s->id }}" x-cloak x-show="detailsOpen"', $ortu);
        $this->assertStringContainsString('<details class="sm:hidden">', $pamong);
        $this->assertStringContainsString('class="hidden sm:inline"', $pamong);
    }
}
