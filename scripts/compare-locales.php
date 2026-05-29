<?php

function flattenKeys(string $file): array
{
    $data = include $file;
    $out = [];
    $walk = function (array $arr, string $prefix) use (&$out, &$walk): void {
        foreach ($arr as $key => $value) {
            $full = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
            if (is_array($value)) {
                $walk($value, $full);
            } else {
                $out[] = $full;
            }
        }
    };
    $walk($data, '');

    return $out;
}

$locales = ['sq', 'de', 'sr', 'fr'];
$enFiles = glob(__DIR__.'/../lang/en/*.php');

foreach ($locales as $locale) {
    echo "\n=== {$locale} ===\n";
    $totalMissing = 0;
    foreach ($enFiles as $enFile) {
        $base = basename($enFile);
        $target = __DIR__."/../lang/{$locale}/{$base}";
        if (! file_exists($target)) {
            echo "MISSING FILE: {$base}\n";
            $totalMissing += 999;
            continue;
        }
        $missing = array_diff(flattenKeys($enFile), flattenKeys($target));
        if ($missing !== []) {
            echo "{$base}: ".count($missing)." missing\n";
            $totalMissing += count($missing);
        }
    }
    echo "Total issues: {$totalMissing}\n";
}
