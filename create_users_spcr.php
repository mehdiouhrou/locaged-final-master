<?php
/**
 * Script création comptes utilisateurs SPCR Rabat
 * =================================================
 * À placer sur le serveur Windows : C:\create_users_spcr.php
 * Lancer avec : php C:\create_users_spcr.php
 *
 * Ce script :
 *   1. Supprime les 8 comptes de test
 *   2. Crée les 26 vrais comptes @spadr.ma
 *   3. Assigne le bon rôle LocaGed à chaque compte
 *   4. Assigne le service (cellule) correspondant
 *
 * Mapping rôles LocaGed → Spatie :
 *   Directrice Générale → Directrice du SPCR
 *   Chef de Pôle        → Chef de Pôle
 *   Chef de Département → Chef de Département
 *   Assistante de Direction → Assistante de Direction
 *   IT Admin            → IT Admin
 *   Utilisateur         → user
 *   Chargée de dépôt    → Chargée de dépôt
 */

define('LARAVEL_START', microtime(true));
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Service;
use Spatie\Permission\Models\Role;

// ============================================================
// CONFIGURATION
// ============================================================
$MOT_DE_PASSE_TEMP = 'Spcr@2026!'; // Mot de passe temporaire identique pour tous

// ============================================================
// DÉFINITION DES 26 COMPTES
// ============================================================
// Colonnes : email, nom_complet, nom_affichage, role_locaged, cellule
// Note : doublons d'email résolus (1 seul compte par email)

$COMPTES = [
    // --- Direction Générale ---
    [
        'email'    => 'imane.bey@spadr.ma',
        'name'     => 'Imane BEY',
        'full_name'=> 'Mme. Imane BEY',
        'role'     => 'Directrice du SPCR',
        'cellule'  => 'Direction Générale',
    ],
    [
        'email'    => 'bouchra.oubari@spadr.ma',
        'name'     => 'Bouchra OUBARI',
        'full_name'=> 'Mme. Bouchra OUBARI',
        'role'     => 'Assistante de Direction',
        'cellule'  => "Secrétariat et Bureau d'ordre",
    ],

    // --- Pôle Technique ---
    [
        'email'    => 'mustapha.fettach@spadr.ma',
        'name'     => 'Mustapha FETTACH',
        'full_name'=> 'M. Mustapha FETTACH',
        'role'     => 'Chef de Pôle',
        'cellule'  => 'Direction du Pôle', // Pôle Technique
        'pole'     => 'Pôle Technique',
    ],
    [
        'email'    => 'lamyaa.azzioui@spadr.ma',
        'name'     => 'Lamyaa AZZIOUI',
        'full_name'=> 'Mme. Lamyaa AZZIOUI',
        'role'     => 'Chef de Département',
        'cellule'  => 'Cellule Investissements Eau Potable',
    ],
    [
        'email'    => 'kaouthar.ennasri@spadr.ma',
        'name'     => 'Kaouthar ENNASRI',
        'full_name'=> 'Mme. Kaouthar ENNASRI',
        'role'     => 'Chef de Département',
        'cellule'  => 'Cellule Investissements Eau Potable',
    ],
    [
        'email'    => 'karim.hammani@spadr.ma',
        'name'     => 'Karim HAMMANI',
        'full_name'=> 'M. Karim HAMMANI',
        'role'     => 'user',
        'cellule'  => 'Cellule Investissements Eau Potable',
    ],
    [
        'email'    => 'sara.tammar@spadr.ma',
        'name'     => 'Sara TAMMAR',
        'full_name'=> 'Mme. Sara TAMMAR',
        'role'     => 'Chef de Département',
        'cellule'  => 'Cellule Performances Techniques Eau Potable',
    ],
    [
        'email'    => 'raif.bezzanin@spadr.ma',
        'name'     => 'Raif BEZZANIN',
        'full_name'=> 'M. Raif BEZZANIN',
        'role'     => 'Chef de Département',
        'cellule'  => 'Cellule Investissements Assainissement',
    ],
    [
        'email'    => 'oussama.boulhaz@spadr.ma',
        'name'     => 'Oussama BOULHAZ',
        'full_name'=> 'M. Oussama BOULHAZ',
        'role'     => 'user',
        'cellule'  => 'Cellule Investissements Assainissement',
    ],
    [
        'email'    => 'anass.friak@spadr.ma',
        'name'     => 'Anass FRIAKH',
        'full_name'=> 'M. Anass FRIAKH',
        'role'     => 'Chef de Département',
        'cellule'  => 'Cellule Performances Techniques Assainissement',
    ],
    [
        'email'    => 'houssam.biqiche@spadr.ma',
        'name'     => 'Houssam Eddine BIQICHE',
        'full_name'=> 'M. Houssam Eddine BIQICHE',
        'role'     => 'Chef de Département',
        'cellule'  => 'Cellule Investissements Electricité',
    ],
    [
        'email'    => 'hamza.lamdouar@spadr.ma',
        'name'     => 'Hamza LAMDOUAR',
        'full_name'=> 'M. Hamza LAMDOUAR',
        'role'     => 'Chef de Département',
        'cellule'  => 'Cellule Performances Techniques Electricité',
    ],
    // MERZGIOUI compte 1 : IT Admin
    [
        'email'    => 'administrateur@spadr.ma',
        'name'     => 'Jaoualia MERZGIOUI',
        'full_name'=> 'Mme. Jaoualia MERZGIOUI',
        'role'     => 'IT Admin',
        'cellule'  => 'Cellule Développement et Maintenance SI',
        'username' => 'administrateur',
    ],
    // MERZGIOUI compte 2 : Chef de Département (compte métier)
    [
        'email'    => 'jaoualia.elmarzgioui@spadr.ma',
        'name'     => 'Jaoualia MERZGIOUI',
        'full_name'=> 'Mme. Jaoualia MERZGIOUI (Métier)',
        'role'     => 'Chef de Département',
        'cellule'  => 'Cellule Développement et Maintenance SI',
    ],
    [
        'email'    => 'kaoutar.dadouch@spadr.ma',
        'name'     => 'Kaoutar DADOUCH',
        'full_name'=> 'Mme. Kaoutar DADOUCH',
        'role'     => 'Chef de Département',
        'cellule'  => 'Cellule Patrimoine',
    ],

    // --- Pôle Administratif ---
    [
        'email'    => 'miloud.ziat@spadr.ma',
        'name'     => 'Miloud ZIAT',
        'full_name'=> 'M. Miloud ZIAT',
        'role'     => 'Chef de Pôle',
        'cellule'  => 'Direction du Pôle', // Pôle Administratif
        'pole'     => 'Pôle Administratif',
    ],
    [
        'email'    => 'mehdi.ibrahimi@spadr.ma',
        'name'     => 'Mehdi IBRAHIMI',
        'full_name'=> 'M. Mehdi IBRAHIMI',
        'role'     => 'Chef de Département',
        'cellule'  => 'Cellule Budgets et PLT',
    ],
    // rajaa.ziat = 1 seul compte (Cellule Budgets et PLT, coordinatrice Valorisation)
    [
        'email'    => 'rajaa.ziat@spadr.ma',
        'name'     => 'Rajaa ZIAT',
        'full_name'=> 'Mme. Rajaa ZIAT',
        'role'     => 'Chef de Département',
        'cellule'  => 'Cellule Budgets et PLT',
    ],
    // amal.eladnani = 1 seul compte (Cellule Comptabilité & Finances)
    [
        'email'    => 'amal.eladnani@spadr.ma',
        'name'     => 'Amal EL ADNANI',
        'full_name'=> 'Mme. Amal EL ADNANI',
        'role'     => 'Chef de Département',
        'cellule'  => 'Cellule Comptabilité & Finances',
    ],
    [
        'email'    => 'mohamed.ouazzani@spadr.ma',
        'name'     => 'Mohamed OUAZZANI TOUHAMI',
        'full_name'=> 'M. Mohamed OUAZZANI TOUHAMI',
        'role'     => 'Chef de Département',
        'cellule'  => 'Cellule Comptabilité & Finances',
    ],
    [
        'email'    => 'jamal.sody@spadr.ma',
        'name'     => 'Jamal SODY',
        'full_name'=> 'M. Jamal SODY',
        'role'     => 'Chef de Département',
        'cellule'  => "Cellule Suivi des Hypothèses et Équilibre",
    ],
    [
        'email'    => 'aicha.elrhandouri@spadr.ma',
        'name'     => 'Aicha EL GHANDOURI',
        'full_name'=> 'Mme. Aicha EL GHANDOURI',
        'role'     => 'Chargée de dépôt',
        'cellule'  => 'Cellule Aspects administratifs et RH',
    ],
    [
        'email'    => 'ghita.amine@spadr.ma',
        'name'     => 'Ghita AMINE',
        'full_name'=> 'Mme. Ghita AMINE',
        'role'     => 'user',
        'cellule'  => 'Cellule Aspects administratifs et RH',
    ],
    [
        'email'    => 'loubna.zmiri@spadr.ma',
        'name'     => 'Loubna ZMIRI',
        'full_name'=> 'Mme. Loubna ZMIRI',
        'role'     => 'Chef de Département',
        'cellule'  => 'Cellule Qualité de service',
    ],
    [
        'email'    => 'meryem.labibe@spadr.ma',
        'name'     => 'Meryem LABIB',
        'full_name'=> 'Mme. Meryem LABIB',
        'role'     => 'Chef de Département',
        'cellule'  => 'Cellule Qualité de service',
    ],
    [
        'email'    => 'fnouaman@spadr.ma',
        'name'     => 'Fatima Azzahra NOUAMAN',
        'full_name'=> 'Mme. Fatima Azzahra NOUAMAN',
        'role'     => 'Chef de Département',
        'cellule'  => 'Cellule Suivi Perf. Commerciale & Tarification',
    ],
];

// ============================================================
// COMPTES DE TEST À SUPPRIMER
// ============================================================
$COMPTES_TEST = [
    'master@test.com',
    'dg@test.com',
    'assistante@test.com',
    'pole@test.com',
    'dept@test.com',
    'user@test.com',
    'it@test.com',
    'budgets@test.com',
    // Conserver master-locaged-mo-hh@locaged.com (compte master)
    // Conserver directrice@test.com si utilisé en production
    'directrice@test.com',
];

// ============================================================
// FONCTIONS
// ============================================================
function log_msg(string $msg): void {
    echo "[" . date('H:i:s') . "] {$msg}\n";
    flush();
}
function log_ok(string $msg): void { echo "\033[32m[OK]  {$msg}\033[0m\n"; flush(); }
function log_err(string $msg): void { echo "\033[31m[ERR] {$msg}\033[0m\n"; flush(); }
function log_warn(string $msg): void { echo "\033[33m[WARN] {$msg}\033[0m\n"; flush(); }

// ============================================================
// ÉTAPE 1 : Vérifier les rôles en base
// ============================================================
log_msg("=== CRÉATION COMPTES SPCR RABAT ===");
log_msg("Vérification des rôles en base...");

$rolesRequis = [
    'Directrice du SPCR',
    'Assistante de Direction',
    'Chef de Pôle',
    'Chef de Département',
    'IT Admin',
    'user',
    'Chargée de dépôt',
];

$rolesMissing = [];
foreach ($rolesRequis as $roleName) {
    $role = Role::where('name', $roleName)->first();
    if (!$role) {
        $rolesMissing[] = $roleName;
    }
}

if (!empty($rolesMissing)) {
    log_err("RÔLES MANQUANTS :");
    foreach ($rolesMissing as $r) {
        log_err("  - {$r}");
    }
    log_err("Lancez d'abord : php artisan db:seed --class=RolesAndPermissionsSeeder --force");
    exit(1);
}
log_ok("Tous les rôles requis sont en base");

// ============================================================
// ÉTAPE 2 : Supprimer les comptes de test
// ============================================================
log_msg("\nSuppression des comptes de test...");
foreach ($COMPTES_TEST as $email) {
    $user = User::where('email', $email)->first();
    if ($user) {
        $user->roles()->detach();
        $user->delete();
        log_ok("Supprimé : {$email}");
    } else {
        log_msg("  [SKIP] Déjà absent : {$email}");
    }
}

// ============================================================
// ÉTAPE 3 : Créer les 26 comptes
// ============================================================
log_msg("\nCréation des " . count($COMPTES) . " comptes @spadr.ma...");

// Charger tous les services en mémoire
$servicesMap = Service::all()->keyBy('name');

// Pour les Chef de Pôle : il y a 2 services "Direction du Pôle" (Technique et Admin)
// On les distingue par le pôle parent
$dirPolesMap = [];
foreach (Service::where('name', 'Direction du Pôle')->with('subDepartment.department')->get() as $svc) {
    $poleName = $svc->subDepartment?->department?->name ?? '';
    $dirPolesMap[$poleName] = $svc;
}

$compteurs = ['created' => 0, 'updated' => 0, 'error' => 0];

foreach ($COMPTES as $def) {
    try {
        // Trouver le service
        $service = null;

        if (isset($def['pole']) && $def['cellule'] === 'Direction du Pôle') {
            // Chef de Pôle : distinguer par pole parent
            $service = $dirPolesMap[$def['pole']] ?? null;
        } else {
            $service = $servicesMap[$def['cellule']] ?? null;
        }

        if (!$service) {
            log_err("Service '{$def['cellule']}' non trouvé pour {$def['email']} — compte créé sans service");
        }

        // Username par défaut = partie avant @ de l'email
        $username = $def['username'] ?? explode('@', $def['email'])[0];

        // Vérifier si existe déjà
        $user = User::where('email', $def['email'])->first();

        if ($user) {
            // Mettre à jour
            $user->name      = $def['name'];
            $user->full_name = $def['full_name'] ?? $def['name'];
            $user->username  = $username;
            $user->save();
            log_warn("Mise à jour : {$def['email']}");
            $compteurs['updated']++;
        } else {
            // Créer
            $userData = [
                'name'       => $def['name'],
                'email'      => $def['email'],
                'password'   => Hash::make($MOT_DE_PASSE_TEMP),
                'username'   => $username,
            ];
            // Ajouter full_name si la colonne existe
            $hasFullName = DB::select("SHOW COLUMNS FROM users LIKE 'full_name'");
            if (!empty($hasFullName)) {
                $userData['full_name'] = $def['full_name'] ?? $def['name'];
            }

            $user = User::create($userData);
            log_ok("Créé : {$def['email']}");
            $compteurs['created']++;
        }

        // Assigner le rôle (sync = remplace tous les rôles précédents)
        $role = Role::where('name', $def['role'])->first();
        if ($role) {
            $user->syncRoles([$role->name]);
        } else {
            log_err("Rôle '{$def['role']}' introuvable pour {$def['email']}");
        }

        // Assigner le service
        if ($service) {
            // Méthode selon votre implémentation (relation pivot ou colonne directe)
            // Option 1 : relation many-to-many via user_service
            if (method_exists($user, 'services')) {
                $user->services()->sync([$service->id]);
            }
            // Option 2 : colonne service_id directe sur users
            elseif (DB::select("SHOW COLUMNS FROM users LIKE 'service_id'")) {
                $user->service_id = $service->id;
                $user->save();
            }
        }

    } catch (\Exception $e) {
        log_err("ERREUR pour {$def['email']} : " . $e->getMessage());
        $compteurs['error']++;
    }
}

// ============================================================
// RAPPORT FINAL
// ============================================================
log_msg("\n=== RÉSULTAT ===");
log_ok("Comptes créés   : {$compteurs['created']}");
log_warn("Comptes mis à jour : {$compteurs['updated']}");
if ($compteurs['error'] > 0) {
    log_err("Erreurs         : {$compteurs['error']}");
}
log_msg("Mot de passe temp : {$MOT_DE_PASSE_TEMP}");
log_msg("⚠️  Informer les utilisateurs de changer leur mot de passe à la première connexion");
log_msg("Durée : " . round(microtime(true) - LARAVEL_START, 2) . "s");
