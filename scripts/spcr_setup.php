<?php
/**
 * SPCR LocaGed — Script de création structure complète
 * Sources : Organigramme, NS N°2/3-2025, Plan classement, CANEVAS, Calendrier conservation
 * 76 catégories (7 DG + 27 REDAL + 33 SRM + 9 SPC) + partages exacts
 */

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// ─────────────────────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────────────────────
function dept($name) {
    $r = DB::table('departments')->where('name', $name)->first();
    if ($r) { echo "  [exist] DEPT: $name [id:{$r->id}]\n"; return $r->id; }
    $id = DB::table('departments')->insertGetId(['name'=>$name,'created_at'=>now(),'updated_at'=>now()]);
    echo "  [crée]  DEPT: $name [id:$id]\n";
    return $id;
}
function subdept($name, $dept_id) {
    $r = DB::table('sub_departments')->where('name',$name)->where('department_id',$dept_id)->first();
    if ($r) { echo "  [exist] UNITE: $name [id:{$r->id}]\n"; return $r->id; }
    $id = DB::table('sub_departments')->insertGetId(['name'=>$name,'department_id'=>$dept_id,'created_at'=>now(),'updated_at'=>now()]);
    echo "  [crée]  UNITE: $name [id:$id]\n";
    return $id;
}
function svc($name, $subdept_id) {
    $r = DB::table('services')->where('name',$name)->where('sub_department_id',$subdept_id)->first();
    if ($r) { echo "  [exist] CELL: $name [id:{$r->id}]\n"; return $r->id; }
    $id = DB::table('services')->insertGetId(['name'=>$name,'sub_department_id'=>$subdept_id,'created_at'=>now(),'updated_at'=>now()]);
    echo "  [crée]  CELL: $name [id:$id]\n";
    return $id;
}
function cat($name, $service_id, $retention) {
    $r = DB::table('categories')->where('name',$name)->first();
    if ($r) {
        // Mettre à jour la conservation si elle a changé
        if (Schema::hasColumn('categories','retention_period')) {
            DB::table('categories')->where('id',$r->id)->update(['retention_period'=>$retention,'service_id'=>$service_id,'updated_at'=>now()]);
        }
        echo "  [exist] CAT: ".substr($name,0,60)." [id:{$r->id}]\n";
        return $r->id;
    }
    $data = ['name'=>$name,'service_id'=>$service_id,'created_at'=>now(),'updated_at'=>now()];
    if (Schema::hasColumn('categories','retention_period')) $data['retention_period'] = $retention;
    $id = DB::table('categories')->insertGetId($data);
    echo "  [crée]  CAT: ".substr($name,0,60)." [id:$id]\n";
    return $id;
}
function share($cat_id, array $svc_ids) {
    $n = 0;
    foreach ($svc_ids as $sid) {
        if (!$sid) continue;
        $exists = DB::table('category_service')->where('category_id',$cat_id)->where('service_id',$sid)->exists();
        if (!$exists) {
            DB::table('category_service')->insert(['category_id'=>$cat_id,'service_id'=>$sid,'created_at'=>now(),'updated_at'=>now()]);
            $n++;
        }
    }
    if ($n > 0) echo "    → $n partage(s) ajouté(s)\n";
}
function assignUser($email, $service_id) {
    $user = App\Models\User::where('email',$email)->first();
    if (!$user) { echo "  [skip] user $email introuvable\n"; return; }
    if (method_exists($user,'services')) {
        $user->services()->syncWithoutDetaching([$service_id]);
        echo "  [ok] $email → service_id:$service_id\n";
    } else {
        $user->update(['service_id'=>$service_id]);
        echo "  [ok direct] $email → service_id:$service_id\n";
    }
}

// ─────────────────────────────────────────────────────────────
// VERIF COLONNE retention_period
// ─────────────────────────────────────────────────────────────
echo "\n=== VÉRIFICATION SCHÉMA ===\n";
if (!Schema::hasColumn('categories','retention_period')) {
    Schema::table('categories', function($t) {
        $t->string('retention_period')->nullable()->after('name');
    });
    echo "  [ok] Colonne retention_period ajoutée\n";
} else {
    echo "  [ok] Colonne retention_period déjà présente\n";
}

// ─────────────────────────────────────────────────────────────
// 1. DÉPARTEMENTS (3 Pôles)
// ─────────────────────────────────────────────────────────────
echo "\n=== DÉPARTEMENTS ===\n";
$dg_id    = dept('Direction Générale');
$tech_id  = dept('Pôle Suivi des aspects techniques et investissements');
$admin_id = dept('Pôle administratif et Suivi des aspects Financiers');

// ─────────────────────────────────────────────────────────────
// 2. SOUS-DÉPARTEMENTS (Unités)
// ─────────────────────────────────────────────────────────────
echo "\n=== UNITÉS ===\n";

// Direction Générale
$u_secretariat = subdept('Secrétariat et Bureau d\'ordre du SPCR', $dg_id);

// Pôle Technique
$u_ep    = subdept('Unité suivi des aspects Eau Potable', $tech_id);
$u_ass   = subdept('Unité suivi des aspects Assainissement', $tech_id);
$u_elec  = subdept('Unité suivi des aspects Electricité', $tech_id);
$u_pat   = subdept('Unité suivi du Patrimoine et SI', $tech_id);

// Pôle Admin
$u_budgets  = subdept('Unité Suivi des Budgets', $admin_id);
$u_fin      = subdept('Unité Suivi des performances financières', $admin_id);
$u_rh       = subdept('Unité aspects Administratifs et RH', $admin_id);
$u_client   = subdept('Unité des aspects clientèle', $admin_id);

// ─────────────────────────────────────────────────────────────
// 3. SERVICES (Cellules)
// ─────────────────────────────────────────────────────────────
echo "\n=== CELLULES ===\n";

// DG / Secrétariat
$c_secretariat = svc('Secrétariat et Bureau d\'ordre', $u_secretariat);

// Pôle Tech — Eau Potable
$c_invest_ep     = svc('Cellule Investissements Eau Potable', $u_ep);
$c_perf_ep       = svc('Cellule Performances Techniques Eau Potable', $u_ep);

// Pôle Tech — Assainissement
$c_invest_ass    = svc('Cellule Investissements Assainissement', $u_ass);
$c_perf_ass      = svc('Cellule Performances Techniques Assainissement', $u_ass);

// Pôle Tech — Electricité
$c_invest_elec   = svc('Cellule Investissements Electricité', $u_elec);
$c_perf_elec     = svc('Cellule Performances Techniques Electricité', $u_elec);

// Pôle Tech — Patrimoine & SI
$c_si            = svc('Cellule Développement et Maintenance SI', $u_pat);
$c_patrimoine    = svc('Cellule Patrimoine', $u_pat);

// Pôle Admin — Budgets
$c_budgets_plt   = svc('Cellule Budgets et PLT', $u_budgets);
$c_valorisation  = svc('Cellule Valorisation des investissements', $u_budgets);

// Pôle Admin — Performances financières
$c_compta        = svc('Cellule Comptabilité & Finances', $u_fin);
$c_hypotheses    = svc('Cellule Suivi des Hypothèses et de l\'équilibre économique & financier des Contrats', $u_fin);

// Pôle Admin — Admin & RH
$c_rh            = svc('Cellule Aspects administratifs et RH', $u_rh);
$c_audit         = svc('Cellule Audits et Juridique', $u_rh);

// Pôle Admin — Clientèle
$c_qualite       = svc('Cellule Qualité de service', $u_client);
$c_perf_comm     = svc('Cellule Suivi de la Performance Commerciale & de Tarification', $u_client);

// Raccourcis groupes pour les partages
$ALL_TECH  = [$c_invest_ep, $c_perf_ep, $c_invest_ass, $c_perf_ass, $c_invest_elec, $c_perf_elec, $c_si, $c_patrimoine];
$ALL_ADMIN = [$c_budgets_plt, $c_valorisation, $c_compta, $c_hypotheses, $c_rh, $c_audit, $c_qualite, $c_perf_comm];
$ALL_BOTH  = array_merge($ALL_TECH, $ALL_ADMIN);

// ─────────────────────────────────────────────────────────────
// 4. CATÉGORIES
// ─────────────────────────────────────────────────────────────
echo "\n=== CATÉGORIES ===\n";

// ── DIRECTION GÉNÉRALE (7 catégories) ──
echo "\n--- Direction Générale & Secrétariat ---\n";
$cat_ids = [];

$cat_ids['dg1'] = cat('Instances de gouvernance', $c_secretariat, 'Permanent');
$cat_ids['dg2'] = cat('Documentation Stratégique', $c_secretariat, 'Permanent');
$cat_ids['dg3'] = cat('Textes réglementaires', $c_secretariat, 'Définitive');
$cat_ids['dg4'] = cat('Pilotage des activités du SPCR', $c_secretariat, 'Permanent');
$cat_ids['dg5'] = cat('Notes et décisions', $c_secretariat, 'Permanent');
$cat_ids['dg6'] = cat('Courriers arrivée', $c_secretariat, '5 ans');
$cat_ids['dg7'] = cat('Courriers départ', $c_secretariat, '5 ans');
// Pas de partage pour les catégories DG

// ── REDAL — N°1-11 (Pôle Admin) ──
echo "\n--- REDAL N°1-11 (Pôle Administratif) ---\n";

$re1  = cat('RE-Dossier N°1 : Communication des états d\'arrêtés des comptes de l\'AD', $c_compta, '10 ans');
// pas de partage — Pôle Admin interne

$re2  = cat('RE-Dossier N°2 : Communication du Reporting des Comptes de l\'Autorité Délégante', $c_compta, 'Permanent');
// pas de partage

$re3  = cat('RE-Dossier N°3 : Copie de la liasse fiscale des états comptables annuels', $c_hypotheses, '10 ans');
// pas de partage

$re4  = cat('RE-Dossier N°4 : Indicateur de performance Commerciale (Pénalités)', $c_perf_comm, '10 ans');
share($re4, [$c_qualite]);

$re5  = cat('RE-Dossier N°5 : Situation des consommations illicites par compteur', $c_perf_comm, '10 ans');
share($re5, [$c_qualite]);

$re6  = cat('RE-Dossier N°6 : Bordereau des prix actualisé', $c_budgets_plt, '10 ans');
share($re6, [$c_valorisation]);

$re7  = cat('RE-Dossier N°7 : Etat de valorisation avec les écarts sur les budgets approuvés', $c_budgets_plt, '10 ans');
share($re7, [$c_valorisation]);

$re8  = cat('RE-Dossier N°8 : Justificatifs des investissements en cours de réalisation', $c_budgets_plt, 'Permanent');
share($re8, [$c_valorisation]);

$re9  = cat('RE-Dossier N°9 : Reporting achat', $c_budgets_plt, '10 ans');
share($re9, [$c_valorisation]);

$re10 = cat('RE-Dossier N°10 : Suivi des dépenses relatives aux projets d\'investissements', $c_budgets_plt, '10 ans');
share($re10, [$c_valorisation]);

$re11 = cat('RE-Dossier N°11 : Rapport des Commissaires aux comptes (Général & Spécial)', $c_audit, '10 ans');
share($re11, array_diff($ALL_ADMIN, [$c_audit])); // toutes cellules Pôle Admin sauf propriétaire

// ── REDAL — N°12-15 (Pôle Tech, Electricité uniquement) ──
echo "\n--- REDAL N°12-15 (Pôle Technique — Electricité) ---\n";

$re12 = cat('RE-Dossier N°12 : Etat des puissances appelées par poste source et par transformateur HTB/HTA', $c_perf_elec, '10 ans');
// pas de partage

$re13 = cat('RE-Dossier N°13 : Etat des rendements cumulés et glissants du mois n-1', $c_perf_elec, '10 ans');
share($re13, [$c_invest_elec]); // Unité Asp. Elec. = Cellule Invest. Elec

$re14 = cat('RE-Dossier N°14 : Rapport sur les interruptions partielles ou générales', $c_perf_elec, '10 ans');
// pas de partage

$re15 = cat('RE-Dossier N°15 : Rapport sur l\'état des incidents', $c_perf_elec, '10 ans');
// pas de partage

// ── REDAL — N°16-27 (Les 2 Pôles — tous les services) ──
echo "\n--- REDAL N°16-27 (Partagé Pôle Tech + Pôle Admin) ---\n";

$re16 = cat('RE-Dossier N°16 : Compte de rendu annuel', $c_invest_ep, 'Permanent');
share($re16, array_diff($ALL_BOTH, [$c_invest_ep]));

$re17 = cat('RE-Dossier N°17 : Budget d\'investissement de l\'année N+1 (REDAL)', $c_invest_ep, '10 ans');
share($re17, array_diff($ALL_BOTH, [$c_invest_ep]));

$re18 = cat('RE-Dossier N°18 : Budget Fonctionnement de l\'année N+1 (REDAL)', $c_invest_ep, '10 ans');
share($re18, array_diff($ALL_BOTH, [$c_invest_ep]));

$re19 = cat('RE-Dossier N°19 : Plan pluriannuel Glissant sur 5 ans de l\'année N+1 (REDAL)', $c_invest_ep, 'Permanent');
share($re19, array_diff($ALL_BOTH, [$c_invest_ep]));

$re20 = cat('RE-Dossier N°20 : Inventaire annuel des biens des Services Délégués (REDAL)', $c_patrimoine, '10 ans');
share($re20, array_diff($ALL_BOTH, [$c_patrimoine]));

$re21 = cat('RE-Dossier N°21 : Publication du programme prévisionnel des marchés (REDAL)', $c_invest_ep, '10 ans');
share($re21, array_diff($ALL_BOTH, [$c_invest_ep]));

$re22 = cat('RE-Dossier N°22 : Liste assurances REDAL', $c_audit, '10 ans');
share($re22, array_diff($ALL_BOTH, [$c_audit]));

$re23 = cat('RE-Dossier N°23 : Cautions REDAL', $c_audit, '10 ans');
share($re23, array_diff($ALL_BOTH, [$c_audit]));

$re24 = cat('RE-Dossier N°24 : Schémas directeurs (eau, électricité, assainissement) REDAL', $c_invest_ep, 'Définitive');
share($re24, array_diff($ALL_BOTH, [$c_invest_ep]));

$re25 = cat('RE-Dossier N°25 : Manuels des procédures REDAL', $c_audit, 'Définitive');
share($re25, array_diff($ALL_BOTH, [$c_audit]));

$re26 = cat('RE-Dossier N°26 : Conventions cadres signées REDAL', $c_audit, 'Définitive');
share($re26, array_diff($ALL_BOTH, [$c_audit]));

$re27 = cat('RE-Dossier N°27 : Dossier CA REDAL', $c_audit, 'Permanent');
share($re27, array_diff($ALL_BOTH, [$c_audit]));

// ── SRM — N°1-13 (Pôle Tech, cellules spécifiques) ──
echo "\n--- SRM N°1-13 (Pôle Technique — cellules ciblées) ---\n";

$TECH_INVEST_PERF = [$c_invest_ep, $c_invest_ass, $c_invest_elec, $c_perf_ep, $c_perf_ass, $c_perf_elec];

$sr1  = cat('SR-Dossier N°1 : Plan pluriannuel d\'investissement (N+1 à N+5) SRM', $c_invest_ep, 'Permanent');
share($sr1, array_diff($TECH_INVEST_PERF, [$c_invest_ep]));

$sr2  = cat('SR-Dossier N°2 : Budget annuel d\'investissement (N+1) SRM', $c_invest_ep, '10 ans');
share($sr2, array_diff($TECH_INVEST_PERF, [$c_invest_ep]));

$sr3  = cat('SR-Dossier N°3 : KPI performances techniques Electricité SRM', $c_perf_elec, '10 ans');
share($sr3, [$c_invest_elec]);

$sr4  = cat('SR-Dossier N°4 : KPI performances techniques Eau Potable SRM', $c_perf_ep, '10 ans');
share($sr4, [$c_invest_ep]);

$sr5  = cat('SR-Dossier N°5 : KPI performances techniques Assainissement SRM', $c_perf_ass, '10 ans');
share($sr5, [$c_invest_ass]);

$sr6  = cat('SR-Dossier N°6 : KPI performances techniques STEP SRM', $c_perf_ass, '10 ans');
share($sr6, [$c_invest_ass]);

$sr7  = cat('SR-Dossier N°7 : KPI performances clientèle SRM', $c_perf_comm, '10 ans');
share($sr7, [$c_qualite]);

$sr8  = cat('SR-Dossier N°8 : Suivi du patrimoine initial SRM', $c_patrimoine, '10 ans');
share($sr8, [$c_si]);

$sr9  = cat('SR-Dossier N°9 : Inventaire permanent des stocks et immobilisations SRM', $c_patrimoine, 'Permanent');
share($sr9, [$c_si]);

$sr10 = cat('SR-Dossier N°10 : Indicateurs achats/ventes eau & électricité SRM', $c_perf_comm, '10 ans');
share($sr10, [$c_qualite]);

$sr11 = cat('SR-Dossier N°11 : Indicateurs de performance investissement SRM', $c_invest_ep, '5 à 10 ans');
share($sr11, array_diff($TECH_INVEST_PERF, [$c_invest_ep]));

$sr12 = cat('SR-Dossier N°12 : Facturation des travaux réalisés - Bordereau des prix SRM', $c_invest_ep, '10 ans');
share($sr12, array_diff($TECH_INVEST_PERF, [$c_invest_ep]));

$sr13 = cat('SR-Dossier N°13 : Schémas directeurs SRM', $c_invest_ep, 'Définitive');
share($sr13, array_diff($TECH_INVEST_PERF, [$c_invest_ep]));

// ── SRM — N°14-33 (Les 2 Pôles) ──
echo "\n--- SRM N°14-33 (Partagé Pôle Tech + Pôle Admin) ---\n";

$sr14 = cat('SR-Dossier N°14 : Projections financières N à N+5 SRM', $c_invest_ep, 'Permanent');
share($sr14, array_diff($ALL_BOTH, [$c_invest_ep]));

$sr15 = cat('SR-Dossier N°15 : Budget Fonctionnement de l\'année N+1 SRM', $c_invest_ep, 'Permanent');
share($sr15, array_diff($ALL_BOTH, [$c_invest_ep]));

$sr16 = cat('SR-Dossier N°16 : Budget de trésorerie consolidé (N+1) SRM', $c_compta, '10 ans');
share($sr16, [$c_hypotheses]); // Service Financier uniquement (Ziat, Sody, El Adnani, Touhami)

$sr17 = cat('SR-Dossier N°17 : Compte rendu financier SRM', $c_compta, '10 ans');
share($sr17, [$c_hypotheses]); // Service Financier uniquement

$sr18 = cat('SR-Dossier N°18 : Situations mensuelles d\'exécution budgétaire SRM', $c_invest_ep, '10 ans');
share($sr18, array_diff($ALL_BOTH, [$c_invest_ep]));

$sr19 = cat('SR-Dossier N°19 : Comptes de produits et charges (CPC) SRM', $c_compta, '10 ans');
share($sr19, array_diff($ALL_BOTH, [$c_compta]));

$sr20 = cat('SR-Dossier N°20 : Rapport de performances financières SRM', $c_compta, '10 ans');
share($sr20, array_diff($ALL_BOTH, [$c_compta]));

$sr21 = cat('SR-Dossier N°21 : État des soldes de gestion (ESG) SRM', $c_compta, '10 ans');
share($sr21, array_diff($ALL_BOTH, [$c_compta]));

$sr22 = cat('SR-Dossier N°22 : Manuel des procédures comptables SRM', $c_compta, 'Définitive');
share($sr22, array_diff($ALL_BOTH, [$c_compta]));

$sr23 = cat('SR-Dossier N°23 : Situation mensuelle du compte FEC / Arrêté annuel SRM', $c_compta, '10 ans');
share($sr23, array_diff($ALL_BOTH, [$c_compta]));

$sr24 = cat('SR-Dossier N°24 : Copie de la liasse fiscale SRM', $c_compta, '10 ans');
share($sr24, array_diff($ALL_BOTH, [$c_compta]));

$sr25 = cat('SR-Dossier N°25 : Etats Comptabilité budgétaire SRM', $c_compta, '10 ans');
share($sr25, array_diff($ALL_BOTH, [$c_compta]));

$sr26 = cat('SR-Dossier N°26 : Rapport Commissaire aux Comptes SRM', $c_audit, '10 ans');
share($sr26, array_diff($ALL_BOTH, [$c_audit]));

$sr27 = cat('SR-Dossier N°27 : Liste assurances SRM', $c_audit, '10 ans');
share($sr27, array_diff($ALL_BOTH, [$c_audit]));

$sr28 = cat('SR-Dossier N°28 : Versement des échéances dettes ONEE SRM', $c_compta, '10 ans');
share($sr28, array_diff($ALL_BOTH, [$c_compta]));

$sr29 = cat('SR-Dossier N°29 : Convention cadre signée ONEE et partenaires sociaux SRM', $c_audit, '10 ans');
share($sr29, array_diff($ALL_BOTH, [$c_audit]));

$sr30 = cat('SR-Dossier N°30 : Liste du personnel (actualisée) SRM', $c_rh, 'Définitive');
share($sr30, array_diff($ALL_BOTH, [$c_rh]));

$sr31 = cat('SR-Dossier N°31 : Plan de formation annuel et pluriannuel SRM', $c_rh, 'Définitive');
share($sr31, array_diff($ALL_BOTH, [$c_rh]));

$sr32 = cat('SR-Dossier N°32 : Engagements sociaux et rapports aux partenaires sociaux SRM', $c_rh, 'Définitive');
share($sr32, array_diff($ALL_BOTH, [$c_rh]));

$sr33 = cat('SR-Dossier N°33 : Canevas des indicateurs de performance RH SRM', $c_rh, '5 ans');
share($sr33, array_diff($ALL_BOTH, [$c_rh]));

// ── SPC — N°1-9 ──
echo "\n--- SPC N°1-9 ---\n";

$sp1 = cat('SP-Dossier N°1 : Budget annuel du SPC (FEC REDAL & FEC SRM)', $c_budgets_plt, '10 ans');
share($sp1, array_diff($ALL_BOTH, [$c_budgets_plt]));

$sp2 = cat('SP-Dossier N°2 : Rapports d\'activité du SPC', $c_budgets_plt, '10 ans');
share($sp2, array_diff($ALL_BOTH, [$c_budgets_plt]));

$sp3 = cat('SP-Dossier N°3 : Rapports D\'audit SPC', $c_audit, '10 ans');
share($sp3, array_diff($ALL_BOTH, [$c_audit]));

$sp4 = cat('SP-Dossier N°4 : Rapports Semestriels et annuels SPC', $c_budgets_plt, 'Permanent');
share($sp4, array_diff($ALL_BOTH, [$c_budgets_plt]));

$sp5 = cat('SP-Dossier N°5 : Marchés et BCs SPC', $c_audit, '10 ans');
share($sp5, array_diff($ALL_BOTH, [$c_audit]));

$sp6 = cat('SP-Dossier N°6 : CRQ SPC', $c_budgets_plt, '10 ans');
share($sp6, array_diff($ALL_BOTH, [$c_budgets_plt]));

$sp7 = cat('SP-Dossier N°7 : Rapports et Fiches Visites Terrains SPC', $c_invest_ep, '5 ans');
share($sp7, array_diff($ALL_BOTH, [$c_invest_ep]));

$sp8 = cat('SP-Dossier N°8 : Etats du dossier RH (SDSPD) SPC', $c_rh, '10 ans après départ du salarié');
share($sp8, array_diff($ALL_ADMIN, [$c_rh])); // Pôle Admin uniquement

$sp9 = cat('SP-Dossier N°9 : Conventions CAS SPC', $c_audit, 'Définitive');
share($sp9, array_diff($ALL_BOTH, [$c_audit]));

// ─────────────────────────────────────────────────────────────
// 5. ASSIGNATION UTILISATEURS DE TEST
// ─────────────────────────────────────────────────────────────
echo "\n=== ASSIGNATION UTILISATEURS ===\n";
assignUser('dg@test.com',          $c_secretariat);
assignUser('assistante@test.com',  $c_secretariat);
assignUser('master@test.com',      $c_secretariat);
assignUser('pole@test.com',        $c_invest_ep);          // Chef Pôle Tech
assignUser('dept@test.com',        $c_perf_ep);            // Chef Unité EP
assignUser('user@test.com',        $c_invest_ep);          // user Pôle Tech
assignUser('it@test.com',          $c_si);                 // IT Admin → Cell. SI
assignUser('budgets@test.com',     $c_budgets_plt);        // user → Cell. Budgets

// ─────────────────────────────────────────────────────────────
// 6. RÉCAPITULATIF FINAL
// ─────────────────────────────────────────────────────────────
echo "\n=== RÉCAPITULATIF ===\n";
echo "  Départements : " . DB::table('departments')->count() . "\n";
echo "  Unités       : " . DB::table('sub_departments')->count() . "\n";
echo "  Cellules     : " . DB::table('services')->count() . "\n";
echo "  Catégories   : " . DB::table('categories')->count() . "\n";
echo "  Partages     : " . DB::table('category_service')->count() . "\n";
echo "\n✅ Structure SPCR créée avec succès !\n";
