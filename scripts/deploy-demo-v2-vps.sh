#!/usr/bin/env bash
# =============================================================================
# LocaGed — déploiement branche demo-v2 sur VPS (Ubuntu, PHP-FPM + Nginx)
#
# Usage (sur le serveur, en root ou avec sudo pour chown) :
#   export APP_ROOT=/var/www/locaged-v2
#   export REPO_URL=https://github.com/mehdiouhrou/locaged-final-master.git
#   bash scripts/deploy-demo-v2-vps.sh
#
# Prérequis : git, composer, php8.2+ (cli), node20+, nginx, mysql/mariadb,
#             redis (recommandé pour Horizon), Typesense (si SCOUT_DRIVER=typesense).
# Premier déploiement : copier .env, php artisan key:generate, configurer DB/Redis.
# =============================================================================
set -euo pipefail

APP_ROOT="${APP_ROOT:-/var/www/locaged-v2}"
BRANCH="${BRANCH:-demo-v2}"
REPO_URL="${REPO_URL:-https://github.com/mehdiouhrou/locaged-final-master.git}"
WEB_USER="${WEB_USER:-www-data}"

log() { echo "[deploy-demo-v2] $*"; }

if [[ ! -d "$APP_ROOT/.git" ]]; then
  log "Clone initial dans $APP_ROOT"
  mkdir -p "$(dirname "$APP_ROOT")"
  git clone --branch "$BRANCH" --single-branch "$REPO_URL" "$APP_ROOT"
fi

cd "$APP_ROOT"

log "git fetch + checkout $BRANCH"
git fetch origin
git checkout "$BRANCH"
git pull origin "$BRANCH"

if [[ ! -f .env ]]; then
  log "ATTENTION: pas de .env — copiez .env.example vers .env et configurez (DB, APP_URL, Redis, Typesense)."
  if [[ -f .env.example ]]; then
    cp .env.example .env
    log "Fichier .env créé depuis .env.example — éditez-le avant la prod."
  fi
fi

log "composer install"
composer install --no-dev --optimize-autoloader --no-interaction

if command -v npm >/dev/null 2>&1; then
  log "npm ci + build"
  npm ci
  npm run build
else
  log "npm absent — installez Node20+ ou lancez npm run build ailleurs et synchronisez public/build"
fi

log "artisan (storage, migrate, caches)"
php artisan storage:link 2>/dev/null || true

if ! grep -q '^APP_KEY=.\+' .env 2>/dev/null; then
  log "APP_KEY vide — génération"
  php artisan key:generate --force
fi

php artisan migrate --force

if [[ "${APP_ENV:-production}" == "production" ]] || grep -q '^APP_ENV=production' .env 2>/dev/null; then
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
else
  php artisan config:clear
  php artisan route:clear
  php artisan view:clear
fi

log "permissions storage / bootstrap/cache"
chown -R "$WEB_USER:$WEB_USER" storage bootstrap/cache 2>/dev/null || {
  log "chown a échoué (lancez en sudo) : sudo chown -R $WEB_USER:$WEB_USER storage bootstrap/cache"
}

php artisan queue:restart 2>/dev/null || true

log "Terminé. Vérifiez Nginx, PHP-FPM, supervisor/Horizon, cron (schedule:run). Voir docs/vps-deploy-demo-v2.md"
