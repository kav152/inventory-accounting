#!/usr/bin/env bash
# Запуск на сервере из корня проекта:
#   bash scripts/create-env-on-server.sh
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

if [[ -f .env ]]; then
  echo ".env уже есть: $ROOT/.env"
  exit 0
fi

if [[ ! -f .env.example ]]; then
  echo "Нет .env.example — сначала git pull"
  exit 1
fi

cp .env.example .env
echo "Создан $ROOT/.env из .env.example"
echo "Откройте файл и укажите DB_PASSWORD_SQL:"
echo "  nano $ROOT/.env"
echo "Права:"
echo "  chmod 640 .env"
echo "  chown www-data:www-data .env   # или пользователь вашего PHP-FPM"
