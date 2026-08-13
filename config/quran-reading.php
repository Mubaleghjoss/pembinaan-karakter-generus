<?php

return [
    /*
    | Scanner can be switched off independently when camera/OCR maintenance is
    | needed. Manual entries, verification, reports, and sheet generation do
    | not depend on this flag.
    */
    'scan_enabled' => (bool) env('QURAN_READING_SCAN_ENABLED', false),
    'ocr_enabled' => (bool) env('QURAN_READING_OCR_ENABLED', true),
    'max_upload_kilobytes' => 8192,
    'max_image_dimension' => 8000,
];
