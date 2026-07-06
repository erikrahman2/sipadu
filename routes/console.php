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

// ── OCR Backup: Daily backup at midnight ─────────────────────────────────────────
Schedule::command('ocr:backup')
    ->dailyAt('00:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/ocr-backup-scheduler.log'));

// ── OCR Backup: Weekly backup on Sunday at 2 AM ───────────────────────────────────
// Keep one week's worth of daily backups + monthly retention
Schedule::command('ocr:backup')
    ->weeklyOn(0, '02:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/ocr-backup-weekly.log'));
