<?php

/**
 * Generates the PWA app icons (and favicon) for Play Snooker using GD.
 * Run with: php scripts/generate-pwa-icons.php
 *
 * Produces a simple, brand-consistent icon (dark baize green background,
 * gold ring, gold "8-ball" style center) at the sizes required by
 * manifest.json, without depending on any external asset pipeline.
 */
$sizes = [192, 512];
$outputDir = __DIR__.'/../public/icons';

if (! is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

foreach ($sizes as $size) {
    $image = imagecreatetruecolor($size, $size);
    imagesavealpha($image, true);

    $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
    imagefill($image, 0, 0, $transparent);

    $baizeDark = imagecolorallocate($image, 10, 30, 43);
    $baizeMid = imagecolorallocate($image, 15, 74, 47);
    $gold = imagecolorallocate($image, 227, 176, 43);
    $white = imagecolorallocate($image, 244, 244, 244);
    $black = imagecolorallocate($image, 21, 21, 21);

    // Rounded-square background.
    $radius = (int) ($size * 0.18);
    imagefilledrectangle($image, $radius, 0, $size - $radius, $size, $baizeDark);
    imagefilledrectangle($image, 0, $radius, $size, $size - $radius, $baizeDark);
    imagefilledellipse($image, $radius, $radius, $radius * 2, $radius * 2, $baizeDark);
    imagefilledellipse($image, $size - $radius, $radius, $radius * 2, $radius * 2, $baizeDark);
    imagefilledellipse($image, $radius, $size - $radius, $radius * 2, $radius * 2, $baizeDark);
    imagefilledellipse($image, $size - $radius, $size - $radius, $radius * 2, $radius * 2, $baizeDark);

    // Subtle felt gradient ring.
    imagefilledellipse($image, (int) ($size / 2), (int) ($size / 2), (int) ($size * 0.92), (int) ($size * 0.92), $baizeMid);

    // Gold "8-ball" center.
    $ballRadius = (int) ($size * 0.34);
    imagefilledellipse($image, (int) ($size / 2), (int) ($size / 2), $ballRadius, $ballRadius, $black);
    imagefilledellipse($image, (int) ($size / 2), (int) ($size / 2), (int) ($ballRadius * 0.55), (int) ($ballRadius * 0.55), $white);

    // Gold ring accent.
    imagesetthickness($image, max(2, (int) ($size * 0.015)));
    imageellipse($image, (int) ($size / 2), (int) ($size / 2), (int) ($size * 0.7), (int) ($size * 0.7), $gold);

    imagepng($image, "{$outputDir}/icon-{$size}.png");
    imagedestroy($image);
}

// Favicon (32x32) reusing the same design.
$favicon = imagecreatetruecolor(32, 32);
imagesavealpha($favicon, true);
$transparent = imagecolorallocatealpha($favicon, 0, 0, 0, 127);
imagefill($favicon, 0, 0, $transparent);
$baizeDark = imagecolorallocate($favicon, 10, 30, 43);
$gold = imagecolorallocate($favicon, 227, 176, 43);
$black = imagecolorallocate($favicon, 21, 21, 21);
$white = imagecolorallocate($favicon, 244, 244, 244);
imagefilledellipse($favicon, 16, 16, 30, 30, $baizeDark);
imagefilledellipse($favicon, 16, 16, 20, 20, $black);
imagefilledellipse($favicon, 16, 16, 10, 10, $white);
imageellipse($favicon, 16, 16, 26, 26, $gold);
imagepng($favicon, __DIR__.'/../public/favicon.ico');
imagedestroy($favicon);

echo "Generated PWA icons in {$outputDir} and public/favicon.ico\n";
