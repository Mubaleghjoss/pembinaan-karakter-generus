<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SiswaTugasPkgViewTest extends TestCase
{
    public function test_mobile_task_cards_keep_content_above_the_full_width_action(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2) . '/resources/views/siswa/tugas-pkg/index.blade.php');
        $css = file_get_contents(dirname(__DIR__, 2) . '/resources/css/app.css');

        $this->assertStringContainsString('pkg-student-task-header-actions', $view);
        $this->assertStringContainsString('pkg-student-task-list', $view);
        $this->assertStringContainsString('pkg-student-task-card-layout', $view);
        $this->assertStringContainsString('pkg-student-task-action', $view);
        $this->assertStringContainsString('w-full text-base font-bold', $view);
        $this->assertStringContainsString('.pkg-student-task-action > *', $css);
        $this->assertStringContainsString('width: 100%;', $css);
        $this->assertStringContainsString('@media (min-width: 640px)', $css);
    }

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
