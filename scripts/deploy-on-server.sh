#!/usr/bin/env bash
# Запуск на сервере (self-hosted runner или вручную из каталога приложения).
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/inventory-accounting}"
WEB_USER="${WEB_USER:-www-data}"
WEB_GROUP="${WEB_GROUP:-www-data}"
BRANCH="${DEPLOY_BRANCH:-main}"
LOG_DIR="${APP_DIR}/src/storage/logs"
LOG_FILE="${LOG_DIR}/deploy.log"

mkdir -p "$LOG_DIR"
touch "$LOG_FILE"
exec > >(tee -a "$LOG_FILE") 2>&1

echo "======== $(date '+%Y-%m-%d %H:%M:%S %z') deploy start ========"
echo "APP_DIR=$APP_DIR BRANCH=$BRANCH"

cd "$APP_DIR"

# .env не трогаем (секреты только на сервере)
if [[ ! -f .env ]]; then
  echo "WARNING: .env отсутствует — скопируйте из .env.example и заполните DB_*_SQL"
fi

git fetch origin --prune
git checkout "$BRANCH"
git pull --ff-only "origin" "$BRANCH"

# PHP-зависимости (если есть composer)
if [[ -f composer.json ]]; then
  if command -v composer >/dev/null 2>&1; then
    composer install --no-dev --optimize-autoloader --no-interaction
  elif [[ -f composer.phar ]]; then
    php composer.phar install --no-dev --optimize-autoloader --no-interaction
  else
    echo "WARNING: composer не найден, vendor не обновлён"
  fi
fi

# Права: код читает www-data, .env только для владельца/группы
chown -R "${WEB_USER}:${WEB_GROUP}" "$APP_DIR" || true
find "$APP_DIR" -type d -exec chmod 755 {} \;
find "$APP_DIR" -type f -exec chmod 644 {} \;
chmod 750 "$APP_DIR" || true
if [[ -f .env ]]; then
  chmod 640 .env
  chown "${WEB_USER}:${WEB_GROUP}" .env
fi
chmod 775 "$LOG_DIR" || true
chmod +x scripts/*.sh 2>/dev/null || true

# Метка успешного деплоя
echo "$(date '+%Y-%m-%dT%H:%M:%S%z') $(git rev-parse --short HEAD)" > DEPLOY_STAMP.txt
chown "${WEB_USER}:${WEB_GROUP}" DEPLOY_STAMP.txt || true

echo "Deployed commit: $(git rev-parse --short HEAD)"
echo "======== $(date '+%Y-%m-%d %H:%M:%S %z') deploy ok ========"
