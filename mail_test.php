<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    Illuminate\Support\Facades\Mail::raw('SMTP test from MTCGS-EMS', function ($message) {
        $message->to('hinasora219@gmail.com')->subject('SMTP test from Laravel');
    });

    echo "MAIL_SENT\n";
} catch (Throwable $e) {
    echo "MAIL_ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
