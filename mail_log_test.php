<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

Illuminate\Support\Facades\Mail::raw('Local test email from MTCGS-EMS', function ($message) {
    $message->to('hinasora219@gmail.com')->subject('Local log mail test');
});

echo "MAIL_LOGGED\n";
