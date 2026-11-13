<?php
$pdf = file_get_contents('C:/Users/FIddler/.gemini/antigravity/brain/2c6a395c-f8e2-4594-be99-01de51ecf1c0/preview_pedagogical_booklet.pdf');
preg_match_all('/\/Type\s*\/Page\b/', $pdf, $m);
echo 'Page count: ' . count($m[0]) . PHP_EOL;

