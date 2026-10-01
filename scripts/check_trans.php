<?php
$fr = json_decode(file_get_contents('/var/www/locaged-v2/lang/fr.json'), true);
$used = file('/tmp/used_keys.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$missing = [];
foreach ($used as $key) {
    if (!array_key_exists($key, $fr)) {
        $missing[] = $key;
    }
}
sort($missing);
echo count($missing) . ' clés manquantes :' . PHP_EOL;
foreach ($missing as $k) echo '  ' . $k . PHP_EOL;
