<?php

use App\Models\PedagogicalMemo;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$memo = PedagogicalMemo::find(2);
echo "=== Activity 0 written_trace snippet ===\n";
echo mb_substr($memo->activities[0]['written_trace'], 0, 800)."\n...\n";

echo "=== Activity 1 written_trace snippet ===\n";
echo mb_substr($memo->activities[1]['written_trace'], 0, 800)."\n...\n";
