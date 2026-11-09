<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// تذكير أسبوعي آلي بالمخططات والتدرجات البيداغوجية
Schedule::command('pedagogical:send-reminders')
    ->weeklyOn(1, '08:00')
    ->withoutOverlapping();
