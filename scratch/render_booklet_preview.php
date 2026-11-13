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
if (! $teacher) {
    echo "Teacher 42 not found\n";
    exit(1);
}

$printService = app(PedagogicalMemoPrintService::class);
$html = $printService->bookletPdfHtml(
    teacher: $teacher,
    subjectId: null,
    levelId: null,
    memoIds: null,
);

$artifactDir = 'C:/Users/FIddler/.gemini/antigravity/brain/2c6a395c-f8e2-4594-be99-01de51ecf1c0';
$pdfPath = $artifactDir . '/preview_pedagogical_booklet.pdf';
$coverPngPath = $artifactDir . '/preview_booklet_cover.png';
$indexPngPath = $artifactDir . '/preview_booklet_index.png';

echo "Rendering booklet PDF...\n";
BrowsershotFactory::html($html)
    ->format('A4')
    ->margins(4, 5, 4, 5)
    ->showBackground()
    ->save($pdfPath);

echo "Booklet PDF saved to: $pdfPath\n";

echo "Rendering cover PNG screenshot...\n";
BrowsershotFactory::html($html)
    ->windowSize(1200, 1697)
    ->deviceScaleFactor(1.5)
    ->showBackground()
    ->select('.cover-sheet')
    ->save($coverPngPath);

echo "Cover PNG saved to: $coverPngPath\n";

echo "Rendering index PNG screenshot...\n";
BrowsershotFactory::html($html)
    ->windowSize(1200, 1697)
    ->deviceScaleFactor(1.5)
    ->showBackground()
    ->select('.sheet:nth-of-type(2)')
    ->save($indexPngPath);

echo "Index PNG saved to: $indexPngPath\n";

echo "Done!\n";
