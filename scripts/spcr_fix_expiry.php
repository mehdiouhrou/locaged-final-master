<?php
/**
 * SPCR — Fix expiry : "5 à 10 ans" → 10 ans | RH SDSPD → illimité
 */
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== FIX EXPIRY SPÉCIAUX ===\n\n";

// Fix 1 : "5 à 10 ans" → 10 ans (SR-Dossier N°11)
$n1 = DB::table('categories')
    ->where('retention_period', '5 à 10 ans')
    ->update(['expiry_value' => 10, 'expiry_unit' => 'years', 'updated_at' => now()]);
echo "  [fix1] '5 à 10 ans' → 10 ans : $n1 catégorie(s)\n";

// Fix 2 : RH SDSPD → illimité (pas de date d'expiration sur la webapp)
$n2 = DB::table('categories')
    ->where('name', 'like', '%SDSPD%')
    ->update(['expiry_value' => null, 'expiry_unit' => 'permanent', 'updated_at' => now()]);
echo "  [fix2] Dossier RH SDSPD → illimité : $n2 catégorie(s)\n";

// Vérification
echo "\n=== VÉRIFICATION ===\n";
$cats = DB::table('categories')
    ->whereIn('id', DB::table('categories')->where('name','like','%SDSPD%')->orWhere('retention_period','5 à 10 ans')->pluck('id'))
    ->get(['id','name','retention_period','expiry_value','expiry_unit']);
foreach ($cats as $c) {
    echo "  ID:{$c->id} | expiry:{$c->expiry_value} {$c->expiry_unit} | {$c->retention_period}\n";
    echo "          → " . substr($c->name, 0, 60) . "\n";
}

echo "\n✅ Fix appliqué !\n";
