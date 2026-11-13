<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (App\Models\DailyLog::all() as $l) {
    $d = new App\Livewire\DataShowMode();
    $d->selectLog($l->id);
    echo "Log {$l->id}: date=" . $l->date?->format('d/m/Y') . " title=" . $l->title . " slides=" . count($d->content) . "\n";
    foreach ($d->content as $idx => $s) {
        echo "   Slide {$idx}: type={$s['type']} title={$s['title']}\n";
    }
}

