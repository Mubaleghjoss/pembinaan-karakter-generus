<?php

return [
    'project_id' => env('FCM_PROJECT_ID'),
    'credentials' => env('FCM_CREDENTIALS'),
    'ca_bundle' => env('FCM_CA_BUNDLE'),
    'enabled' => (bool) env('FCM_ENABLED', false),
];
