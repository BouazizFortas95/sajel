<?php

use App\Models\PedagogicalMemo;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$memo = PedagogicalMemo::find(2);
if (! $memo) {
    echo "Memo 2 not found\n";
    exit;
}

echo 'ID: '.$memo->id."\n";
echo 'Title: '.$memo->title."\n";
echo 'Topic: '.$memo->topic."\n";
echo 'Phase: '.$memo->phase."\n";
echo 'Competence: '.$memo->competence."\n";
echo 'Starting situation length: '.strlen($memo->starting_situation)."\n";
echo 'Resources length: '.strlen($memo->resources)."\n";
echo 'Activities count: '.(is_array($memo->activities) ? count($memo->activities) : 0)."\n";
if (is_array($memo->activities)) {
    foreach ($memo->activities as $i => $act) {
        echo "Activity {$i} name: ".($act['name'] ?? '').' | duration: '.($act['duration'] ?? '')."\n";
        echo '  content len: '.strlen($act['content'] ?? '')."\n";
        echo '  written_trace len: '.strlen($act['written_trace'] ?? '')."\n";
        echo '  teacher_role len: '.strlen($act['teacher_role'] ?? '')."\n";
    }
}
