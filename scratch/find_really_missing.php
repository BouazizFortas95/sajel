<?php
$files = glob('resources/views/print/*.blade.php');
$reallyMissing = [];

foreach ($files as $f) {
    $content = file_get_contents($f);
    $hasDirect = strpos($content, 'الجمهورية الجزائرية') !== false;
    $hasInclude = strpos($content, 'document-head') !== false;

    if (!$hasDirect && !$hasInclude) {
        $reallyMissing[] = basename($f);
    }
}

echo "Really missing Republic header (" . count($reallyMissing) . "):\n";
foreach ($reallyMissing as $m) echo "  - $m\n";

