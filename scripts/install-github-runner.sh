#!/usr/bin/env bash
# Установка GitHub self-hosted runner на Linux-сервере.
#
# 1) Откройте (нужны права Admin на репозиторий):
#    https://github.com/kav152/inventory-accounting/settings/actions/runners/new
#    Linux → x64 → скопируйте токен из команды ./config.sh (живёт ~1 час!)
#
# 2) На сервере:
#    sudo bash scripts/install-github-runner.sh 'ВАШ_ТОКЕН_С_GITHUB'
#
# Либо из /tmp после скачивания этого скрипта с raw.githubusercontent.com

set -euo pipefail

REPO_URL="${REPO_URL:-https://github.com/kav152/inventory-accounting}"
RUNNER_DIR="${RUNNER_DIR:-/opt/actions-runner}"
RUNNER_USER="${RUNNER_USER:-${SUDO_USER:-$USER}}"
APP_DIR="${APP_DIR:-/var/www/inventory-accounting}"
LABELS="${RUNNER_LABELS:-self-hosted,linux,x64}"

TOKEN="${1:-}"
if [[ -z "$TOKEN" ]]; then
  echo "Usage: sudo bash $0 '<registration-token-from-github>'"
  echo "Токен: Settings → Actions → Runners → New self-hosted runner (срок ~1 час)"
  exit 1
fi

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Запустите через sudo"
  exit 1
fi

echo "==> Каталог runner: $RUNNER_DIR (user=$RUNNER_USER)"
mkdir -p "$RUNNER_DIR"
cd "$RUNNER_DIR"

# Актуальный пакет x64 (при смене версии GitHub обновит цифры на странице New runner)
RUNNER_VERSION="${RUNNER_VERSION:-2.321.0}"
TGZ="actions-runner-linux-x64-${RUNNER_VERSION}.tar.gz"
if [[ ! -f "$TGZ" ]]; then
  echo "==> Скачиваю $TGZ"
  curl -fsSL -o "$TGZ" \
    "https://github.com/actions/runner/releases/download/v${RUNNER_VERSION}/actions-runner-linux-x64-${RUNNER_VERSION}.tar.gz"
fi

if [[ ! -f ./config.sh ]]; then
  tar xzf "$TGZ"
fi

chown -R "$RUNNER_USER:$RUNNER_USER" "$RUNNER_DIR"

# Права на каталог приложения для деплоя
if id -u "$RUNNER_USER" >/dev/null 2>&1 && [[ -d "$APP_DIR" ]]; then
  if getent group www-data >/dev/null 2>&1; then
    usermod -aG www-data "$RUNNER_USER" || true
  fi
  # чтобы git pull / chmod работали
  chown -R "$RUNNER_USER:www-data" "$APP_DIR" || chown -R "$RUNNER_USER:$RUNNER_USER" "$APP_DIR" || true
fi

echo "==> Регистрация в GitHub (токен одноразовый/~1ч)"
sudo -u "$RUNNER_USER" bash -lc "cd '$RUNNER_DIR' && ./config.sh --unattended \
  --url '$REPO_URL' \
  --token '$TOKEN' \
  --name \"\$(hostname)-inventory\" \
  --labels '$LABELS' \
  --work '_work' \
  --replace"

echo "==> Установка systemd-сервиса"
cd "$RUNNER_DIR"
./svc.sh install "$RUNNER_USER"
./svc.sh start

echo "==> Статус"
./svc.sh status || systemctl status actions.runner.* --no-pager || true

echo "Готово. Проверьте: GitHub → Settings → Actions → Runners (должен быть Idle/Online)"
echo "Затем: Actions → Deploy to server → Run workflow"
