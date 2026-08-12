<?php

return [
    /*
    | Scanner stays disabled in production until the structured-sheet pilot
    | has been completed. Manual entries, verification, reports, and sheet
    | generation do not depend on this flag.
    */
    'scan_enabled' => (bool) env('QURAN_READING_SCAN_ENABLED', false),
];
