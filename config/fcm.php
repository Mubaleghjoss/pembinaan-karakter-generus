<?php

return [
    'project_id' => env('FCM_PROJECT_ID'),
    'credentials' => env('FCM_CREDENTIALS'),
    'enabled' => (bool) env('FCM_ENABLED', false),
];
