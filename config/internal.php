<?php

return [
    'test_mode' => filter_var(env('APP_INTERNAL_TEST_MODE', false), FILTER_VALIDATE_BOOLEAN),
    'test_mode_expires_at' => env('APP_INTERNAL_TEST_MODE_EXPIRES_AT'),
    'owner_email' => env('APP_OWNER_EMAIL'),
    'super_admin_email' => env('APP_SUPER_ADMIN_EMAIL', 'donvictorlive@gmail.com'),
];
