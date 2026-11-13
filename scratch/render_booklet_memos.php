<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
app()->setLocale('ar');

use App\Models\User;
use App\Services\PedagogicalMemoPrintService;
use App\Support\BrowsershotFactory;

$teacher = User::find(42);
$printService = app(PedagogicalMemoPrintService::class);
$html = $printService->bookletPdfHtml(
    teacher: $teacher,
    subjectId: null,
    levelId: null,
    memoIds: null,
);

$artifactDir = 'C:/Users/FIddler/.gemini/antigravity/brain/2c6a395c-f8e2-4594-be99-01de51ecf1c0';
$memo1PngPath = $artifactDir . '/preview_booklet_memo1.png';
$memo2PngPath = $artifactDir . '/preview_booklet_memo2.png';

echo "Rendering memo 1 PNG screenshot...\n";
BrowsershotFactory::html($html)
    ->windowSize(1200, 1697)
    ->deviceScaleFactor(1.5)
    ->showBackground()
    ->select('.sheet:nth-of-type(3)')
    ->save($memo1PngPath);

echo "Memo 1 PNG saved to: $memo1PngPath\n";

echo "Rendering memo 2 PNG screenshot...\n";
BrowsershotFactory::html($html)
    ->windowSize(1200, 1697)
    ->deviceScaleFactor(1.5)
    ->showBackground()
    ->select('.sheet:nth-of-type(4)')
    ->save($memo2PngPath);

echo "Memo 2 PNG saved to: $memo2PngPath\n";

echo "Done!\n";

