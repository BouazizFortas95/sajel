<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$dl = App\Models\DailyLog::where('title', 'like', '%ويندوز%')
    ->orWhere('title', 'like', '%نظام التشغيل%')
    ->orWhere('actual_content', 'like', '%ويندوز%')
    ->first();

if ($dl) {
    echo "ID: " . $dl->id . "\n";
    echo "Title: " . $dl->title . "\n";
    echo "Content Data: " . json_encode($dl->content_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    echo "Actual content: " . $dl->actual_content . "\n";
} else {
    echo "No matching daily log found\n";
    $latest = App\Models\DailyLog::latest()->take(3)->get();
    foreach ($latest as $l) {
        echo "Log ID: {$l->id}, Title: {$l->title}\n";
    }
}

