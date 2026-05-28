<?php

if (! extension_loaded('gd')) {
    fwrite(STDERR, "PHP GD extension is required.\n");
    exit(1);
}

$target = dirname(__DIR__).'/public/og/ziifra-share.png';
$im = imagecreatetruecolor(1200, 630);
$bg = imagecolorallocate($im, 246, 245, 242);
imagefill($im, 0, 0, $bg);

$bars = [
    [0, 14, 5, 12, [132, 204, 22]],
    [76, 54, 40, 136, [45, 212, 191]],
    [152, 8, 40, 192, [56, 189, 248]],
    [228, 54, 40, 136, [99, 102, 241]],
    [304, 76, 40, 96, [37, 99, 235]],
];

foreach ($bars as $bar) {
    $color = imagecolorallocate($im, $bar[4][0], $bar[4][1], $bar[4][2]);
    imagefilledrectangle(
        $im,
        120 + $bar[0],
        210 + $bar[1],
        120 + $bar[0] + $bar[2],
        210 + $bar[1] + $bar[3],
        $color,
    );
}

$ink = imagecolorallocate($im, 15, 23, 42);
imagestring($im, 5, 120, 360, 'ZIIFRA', $ink);
imagestring($im, 3, 122, 410, 'HR management for teams in Kosovo', imagecolorallocate($im, 100, 116, 139));

imagepng($im, $target);
imagedestroy($im);

echo "Wrote {$target}\n";
