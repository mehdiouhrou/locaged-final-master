# Déploiement : recherche (Scout / Typesense) et Horizon

## Recherche

- Le projet utilise **Laravel Scout** avec le moteur **Typesense** (`typesense/typesense-php`). Variables typiques : `SCOUT_DRIVER=typesense`, `TYPESENSE_*` (voir `.env.example`).
- Les indexations sont déclenchées sur les modèles concernés (ex. `DocumentVersion` searchable). Les documents non approuvés ne doivent pas être indexés.

## Queues et Horizon

- `QUEUE_CONNECTION=redis` est requis pour **Horizon** en production.
- Les jobs OCR (`ProcessOcrJob`) peuvent être routés vers une file dédiée via `OCR_QUEUE` (voir `config/ged.php`). Configurer un superviseur Horizon qui écoute cette file avec une concurrence adaptée (OCR = CPU intensif).
- La file `default` reste disponible pour les notifications et tâches courtes.

## Fichiers de configuration

- [`config/horizon.php`](../config/horizon.php) — superviseurs, `path`, Redis.
- [`config/queue.php`](../config/queue.php) — connexions.
- Accès UI Horizon : permission `access horizon` (gate `viewHorizon`).

## Commandes

```bash
php artisan horizon
```

Superviseur systemd / Docker : lancer `horizon` en tant que processus long, avec `horizon:terminate` au déploiement avant redémarrage.
