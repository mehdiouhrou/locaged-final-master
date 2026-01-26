<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$row = DB::table('ui_translations')
    ->where('key', 'auth.ui.we_couldnt_process')
    ->first();

if ($row) {
    echo "Key: {$row->key}\n";
    echo "EN: {$row->en_text}\n";
    echo "FR: {$row->fr_text}\n";
    echo "AR: {$row->ar_text}\n";
} else {
    echo "Translation not found in database\n";
}
