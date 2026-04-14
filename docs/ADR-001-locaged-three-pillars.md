# ADR-001 — LocaGed V2 : trois piliers, accès documents, et écarts volontaires avec la spec rédactionnelle

**Statut :** Accepté (implémentation progressive)  
**Date :** 2026-04-12  
**Contexte :** Alignement entre le cahier des charges « trois piliers » (classification / profils / organigramme d’approbation) et le dépôt Laravel 12 actuel.

## Décisions

### 1. Classification documentaire (pilier 1)

- **Décision :** Conserver en base les colonnes d’**étiquetage administratif** sur `categories` (`department_id`, `sub_department_id`, unicité par périmètre) pour l’UI et la gouvernance côté client, **sans** en faire le moteur du contrôle d’accès aux documents.
- **Règle produit :** « Qui peut voir quel document » ne dépend **pas** du département de la catégorie lorsque `GED_CATEGORY_ACCESS_DRIVER=profile` ; il dépend des **profils** (`profile_category` + rattachements utilisateur / service).
- **Spec stricte** (« catégorie sans aucun lien org ») : **non retenue** pour cette phase afin d’éviter une migration destructive massive ; une évolution ultérieure pourra retirer ces colonnes si un tenant unique global est validé.

Voir aussi [classification-and-access.md](./classification-and-access.md).

### 2. Contrôle d’accès (pilier 2)

- **Décision :** Le driver `profile` est la cible ; `legacy` reste disponible pour compatibilité et bascule progressive (`GED_CATEGORY_ACCESS_DRIVER` dans `.env`).
- **Source de vérité code :** [`ProfileCategoryAccessService`](../app/Services/ProfileCategoryAccessService.php) ; la spec mentionne `Profile::getAccessibleCategoryIds` — le modèle [`Profile`](../app/Models/Profile.php) expose déjà `Profile::getAccessibleCategoryIds(User $user)` comme façade vers ce service (une seule implémentation).

### 3. Organigramme (pilier 3)

- **Décision :** Hiérarchie `Department` → `SubDepartment` → `Service` sert l’**audit**, les **rapports**, la **chaîne d’approbation** métier et le scope **legacy** ; elle ne doit pas court-circuiter les profils lorsque le driver est `profile`.

### 4. Statuts « refused » vs « declined »

- **Décision :** Le code et la base utilisent **`declined`**. La spec « refused » est traitée comme **synonyme documentaire** ; pas de renommage DB dans cette phase.

### 5. Recherche plein texte

- **Décision :** Standardiser sur **Laravel Scout + Typesense**. Toute mention Elasticsearch historique est considérée obsolète.

### 6. Rôles nommés (spec §5.1 vs Spatie)

- **Décision :** Les rôles en production restent ceux du seeder ([`RolesAndPermissionsSeeder`](../database/seeders/RolesAndPermissionsSeeder.php)) : `master`, `Super Administrator`, `Admin de pole`, `Admin de departments` (alias métier « Division Chief »), `Admin de cellule`, `user`, `admin`, etc.
- **Matrice spec** (Directrice, IT Admin, …) : à traduire **fonctionnellement** via permissions Spatie (`view any document`, `create user`, …), pas forcément par renommage des rôles.

### 7. Permissions : `can()` vs `hasRole()`

- **Décision :** Réduire progressivement `hasRole()` hors middleware de routes ; introduire des permissions explicites (`access horizon`, `view system activity log`, …) et vérifier avec `$user->can(...)`.

### 8. Documents `pending` (visibilité)

- **Décision :** Hors `view any document`, un document `pending` n’est listé que pour **l’auteur** (`created_by`) ou pour les comptes pouvant **approuver / refuser** (`approve document` / `decline document`) dans le périmètre déjà filtré par le reste du scope. Une affinage « uniquement la chaîne workflow » pourra s’appuyer sur `workflow_rules` une fois le modèle aligné sur la spec (par `category_id`).

## Conséquences

- Les PRs futures sur scopes / audit / upload doivent référencer cet ADR.
- Les environnements peuvent activer `GED_CATEGORY_ACCESS_DRIVER=profile` après contrôle des données `profile_*`.

## Références code

- Scope document : [`app/Models/Document.php`](../app/Models/Document.php)  
- Scope catégorie : [`app/Models/Category.php`](../app/Models/Category.php)  
- Config driver : [`config/ged.php`](../config/ged.php)
