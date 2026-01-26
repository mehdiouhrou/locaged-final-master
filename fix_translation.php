<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Fix the HTML entity encoding in the database
$updated = DB::table('ui_translations')
    ->where('key', 'auth.ui.we_couldnt_process')
    ->update([
        'en_text' => "We couldn't process your request",
        'fr_text' => "Nous n'avons pas pu traiter votre demande",
        'ar_text' => "لم نتمكن من معالجة طلبك",
    ]);

if ($updated) {
    echo "SUCCESS: Translation updated successfully!\n";
    echo "The HTML entities have been replaced with proper apostrophes.\n";
} else {
    // Try to insert if it doesn't exist
    $exists = DB::table('ui_translations')
        ->where('key', 'auth.ui.we_couldnt_process')
        ->exists();
    
    if (!$exists) {
        DB::table('ui_translations')->insert([
            'key' => 'auth.ui.we_couldnt_process',
            'en_text' => "We couldn't process your request",
            'fr_text' => "Nous n'avons pas pu traiter votre demande",
            'ar_text' => "لم نتمكن من معالجة طلبك",
        ]);
        echo "SUCCESS: Translation inserted successfully!\n";
    } else {
        echo "No changes needed - translation already correct.\n";
    }
}

// Verify the fix
$row = DB::table('ui_translations')
    ->where('key', 'auth.ui.we_couldnt_process')
    ->first();

echo "\nCurrent database values:\n";
echo "EN: {$row->en_text}\n";
echo "FR: {$row->fr_text}\n";
echo "AR: {$row->ar_text}\n";
