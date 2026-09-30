<?php
$f = base_path('resources/views/documents/create.blade.php');
$c = file_get_contents($f);
$c = str_replace(
    "@if (request('mode') === 'archive')",
    "@if (request('mode') === 'archive' || !App\\Support\\Branding::isCollaborativeModuleEnabled())",
    $c
);
file_put_contents($f, $c);
echo "OK\n";
echo substr($c, strpos($c, '@if (request'), 80) . "\n";
