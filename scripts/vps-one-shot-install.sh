#!/usr/bin/env bash
# =============================================================================
# LocaGed demo-v2 — installation VPS Ubuntu 22.04 en une passe (root).
#
# Une seule commande (copier-coller sur le VPS) :
#   curl -fsSL https://raw.githubusercontent.com/mehdiouhrou/locaged-final-master/demo-v2/scripts/vps-one-shot-install.sh | bash
#
# Avec options (exemple) :
#   export APP_URL="http://62.238.16.89"
#   export DB_PASSWORD="MonMotDePasseFort"
#   curl -fsSL https://raw.githubusercontent.com/mehdiouhrou/locaged-final-master/demo-v2/scripts/vps-one-shot-install.sh | bash
#
# Ne pas utiliser sur une prod déjà configurée sans sauvegarde (écrase la config Nginx du site).
# =============================================================================
set -euo pipefail

[[ "${EUID:-0}" -eq 0 ]] || { echo "Lancez en root (sudo -i ou ssh root@…)"; exit 1; }

export DEBIAN_FRONTEND=noninteractive

APP_URL="${APP_URL:-http://62.238.16.89}"
ASSET_URL="${ASSET_URL:-$APP_URL}"
APP_ROOT="${APP_ROOT:-/var/www/locaged-v2}"
BRANCH="${BRANCH:-demo-v2}"
REPO_URL="${REPO_URL:-https://github.com/mehdiouhrou/locaged-final-master.git}"
DB_NAME="${DB_NAME:-locaged_v2}"
DB_USER="${DB_USER:-locaged_v2}"
DB_PASSWORD="${DB_PASSWORD:-$(openssl rand -base64 32 | tr -dc 'a-zA-Z0-9' | head -c 24)}"
PHP_VER="${PHP_VER:-8.2}"

log() { echo -e "\033[0;32m[locaged-v2]\033[0m $*"; }

log "Mise à jour des paquets…"
apt-get update -qq
apt-get upgrade -y -qq

log "Installation Nginx, MySQL, Redis, PHP ${PHP_VER}, Git, dépendances…"
apt-get install -y -qq nginx mysql-server redis-server git curl unzip \
  software-properties-common ca-certificates gnupg lsb-release

add-apt-repository -y ppa:ondrej/php >/dev/null 2>&1 || true
apt-get update -qq
apt-get install -y -qq \
  "php${PHP_VER}-fpm" "php${PHP_VER}-cli" "php${PHP_VER}-mysql" "php${PHP_VER}-redis" \
  "php${PHP_VER}-curl" "php${PHP_VER}-gd" "php${PHP_VER}-imagick" "php${PHP_VER}-mbstring" \
  "php${PHP_VER}-xml" "php${PHP_VER}-zip" "php${PHP_VER}-bcmath" "php${PHP_VER}-intl"

sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 100M/' "/etc/php/${PHP_VER}/fpm/php.ini"
sed -i 's/^post_max_size = .*/post_max_size = 100M/' "/etc/php/${PHP_VER}/fpm/php.ini"
sed -i 's/^memory_limit = .*/memory_limit = 512M/' "/etc/php/${PHP_VER}/fpm/php.ini"

systemctl enable --now nginx "php${PHP_VER}-fpm" mysql redis-server

if ! command -v composer >/dev/null 2>&1; then
  log "Installation Composer…"
  curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

if ! command -v node >/dev/null 2>&1 || ! node -v | grep -q '^v20\.'; then
  log "Installation Node.js 20…"
  curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
  apt-get install -y -qq nodejs
fi

log "Base MySQL + utilisateur…"
mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';"
mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost'; FLUSH PRIVILEGES;"

log "Code source (${BRANCH})…"
mkdir -p "$(dirname "$APP_ROOT")"
if [[ -d "${APP_ROOT}/.git" ]]; then
  git -C "$APP_ROOT" fetch origin
  git -C "$APP_ROOT" checkout "$BRANCH"
  git -C "$APP_ROOT" pull origin "$BRANCH"
else
  rm -rf "$APP_ROOT"
  git clone --branch "$BRANCH" --single-branch --depth 1 "$REPO_URL" "$APP_ROOT"
fi

cd "$APP_ROOT"

log "Fichier .env…"
cp -f .env.example .env

# Remplacements sûrs (sed)
HOST_NO_SCHEME="${APP_URL#http://}"
HOST_NO_SCHEME="${HOST_NO_SCHEME#https://}"

sed -i "s|^APP_URL=.*|APP_URL=${APP_URL}|" .env
sed -i "s|^ASSET_URL=.*|ASSET_URL=${ASSET_URL}|" .env
sed -i 's/^APP_ENV=.*/APP_ENV=production/' .env
sed -i 's/^APP_DEBUG=.*/APP_DEBUG=false/' .env
sed -i "s/^DB_DATABASE=.*/DB_DATABASE=${DB_NAME}/" .env
sed -i "s/^DB_USERNAME=.*/DB_USERNAME=${DB_USER}/" .env
sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD}|" .env
sed -i 's/^SCOUT_DRIVER=.*/SCOUT_DRIVER=null/' .env
grep -q '^SESSION_DRIVER=' .env && sed -i 's/^SESSION_DRIVER=.*/SESSION_DRIVER=database/' .env || echo 'SESSION_DRIVER=database' >> .env

log "Composer + npm build (peut prendre plusieurs minutes)…"
composer install --no-dev --optimize-autoloader --no-interaction --no-ansi
npm ci --silent
npm run build

log "Clé + init (migrations, rôles, admin@example.com)…"
php artisan key:generate --force
php artisan storage:link 2>/dev/null || true
php artisan init:env

log "Caches production…"
php artisan config:cache
php artisan route:cache
php artisan view:cache

chown -R www-data:www-data "$APP_ROOT/storage" "$APP_ROOT/bootstrap/cache"

FPM_SOCK="/var/run/php/php${PHP_VER}-fpm.sock"
[[ -S "$FPM_SOCK" ]] || FPM_SOCK="$(ls /var/run/php/php*-fpm.sock 2>/dev/null | head -1 || true)"
[[ -S "$FPM_SOCK" ]] || { echo "Socket PHP-FPM introuvable"; exit 1; }

log "Nginx…"
NGX_SITE="/etc/nginx/sites-available/locaged-v2"
cat >"$NGX_SITE" <<NGX
server {
    listen 80;
    server_name ${HOST_NO_SCHEME};
    root ${APP_ROOT}/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    index index.php;
    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php\$ {
        fastcgi_pass unix:${FPM_SOCK};
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGX

ln -sf "$NGX_SITE" /etc/nginx/sites-enabled/locaged-v2
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx
systemctl reload "php${PHP_VER}-fpm"

log "Terminé."
echo ""
echo "═══════════════════════════════════════════════════════════════"
echo "  URL : ${APP_URL}"
echo "  Dossier  : ${APP_ROOT}"
echo "  MySQL DB : ${DB_NAME} / user ${DB_USER}"
echo "  MySQL MDP: ${DB_PASSWORD}"
echo ""
echo "  Connexion applicative : admin@example.com / password"
echo "  (changez ce mot de passe tout de suite après le1er login)"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "Pare-feu (optionnel, après vérification SSH) :"
echo "  ufw allow OpenSSH && ufw allow 80/tcp && ufw allow 443/tcp && ufw enable"
