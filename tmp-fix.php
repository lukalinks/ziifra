<?php
$path = __DIR__.'/resources/views/app/settings/billing.blade.php';
$c = file_get_contents($path);
$c = str_replace('motion.div', 'motion.div', $c);
file_put_contents($path, $c);
