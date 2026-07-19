<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SiswaTugasPkgViewTest extends TestCase
{
    public function test_microphone_permission_panel_and_automatic_prompt_are_removed(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2) . '/resources/views/siswa/tugas-pkg/index.blade.php');

        $this->assertStringNotContainsString('Izin mikrofon untuk voice note', $view);
        $this->assertStringNotContainsString('Aktifkan Mikrofon', $view);
        $this->assertStringNotContainsString('Tutup Cara', $view);
        $this->assertStringNotContainsString('initMediaPermissionGate', $view);
        $this->assertStringNotContainsString('requestMicrophonePermission', $view);

        $this->assertStringContainsString('async startVoiceRecording(event)', $view);
        $this->assertStringContainsString('navigator.mediaDevices.getUserMedia({ audio: true })', $view);
    }
}
