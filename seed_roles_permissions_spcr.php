<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
function log_ok(string $m): void { echo "[OK]  {$m}\n"; flush(); }
function log_msg(string $m): void { echo "[" . date("H:i:s") . "] {$m}\n"; flush(); }
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
$all = ["upload document","approve document","refuse document","delete document","view any document","view department document","view service document","view own document","create location","edit location","delete location","create user","edit user","delete user","view users list","manage loans","request loan","view loan history","view document history","view audit","view destruction certificates","destroy expired document","create category","edit category","delete category","manage shared services"];
foreach($all as $p) Permission::firstOrCreate(["name"=>$p,"guard_name"=>"web"]);
log_ok(count($all)." permissions OK");
$matrix = [
"master" => $all,
"Directrice Générale" => ["upload document","approve document","refuse document","view any document","destroy expired document","create location","edit location","delete location","manage loans","request loan","view loan history","view document history","view audit","view destruction certificates","create category","edit category"],
"Assistante de Direction" => ["upload document","approve document","refuse document","view any document","create location","edit location","view loan history","view document history","request loan","view destruction certificates"],
"Chef de Pôle" => ["upload document","approve document","refuse document","view department document","destroy expired document","create location","edit location","delete location","view document history","view loan history","request loan","view destruction certificates"],
"Chef de Département" => ["upload document","view service document","view own document","request loan"],
"IT Admin" => ["upload document","approve document","refuse document","view any document","view audit","create user","edit user","delete user","view users list","manage loans","request loan","view loan history","view document history","view destruction certificates","manage shared services"],
"Utilisateur" => ["upload document","view service document","view own document","request loan"],
"Chargée de dépôt" => ["upload document","view service document","view own document","create location","edit location","manage loans","view loan history","request loan","view destruction certificates"],
];
foreach($matrix as $name => $perms) {
  $role = Role::firstOrCreate(["name"=>$name,"guard_name"=>"web"]);
  $role->syncPermissions($perms);
  log_ok("Role [{$name}] -> ".count($perms)." permissions");
}
log_msg("DONE — ".Role::count()." roles en base");
