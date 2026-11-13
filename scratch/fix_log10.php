<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$l = App\Models\DailyLog::find(10);
if ($l) {
    $l->title = str_replace('نظلم', 'نظم', $l->title);
    $l->actual_content = str_replace('نظلم', 'نظم', $l->actual_content);
    $l->save();
    echo "Fixed log 10: " . $l->title . "\n";
}

