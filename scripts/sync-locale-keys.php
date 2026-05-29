<?php

/**
 * Merge missing keys from lang/en into target locales.
 * Existing translations in the target locale are preserved.
 *
 * Usage: php scripts/sync-locale-keys.php sq [de] [sr]
 */

$targets = [];
$skip = [];
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--skip=')) {
        $skip = array_merge($skip, explode(',', substr($arg, 7)));
    } else {
        $targets[] = $arg;
    }
}
if ($targets === []) {
    $targets = ['sq'];
}

$enDir = dirname(__DIR__).'/lang/en';

foreach ($targets as $locale) {
    $targetDir = dirname(__DIR__)."/lang/{$locale}";
    if (! is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    foreach (glob("{$enDir}/*.php") as $enFile) {
        $base = basename($enFile);
        if (in_array($base, $skip, true)) {
            continue;
        }
        $targetFile = "{$targetDir}/{$base}";
        $en = require $enFile;
        $target = file_exists($targetFile) ? require $targetFile : [];
        $merged = array_replace_recursive($en, $target);
        $content = "<?php\n\nreturn ".var_export($merged, true).";\n";
        file_put_contents($targetFile, $content);
        echo "{$locale}/{$base}\n";
    }
}

echo "Done.\n";
