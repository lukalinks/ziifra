<?php

function flatten(string $file): array
{
    $data = require $file;
    $out = [];
    $walk = function (array $arr, string $prefix) use (&$out, &$walk): void {
        foreach ($arr as $key => $value) {
            $full = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
            if (is_array($value)) {
                $walk($value, $full);
            } else {
                $out[$full] = (string) $value;
            }
        }
    };
    $walk($data, '');

    return $out;
}

$locale = $argv[1] ?? 'sq';
$enDir = dirname(__DIR__).'/lang/en';
$targetDir = dirname(__DIR__)."/lang/{$locale}";
$total = 0;

foreach (glob("{$enDir}/*.php") as $enFile) {
    $base = basename($enFile);
    $targetFile = "{$targetDir}/{$base}";
    if (! file_exists($targetFile)) {
        continue;
    }
    $en = flatten($enFile);
    $target = flatten($targetFile);
    $same = 0;
    foreach ($en as $key => $enValue) {
        if (($target[$key] ?? null) === $enValue) {
            $same++;
        }
    }
    if ($same > 0) {
        echo "{$base}: {$same} still English\n";
        $total += $same;
    }
}

echo "Total still English in {$locale}: {$total}\n";
