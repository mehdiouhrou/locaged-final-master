<?php
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

$perms = ['view any role', 'create role', 'update role', 'delete role'];
foreach ($perms as $p) {
    Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
    echo 'Permission OK: ' . $p . PHP_EOL;
}

foreach (['IT Admin', 'Directrice Générale'] as $rn) {
    $role = Role::where('name', $rn)->first();
    if ($role) {
        $role->givePermissionTo($perms);
        echo 'Role OK: ' . $rn . PHP_EOL;
    } else {
        echo 'INTROUVABLE: ' . $rn . PHP_EOL;
    }
}

app()['cache']->forget('spatie.permission.cache');
echo 'Cache vide' . PHP_EOL;
