<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PedagogicalMemo;

echo "Total memos: " . PedagogicalMemo::count() . "\n";
foreach (PedagogicalMemo::all() as $m) {
    echo "  - ID {$m->id}: {$m->title} | Subj: " . ($m->subject?->name ?? 'None') . " | User: " . ($m->user?->name ?? 'None') . "\n";
}

