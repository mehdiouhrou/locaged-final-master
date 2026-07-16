<?php
require '/var/www/locaged-v2/vendor/autoload.php';
$app = require '/var/www/locaged-v2/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Profile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

$mdp = Hash::make('Test@1234');

echo "=== PROFILS ===\n";

$profil_ep = Profile::firstOrCreate(
    ['name' => 'TEST - Technique Eau Potable'],
    ['description' => 'Categories EP', 'created_by' => 1]
);
$profil_ep->categories()->sync([2, 3, 4]);
echo "Profil EP OK\n";

$profil_rh = Profile::firstOrCreate(
    ['name' => 'TEST - RH Administratif'],
    ['description' => 'Categories RH', 'created_by' => 1]
);
$profil_rh->categories()->sync([13, 14, 15]);
echo "Profil RH OK\n";

echo "=== CATEGORY_SERVICE (Assainissement) ===\n";
foreach ([[5,5],[6,5],[7,5]] as $lien) {
    DB::table('category_service')->updateOrInsert(
        ['category_id' => $lien[0], 'service_id' => $lien[1]],
        ['category_id' => $lien[0], 'service_id' => $lien[1]]
    );
}
echo "OK\n";

echo "=== CATEGORY_ROLE (Budgets) ===\n";
$role_user_id = DB::table('roles')->where('name', 'user')->value('id');
if ($role_user_id) {
    foreach ([8, 9, 10] as $cat_id) {
        DB::table('category_role')->updateOrInsert(
            ['category_id' => $cat_id, 'role_id' => $role_user_id],
            ['category_id' => $cat_id, 'role_id' => $role_user_id]
        );
    }
    echo "OK\n";
} else {
    echo "ERREUR: role user introuvable\n";
}

echo "=== UTILISATEURS ===\n";

$liste = [
    ['master@test.locaged',     'Master TEST',           'master',                   null, null, null, null],
    ['dg@test.locaged',         'Directrice DG TEST',    'Directrice du SPCR',       3,    null, null, null],
    ['assistante@test.locaged', 'Assistante TEST',        'Assistante de Direction',  3,    null, null, null],
    ['depot@test.locaged',      'Chargee depot TEST',    'Chargee de depot',          5,    10,   18,   null],
    ['pole.tech@test.locaged',  'Chef Pole Tech TEST',   'Chef de Pole',              4,    null, null, null],
    ['pole.admin@test.locaged', 'Chef Pole Admin TEST',  'Chef de Pole',              5,    null, null, null],
    ['user.ep@test.locaged',    'User EP TEST',          'user',                      4,    3,    3,    $profil_ep->id],
    ['user.ass@test.locaged',   'User Ass TEST',         'user',                      4,    4,    5,    null],
    ['user.budgets@test.locaged','User Budgets TEST',    'user',                      5,    7,    11,   null],
    ['user.rh@test.locaged',    'User RH TEST',         'user',                       5,    10,   17,   $profil_rh->id],
];

foreach ($liste as [$email, $nom, $role_name, $dept, $subdept, $service, $profil_id]) {
    $user = User::updateOrCreate(
        ['email' => $email],
        ['name' => $nom, 'username' => explode('@', $email)[0], 'full_name' => $nom, 'password' => $mdp]
    );

    $role = Role::where('name', $role_name)->first();
    if ($role) {
        $user->syncRoles([$role->name]);
    } else {
        echo "ROLE INTROUVABLE: $role_name\n";
        continue;
    }

    if ($dept) {
        DB::table('department_user')->updateOrInsert(
            ['user_id' => $user->id, 'department_id' => $dept],
            ['user_id' => $user->id, 'department_id' => $dept]
        );
    }
    if ($subdept && DB::getSchemaBuilder()->hasTable('sub_department_user')) {
        DB::table('sub_department_user')->updateOrInsert(
            ['user_id' => $user->id, 'sub_department_id' => $subdept],
            ['user_id' => $user->id, 'sub_department_id' => $subdept]
        );
    }
    if ($service && DB::getSchemaBuilder()->hasTable('service_user')) {
        DB::table('service_user')->updateOrInsert(
            ['user_id' => $user->id, 'service_id' => $service],
            ['user_id' => $user->id, 'service_id' => $service]
        );
    }
    if ($profil_id && DB::getSchemaBuilder()->hasTable('profile_user')) {
        DB::table('profile_user')->updateOrInsert(
            ['user_id' => $user->id, 'profile_id' => $profil_id],
            ['user_id' => $user->id, 'profile_id' => $profil_id]
        );
    }

    echo "OK [{$user->id}] $email | $role_name\n";
}

echo "\nTotal users: " . User::count() . "\n";
echo "Mot de passe: Test@1234\n";
