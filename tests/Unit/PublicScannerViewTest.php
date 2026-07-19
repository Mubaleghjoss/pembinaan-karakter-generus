<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PublicScannerViewTest extends TestCase
{
    #[Test]
    public function scanner_controls_are_shown_before_schedule_without_the_marketing_hero(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2) . '/resources/views/public/scanner.blade.php');

        $this->assertStringContainsString('flex max-w-4xl flex-col', $view);
        $this->assertStringContainsString('order-1 mx-auto w-full max-w-md', $view);
        $this->assertStringContainsString('order-2 mt-8 pkg-surface', $view);
        $this->assertStringContainsString('Mulai Pindai QR', $view);

        $this->assertStringNotContainsString('Presensi digital', $view);
        $this->assertStringNotContainsString('Pindai presensi lebih cepat dan lebih jelas.', $view);
        $this->assertStringNotContainsString('QR &amp; Wajah', $view);
        $this->assertStringNotContainsString('QR & Wajah', $view);
    }
}
