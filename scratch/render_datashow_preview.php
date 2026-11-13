<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Teacher;
use App\Livewire\DataShowMode;
use Livewire\Livewire;
use Spatie\Browsershot\Browsershot;

app()->setLocale('ar');
$user = User::first();
auth()->login($user);

$test = Livewire::test(DataShowMode::class)
    ->call('selectLog', 10)
    ->set('slideIndex', 5);

$renderedHtml = $test->html();

// Wrap in full HTML page with Tailwind CDN for accurate visual test
$fullHtml = <<<HTML
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>DataShow Preview</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; margin: 0; padding: 0; background-color: #020617; }
    </style>
</head>
<body class="bg-slate-950 text-white theme-cinema">
    {$renderedHtml}
</body>
</html>
HTML;

$htmlPath = __DIR__ . '/datashow_slide5.html';
file_put_contents($htmlPath, $fullHtml);
echo "Saved HTML to $htmlPath\n";

$outputPath = 'C:/Users/FIddler/.gemini/antigravity/brain/2c6a395c-f8e2-4594-be99-01de51ecf1c0/preview_datashow_slide5.png';

Browsershot::html($fullHtml)
    ->windowSize(1400, 900)
    ->deviceScaleFactor(1.5)
    ->waitUntilNetworkIdle()
    ->save($outputPath);

echo "Screenshot saved to $outputPath\n";
