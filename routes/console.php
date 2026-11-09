<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// المزامنة اليومية التلقائية للمخططات والتدرجات من دفتر النصوص
Schedule::command('pedagogical:sync-plans')
    ->dailyAt('06:00')
    ->withoutOverlapping();

// التذكير الأسبوعي الآلي بالمخططات والتقويمات المستحقة (كل يوم إثنين الساعة 08:00)
Schedule::command('pedagogical:send-reminders --auto')
    ->weeklyOn(1, '08:00')
    ->withoutOverlapping();

// فحص شهري للتدرجات والمخططات المستحقة نهاية كل شهر (يوم 25 الساعة 09:00)
Schedule::command('pedagogical:send-reminders')
    ->monthlyOn(25, '09:00')
    ->withoutOverlapping();
