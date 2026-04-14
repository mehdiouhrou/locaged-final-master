# Destruction, expiration et PV (spec vs implémentation actuelle)

## Spec cible

- Expiration : passage à un statut « expiré », documents encore consultables avec badge.
- Destruction : action irréversible réservée aux rôles autorisés ; **PV horodaté** ; conservation longue durée (ex. 30 ans) ; traces d’audit conservées.

## Implémentation actuelle (repères code)

- Demandes de destruction et flux associés : [`DocumentDestructionRequestController`](../app/Http/Controllers/DocumentDestructionRequestController.php), modèle `DocumentDestructionRequest`.
- Documents : champs `expire_at`, `is_expired`, statuts `archived` / `destroyed` dans [`DocumentStatus`](../app/Enums/DocumentStatus.php).
- Commandes planifiées : voir `app/Console/Commands` (`CheckExpiredDocuments`, `MarkExpiredDocuments`, etc.).

## Écarts connus

- La spec mentionne une table dédiée **`destruction_certificates`** et un PV systématique : **à valider métier** ; si absent en base, prévoir migration + génération PDF (mPDF déjà présent dans le projet) dans une phase ultérieure.

## Alignement sans migration lourde

- Documenter dans les procédures internes l’usage des écrans « Destruction » existants.
- Lors de l’ajout du PV : stocker le chemin du fichier PDF et les hashes dans une table dédiée ou dans `document_destruction_requests` selon le modèle retenu.
