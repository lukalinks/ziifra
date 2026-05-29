<?php

foreach (glob(__DIR__.'/../lang/sq/*.php') as $file) {
    $content = file_get_contents($file);
    $fixed = preg_replace('/,\s*;/', ',', $content);
    if ($fixed !== $content) {
        file_put_contents($file, $fixed);
        echo "Fixed: ".basename($file)."\n";
    }
    include $file;
}

echo "All sq files valid.\n";
