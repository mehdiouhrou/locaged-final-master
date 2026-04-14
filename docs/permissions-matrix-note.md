# Matrice des permissions (Spatie)

La liste **canonique** des noms de permissions et leur assignation par rôle est définie dans :

- [`database/seeders/RolesAndPermissionsSeeder.php`](../database/seeders/RolesAndPermissionsSeeder.php)

Pour comparer avec la matrice fonctionnelle du cahier des charges (Directrice, IT Admin, etc.), se référer à l’[ADR-001](./ADR-001-locaged-three-pillars.md) : les **rôles Spatie** restent les noms déployés ; les intitulés métier se mappent sur des **permissions** (`view any document`, `create user`, `access horizon`, …).

Après modification du seeder, exécuter :

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
```

(en environnement contrôlé ; préférer une commande dédiée prod si les rôles sont déjà personnalisés).
