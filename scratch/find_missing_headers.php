<?php
$files = glob('resources/views/print/*.blade.php');
$missing = [];
$has = [];

foreach ($files as $f) {
    $content = file_get_contents($f);
    if (strpos($content, 'الجمهورية الجزائرية') === false) {
        $missing[] = basename($f);
    } else {
        $has[] = basename($f);
    }
}

echo "HAS header (" . count($has) . "):\n";
foreach ($has as $h) echo "  + $h\n";

echo "\nMISSING header (" . count($missing) . "):\n";
foreach ($missing as $m) echo "  - $m\n";

