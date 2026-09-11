<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    Artisan::call('services', [
        '--source' => storage_path('app/assets'),
        '--sync' => true,
        '--publish' => true,
    ]);
})->name('sync-service-source-images')->hourly()->withoutOverlapping();
