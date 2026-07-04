<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes & Scheduling
|--------------------------------------------------------------------------
|
| Scheduled tasks untuk aplikasi SiPadu
|
*/

// ── Daily Health Check: Fix missing validations ──────────────────────────────────
// Run every hour to catch any documents that were processed without validation
Schedule::command('ocr:reprocess-failed --fix-missing')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/ocr-fix-scheduler.log'));
