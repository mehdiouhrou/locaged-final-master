<?php
/**
 * Ajoute les permissions nécessaires pour la page gestion des rôles
 * et les assigne aux rôles autorisés.
 *
 * Usage : php seed_roles_permissions.php
 */

require __DIR__ . '/../bootstrap/app.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

$permissionsToCreate = [
    'manage roles',
    'view roles page',
];

echo "=== Création des permissions ===" . PHP_EOL;
foreach ($permissionsToCreate as $permName) {
    $perm = Permission::firstOrCreate([
        'name'       => $permName,
        'guard_name' => 'web',
    ]);
    echo ($perm->wasRecentlyCreated ? 'CRÉÉ' : 'EXISTE') . " : {$permName}" . PHP_EOL;
}

echo PHP_EOL . "=== Assignation aux rôles ===" . PHP_EOL;
$rolesWithAccess = ['master', 'IT Admin', 'Directrice Générale'];

foreach ($rolesWithAccess as $roleName) {
    $role = Role::where('name', $roleName)->first();
    if ($role) {
        $role->givePermissionTo($permissionsToCreate);
        echo "OK : {$roleName}" . PHP_EOL;
    } else {
        echo "INTROUVABLE : {$roleName}" . PHP_EOL;
    }
}

// Vider le cache Spatie
app()['cache']->forget('spatie.permission.cache');
echo PHP_EOL . "Cache Spatie vidé." . PHP_EOL;
echo "Terminé." . PHP_EOL;
