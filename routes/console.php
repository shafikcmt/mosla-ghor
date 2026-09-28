<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Temp images from the product editor that were never saved (see App\Support\TempUpload).
// Also cleaned opportunistically on upload, so this only matters if the scheduler runs.
Artisan::command('uploads:clean-temp {--hours=24 : Remove temp uploads older than this}', function () {
    $removed = \App\Support\TempUpload::cleanup(max(1, (int) $this->option('hours')) * 3600);
    $this->info("Removed {$removed} stale temp upload(s).");
})->purpose('Delete unsaved temp product images');

\Illuminate\Support\Facades\Schedule::command('uploads:clean-temp')->daily();
