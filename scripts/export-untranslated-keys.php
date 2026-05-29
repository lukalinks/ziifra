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
$file = $argv[2] ?? null;
$enDir = dirname(__DIR__).'/lang/en';
$targetDir = dirname(__DIR__)."/lang/{$locale}";

$files = $file ? [basename($file)] : array_map('basename', glob("{$enDir}/*.php"));

foreach ($files as $base) {
    $enFile = "{$enDir}/{$base}";
    $targetFile = "{$targetDir}/{$base}";
    if (! file_exists($targetFile)) {
        continue;
    }
    $en = flatten($enFile);
    $target = flatten($targetFile);
    $gaps = [];
    foreach ($en as $key => $enValue) {
        if (($target[$key] ?? null) === $enValue) {
            $gaps[$key] = $enValue;
        }
    }
    if ($gaps !== []) {
        echo "=== {$base} ===\n";
        foreach ($gaps as $k => $v) {
            echo "{$k} => {$v}\n";
        }
        echo "\n";
    }
}
