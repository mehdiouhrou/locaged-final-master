# Classification (catégories) et contrôle d’accès

## Rappel

- **Catégorie / sous-catégorie** : type de document, durée légale (`expiry_value`, `expiry_unit`), workflows associés.
- **Profil** : liste de catégories visibles ; assignation à un **utilisateur** ou à un **service** (héritage pour les membres du service).

## Schéma actuel

Les lignes `categories` peuvent porter `department_id` / `sub_department_id` pour **ranger** les types de documents par pôle ou unité dans l’interface d’administration. Cela **ne remplace pas** le pilier « profils » : avec `GED_CATEGORY_ACCESS_DRIVER=profile`, la liste des documents visibles est calculée via [`ProfileCategoryAccessService`](../app/Services/ProfileCategoryAccessService.php), pas via le département de la catégorie.

## Upload

Le formulaire Livewire [`MultipleDocumentsCreateForm`](../app/Livewire/MultipleDocumentsCreateForm.php) charge les catégories via le **GlobalScope** du modèle `Category` : l’utilisateur ne voit que les catégories autorisées par ses profils (sauf bypass explicite par permission `view any category`).

## Bascule prod

1. Vérifier les lignes `profile_category`, `profile_user`, `profile_service`.
2. Passer `GED_CATEGORY_ACCESS_DRIVER=profile` dans `.env`.
3. Tester avec chaque rôle métier (liste documents, upload, approbations).
