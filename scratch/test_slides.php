<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$dsm = new App\Livewire\DataShowMode();
$dsm->selectLog(10);

echo "Slide count: " . count($dsm->content) . "\n";
foreach ($dsm->content as $i => $s) {
    echo "Slide $i: type={$s['type']}, title={$s['title']}\n";
    if (isset($s['description'])) {
        echo "   Desc: " . substr(strip_tags($s['description']), 0, 50) . "...\n";
    }
    if (isset($s['register'])) {
        echo "   Register: " . substr(strip_tags($s['register']), 0, 80) . "...\n";
    }
}

