<?php

return [
    /*
     * The only SMS driver that ships with this project writes to a log file.
     * There is no external provider call, no credential and no bill.
     */
    'driver' => env('SMS_DRIVER', 'log'),

    /*
     * This file contains the recipient phone number and the message body.
     * It is a development driver: replace SmsChannel with a provider before
     * production, or keep the path on a private, rotated volume.
     */
    'log_path' => env('SMS_LOG_PATH') ?: storage_path('logs/sms.log'),
];
