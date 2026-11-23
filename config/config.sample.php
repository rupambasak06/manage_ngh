<?php
/**
 * Sample Configuration File
 *
 * Copy this file to config/config.php and fill in your cPanel MySQL details,
 * OR run install.php once in your browser to generate config/config.php automatically.
 */

declare(strict_types=1);

return [
    'db' => [
        'host'    => 'localhost',
        'port'    => '3306',
        'name'    => 'cpaneluser_hotel_checklist',
        'user'    => 'cpaneluser_dbuser',
        'pass'    => 'CHANGE_ME_STRONG_PASSWORD',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name'            => 'হোটেল রুম ডেইলি চেকলিস্ট',
        'timezone'        => 'Asia/Kolkata', // Use 'Asia/Dhaka' for Bangladesh if needed
        'base_url'        => '',             // Leave empty for auto-detection, or set e.g. 'https://checklist.example.com'
        'poll_interval'   => 10000,          // Live refresh interval in milliseconds (10 seconds)
        'session_name'    => 'HCL_SESSID',
        'session_lifetime'=> 43200,          // 12 hours in seconds
        'debug'           => false,
    ],
];
