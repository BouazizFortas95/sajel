<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$teachers = App\Models\User::where('role', 'teacher')->get();
foreach ($teachers as $t) {
    echo "ID: {$t->id} | Name: {$t->name} | Email: {$t->email} | Primary: " . ($t->primarySubject?->name ?? 'None') . " | Secondary: " . ($t->secondarySubject?->name ?? 'None') . "\n";
    $scheds = App\Models\Schedule::where('user_id', $t->id)->with('subject', 'classroom.level')->get();
    echo "  Schedules (" . $scheds->count() . "):\n";
    foreach ($scheds as $s) {
        echo "    - Subj: " . ($s->subject?->name ?? 'None') . " | Class: " . ($s->classroom?->name ?? 'None') . " | Level: " . ($s->classroom?->level?->name ?? 'None') . "\n";
    }
}

