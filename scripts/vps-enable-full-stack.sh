#!/usr/bin/env bash
# =============================================================================
# LocaGed — active la pile « complète » sur le VPS (Ubuntu 22.04+, root) :
#   - Tesseract (OCR)
#   - ClamAV + freshclam (antivirus upload, via clamscan CLI)
#   - Typesense (Docker) + Scout
#   - Redis : queues, cache, sessions
#   - Laravel Horizon (supervisor)
#   - Règles workflow par défaut + GED_ENFORCE_WORKFLOW_RULES=true
#   - Indexation initiale Typesense (commande artisan)
#
# Usage :
#   export APP_ROOT=/var/www/locaged-v2
#   bash scripts/vps-enable-full-stack.sh
#
# Prérequis : dépôt déjà déployé, .env avec DB OK, PHP CLI (même version que FPM).
# =============================================================================
set -euo pipefail

[[ "${EUID:-0}" -eq 0 ]] || { echo "Exécuter en root (sudo -i)."; exit 1; }

export DEBIAN_FRONTEND=noninteractive

APP_ROOT="${APP_ROOT:-/var/www/locaged-v2}"
TYPESENSE_IMAGE="${TYPESENSE_IMAGE:-typesense/typesense:0.25.2}"

log() { echo -e "\033[0;32m[locaged-full]\033[0m $*"; }
warn() { echo -e "\033[0;33m[locaged-full]\033[0m $*"; }

[[ -f "${APP_ROOT}/artisan" ]] || { echo "APP_ROOT invalide : ${APP_ROOT}"; exit 1; }

PHPVER="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
apt-get update -qq

log "Redis + extension php${PHPVER}-redis…"
if ! systemctl is-active --quiet redis-server 2>/dev/null; then
  apt-get install -y -qq redis-server
  systemctl enable --now redis-server
fi
apt-get install -y -qq "php${PHPVER}-redis"
systemctl reload "php${PHPVER}-fpm" 2>/dev/null || true

log "Paquets : Tesseract, ClamAV, Supervisor, Docker…"
apt-get install -y -qq \
  tesseract-ocr tesseract-ocr-fra tesseract-ocr-eng tesseract-ocr-ara \
  clamav clamav-daemon \
  supervisor \
  docker.io

systemctl enable --now docker

log "Mise à jour signatures ClamAV (peut prendre 1–5 min la première fois)…"
systemctl stop clamav-freshclam 2>/dev/null || true
freshclam || log "freshclam partiel — relancer plus tard : freshclam && systemctl start clamav-freshclam"
systemctl enable clamav-freshclam 2>/dev/null || true
systemctl start clamav-freshclam 2>/dev/null || true

log "Typesense (Docker)…"
TS_KEY="$(grep -E '^TYPESENSE_API_KEY=' "${APP_ROOT}/.env" 2>/dev/null | sed 's/^TYPESENSE_API_KEY=//' | tr -d '"' || true)"
TS_KEY="${TS_KEY//$'\r'/}"
TS_KEY="$(echo -n "$TS_KEY" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')"
if [[ -z "$TS_KEY" || "$TS_KEY" == "xyz" ]]; then
  TS_KEY="$(openssl rand -hex 24)"
  warn "Nouvelle clé Typesense générée (enregistrée dans .env) — conservez-la hors dépôt."
fi

docker rm -f typesense 2>/dev/null || true
docker run -d --name typesense --restart unless-stopped \
  -p 127.0.0.1:8108:8108 \
  -v typesense-data:/data \
  "${TYPESENSE_IMAGE}" \
  --data-dir /data --api-key="${TS_KEY}" --enable-cors

sleep 3
if ! curl -sf "http://127.0.0.1:8108/health" >/dev/null; then
  log "ATTENTION : Typesense ne répond pas sur :8108 — vérifiez docker logs typesense"
else
  log "Typesense OK (localhost:8108)"
fi

log "Mise à jour .env (Redis, Scout, ClamAV, workflow)…"
ENV_FILE="${APP_ROOT}/.env"
touch "$ENV_FILE"

set_kv() {
  local key="$1" val="$2"
  if grep -q "^${key}=" "$ENV_FILE"; then
    sed -i "s|^${key}=.*|${key}=${val}|" "$ENV_FILE"
  else
    echo "${key}=${val}" >> "$ENV_FILE"
  fi
}

set_kv "QUEUE_CONNECTION" "redis"
set_kv "CACHE_STORE" "redis"
set_kv "SESSION_DRIVER" "redis"
set_kv "SCOUT_DRIVER" "typesense"
set_kv "TYPESENSE_HOST" "127.0.0.1"
set_kv "TYPESENSE_PORT" "8108"
set_kv "TYPESENSE_PROTOCOL" "http"
set_kv "TYPESENSE_API_KEY" "${TS_KEY}"
set_kv "CLAMAV_ENABLED" "true"
set_kv "CLAMAV_BINARY" "clamscan"
set_kv "GED_ENFORCE_WORKFLOW_RULES" "true"

log "Artisan (cache, règles workflow, Horizon, index Typesense)…"
cd "$APP_ROOT"
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan ged:seed-default-workflow-rules || warn "ged:seed-default-workflow-rules : déjà exécuté ou erreur (voir sortie ci-dessus)"
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan queue:restart || true

if ! sudo -u www-data php artisan app:import-to-typesense --include-empty-ocr; then
  warn "Import Typesense : échec ou aucun document indexable — relancer après migration / données : php artisan app:import-to-typesense --include-empty-ocr"
fi

log "Supervisor — Horizon…"
HORIZON_CONF="/etc/supervisor/conf.d/locaged-horizon.conf"
cat >"$HORIZON_CONF" <<EOF
[program:locaged-horizon]
process_name=%(program_name)s
command=php ${APP_ROOT}/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=${APP_ROOT}/storage/logs/horizon.log
stopwaitsecs=3600
EOF

mkdir -p "${APP_ROOT}/storage/logs"
chown -R www-data:www-data "${APP_ROOT}/storage" "${APP_ROOT}/bootstrap/cache"

supervisorctl reread
supervisorctl update
supervisorctl restart locaged-horizon || supervisorctl start locaged-horizon

log "Cron scheduler (/etc/cron.d/locaged-scheduler)…"
cat >/etc/cron.d/locaged-scheduler <<CRON
* * * * * www-data cd ${APP_ROOT} && php artisan schedule:run >> /dev/null 2>&1

CRON
chmod 644 /etc/cron.d/locaged-scheduler

log "Terminé."
echo ""
echo "Résumé :"
echo "  - OCR : Tesseract installé ; jobs traités par Horizon (Redis)"
echo "  - ClamAV   : activé dans .env ; signatures via freshclam"
echo "  - Typesense: Docker typesense, Scout driver=typesense"
echo "  - Horizon  : supervisorctl status locaged-horizon"
echo "  - Workflow : GED_ENFORCE_WORKFLOW_RULES=true (règles par département seedées)"
echo ""
echo "Vérifications :"
echo "  supervisorctl status locaged-horizon"
echo "  curl -s http://127.0.0.1:8108/health"
echo "  journalctl -u supervisor -n 50 --no-pager   # ou tail -f ${APP_ROOT}/storage/logs/horizon.log"
