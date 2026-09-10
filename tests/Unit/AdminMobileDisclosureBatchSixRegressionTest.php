<?php

namespace Tests\Unit;

use Tests\TestCase;

class AdminMobileDisclosureBatchSixRegressionTest extends TestCase
{
    public function test_batch_six_record_cards_keep_secondary_content_in_mobile_disclosures(): void
    {
        $files = [
            'resources/views/admin/boss/index.blade.php',
            'resources/views/admin/chat-groups/index.blade.php',
            'resources/views/admin/generus-registration/index.blade.php',
            'resources/views/admin/karakter-luhur/index.blade.php',
            'resources/views/berita/index.blade.php',
            'resources/views/presentations/index.blade.php',
        ];

        foreach ($files as $path) {
            $view = file_get_contents(base_path($path));

            $this->assertNotFalse($view, "Unable to read {$path}");
            $this->assertStringContainsString('<details', $view, "{$path} needs a native mobile disclosure");
            $this->assertStringContainsString('md:hidden', $view, "{$path} must scope the disclosure to mobile");
        }
    }

    public function test_mobile_record_summaries_keep_identity_status_or_date_visible(): void
    {
        $boss = file_get_contents(base_path('resources/views/admin/boss/index.blade.php'));
        $registration = file_get_contents(base_path('resources/views/admin/generus-registration/index.blade.php'));
        $news = file_get_contents(base_path('resources/views/berita/index.blade.php'));
        $presentations = file_get_contents(base_path('resources/views/presentations/index.blade.php'));

        $this->assertStringContainsString('{{ $b->nama }}', $boss);
        $this->assertStringContainsString('TTD {{ optional($row', $registration);
        $this->assertStringContainsString('$item->published_at ? $item->published_at->format(\'d M Y\') : \'Draft\'', $news);
        $this->assertStringContainsString('{{ $presentation->is_published ? \'Publik\' : \'Draft\' }}', $presentations);
    }

    public function test_existing_forms_and_workspace_pages_are_not_wrapped_in_new_mobile_disclosures(): void
    {
        $materials = file_get_contents(base_path('resources/views/teacher-planning/materials.blade.php'));
        $planning = file_get_contents(base_path('resources/views/teacher-planning/index.blade.php'));
        $meetingNotes = file_get_contents(base_path('resources/views/catatan-rapat/index.blade.php'));
        $quran = file_get_contents(base_path('resources/views/quran-reading/operational-index.blade.php'));

        $this->assertStringContainsString('<details class="p-4 sm:p-5">', $materials);
        $this->assertStringNotContainsString('md:hidden', $planning);
        $this->assertStringNotContainsString('md:hidden', $meetingNotes);
        $this->assertStringNotContainsString('md:hidden', $quran);
    }
}
