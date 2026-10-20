<?php

return [
    /*
     * The only SMS driver that ships with this project writes to a log file.
     * There is no external provider call, no credential and no bill.
     */
    'driver' => env('SMS_DRIVER', 'log'),

    'log_path' => env('SMS_LOG_PATH') ?: storage_path('logs/sms.log'),
];
