-- Pendamping migrasi Laravel 2026_08_15_180000_make_quran_reading_pages_nullable.php
ALTER TABLE `quran_reading_entries`
    MODIFY `page_start` SMALLINT UNSIGNED NULL,
    MODIFY `page_end` SMALLINT UNSIGNED NULL;
