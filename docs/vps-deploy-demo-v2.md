# Déploiement LocaGed **demo-v2** sur VPS

Ce guide suppose **Ubuntu 22.04** (ou équivalent), accès **root/sudo**, et un domaine ou IP pointant vers le serveur.

## 1. Stack minimale

| Composant | Rôle |
|-----------|------|
| PHP 8.2+ FPM + extensions (mysql, redis, curl, gd, imagick, mbstring, xml, zip, bcmath, intl) | Application |
| Nginx | HTTP |
| MySQL 8 / MariaDB | Base |
| Redis | Cache, sessions, queues, **Horizon** |
| Node 20+ | `npm run build` sur le serveur (ou build local + rsync `public/build`) |
| Typesense | Si `SCOUT_DRIVER=typesense` dans `.env` |

Optionnel : **Tesseract** + langues (fra, eng, ara) pour l’OCR.

Référence installation complète des paquets : `install.sh` à la racine du dépôt (répertoire par défaut `/var/www/locaged` — pour la V2 utilise un autre dossier, ex. `/var/www/locaged-v2`).

## 2. Base de données

```sql
CREATE DATABASE locaged_v2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'locaged_v2'@'localhost' IDENTIFIED BY 'MOT_DE_PASSE_FORT';
GRANT ALL PRIVILEGES ON locaged_v2.* TO 'locaged_v2'@'localhost';
FLUSH PRIVILEGES;
```

## 3. Cloner / mettre à jour le code (branche `demo-v2`)

```bash
export APP_ROOT=/var/www/locaged-v2
export REPO_URL=https://github.com/mehdiouhrou/locaged-final-master.git
sudo mkdir -p "$APP_ROOT"
sudo chown -R "$USER:$USER" "$APP_ROOT"
```

**Script** (`scripts/deploy-demo-v2-vps.sh`) : clone automatique si `$APP_ROOT` n’est pas encore un dépôt Git, puis `git pull`, `composer`, `npm run build`, `migrate`, caches.

```bash
export APP_ROOT=/var/www/locaged-v2
export REPO_URL=https://github.com/mehdiouhrou/locaged-final-master.git
cd /var/www
git clone --branch demo-v2 --single-branch "$REPO_URL" locaged-v2
cd locaged-v2
bash scripts/deploy-demo-v2-vps.sh
```

Vous pouvez aussi lancer uniquement le script : sans dossier Git, il clone dans `$APP_ROOT` (par défaut `/var/www/locaged-v2`).

## 4. Fichier `.env`

```bash
cd /var/www/locaged-v2
cp .env.example .env
nano .env
```

À ajuster au minimum :

- `APP_ENV=production`, `APP_DEBUG=false`
- `APP_URL=https://votre-domaine.tld` (et `ASSET_URL` identique si pas de CDN)
- `DB_*` vers la base créée ci-dessus
- `SESSION_DRIVER=redis`, `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis` (recommandé pour Horizon)
- `SCOUT_DRIVER=typesense` + `TYPESENSE_*` si recherche Typesense
- `GED_CATEGORY_ACCESS_DRIVER=profile` (déjà dans l’exemple V2)

Générer la clé si besoin :

```bash
php artisan key:generate --force
```

## 5. Première initialisation données

```bash
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan init:env
```

`init:env` crée notamment `admin@example.com` / `password` (rôle **master**) — **changez le mot de passe** tout de suite en prod.

## 6. Liens symboliques et droits

```bash
php artisan storage:link
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rwx storage bootstrap/cache
```

## 7. Nginx (exemple)

Remplacez `server_name` et `root` :

```nginx
server {
    listen 80;
    server_name votre-domaine.tld;
    root /var/www/locaged-v2/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Puis : `sudo nginx -t && sudo systemctl reload nginx`.

HTTPS : utilisez **Certbot** (`certbot --nginx`).

## 8. Scheduler & queues

**Cron** (utilisateur qui exécute PHP, souvent `www-data`) :

```cron
* * * * * cd /var/www/locaged-v2 && php artisan schedule:run >> /dev/null 2>&1
```

**Horizon** (recommandé si `QUEUE_CONNECTION=redis`) — exemple Supervisor :

```ini
[program:locaged-v2-horizon]
process_name=%(program_name)s
command=php /var/www/locaged-v2/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/locaged-v2/storage/logs/horizon.log
stopwaitsecs=3600
```

`sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl start locaged-v2-horizon`

## 9. Déploiements suivants

Sur le serveur :

```bash
cd /var/www/locaged-v2
sudo -u www-data git pull origin demo-v2
sudo -u www-data composer install --no-dev --optimize-autoloader --no-interaction
sudo -u www-data npm ci && sudo -u www-data npm run build
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan config:cache && sudo -u www-data php artisan route:cache && sudo -u www-data php artisan view:cache
sudo -u www-data php artisan queue:restart
sudo systemctl reload php8.2-fpm
```

Ou relancer `scripts/deploy-demo-v2-vps.sh` avec les mêmes variables d’environnement.

## 10. Check-list rapide

- [ ] `.env` complet, `APP_DEBUG=false`
- [ ] MySQL migré + seed / `init:env`
- [ ] `storage:link`, droits `storage` et `bootstrap/cache`
- [ ] Redis + Typesense si utilisés
- [ ] Nginx `root` → `…/public`
- [ ] Cron `schedule:run`
- [ ] Horizon ou `queue:work` si jobs OCR / notifications
- [ ] Mot de passe admin changé après premier login
