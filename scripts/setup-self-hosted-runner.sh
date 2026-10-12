#!/usr/bin/env bash
# Полная установка self-hosted GitHub Actions runner + подготовка деплоя.
#
# На сервере (Linux), из-под sudo:
#
#   # Вариант A — PAT с правом admin на репо (токен регистрации получит сам):
#   export GH_TOKEN='ghp_xxxxxxxx'   # или github_pat_...
#   sudo -E bash scripts/setup-self-hosted-runner.sh
#
#   # Вариант B — токен с страницы New runner (~1 час):
#   sudo bash scripts/setup-self-hosted-runner.sh --token 'AAAAA...'
#
# После успеха: GitHub → Settings → Actions → Runners = Idle
#               Actions → Deploy to server → Run workflow

set -euo pipefail

OWNER="${GITHUB_OWNER:-kav152}"
REPO="${GITHUB_REPO:-inventory-accounting}"
REPO_URL="${REPO_URL:-https://github.com/${OWNER}/${REPO}}"
APP_DIR="${APP_DIR:-/var/www/inventory-accounting}"
RUNNER_DIR="${RUNNER_DIR:-/opt/actions-runner}"
RUNNER_VERSION="${RUNNER_VERSION:-2.321.0}"
LABELS="${RUNNER_LABELS:-self-hosted,linux,x64}"
BRANCH="${DEPLOY_BRANCH:-main}"

REG_TOKEN=""
GH_API_TOKEN="${GH_TOKEN:-${GITHUB_TOKEN:-}}"

usage() {
  cat <<'EOF'
Usage:
  sudo -E bash scripts/setup-self-hosted-runner.sh
  sudo bash scripts/setup-self-hosted-runner.sh --token '<runner-registration-token>'
  sudo -E bash scripts/setup-self-hosted-runner.sh --gh-token '<pat>'

Env:
  GH_TOKEN / GITHUB_TOKEN  — PAT Admin на репозиторий (сам запросит registration token)
  APP_DIR                  — каталог приложения (default /var/www/inventory-accounting)
  RUNNER_DIR               — куда ставить runner (default /opt/actions-runner)
  RUNNER_USER              — пользователь сервиса (default: кто вызвал sudo)
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --token)
      REG_TOKEN="${2:-}"; shift 2 ;;
    --gh-token)
      GH_API_TOKEN="${2:-}"; shift 2 ;;
    -h|--help)
      usage; exit 0 ;;
    *)
      echo "Unknown arg: $1"; usage; exit 1 ;;
  esac
done

if [[ "$(id -u)" -ne 0 ]]; then
  echo "ERROR: нужен sudo"
  exit 1
fi

RUNNER_USER="${RUNNER_USER:-${SUDO_USER:-}}"
if [[ -z "$RUNNER_USER" || "$RUNNER_USER" == "root" ]]; then
  # не оставляем сервис от root без явного указания
  if id -u "www-data" >/dev/null 2>&1; then
    RUNNER_USER="www-data"
  else
    echo "ERROR: задайте RUNNER_USER=ваш_логин"
    exit 1
  fi
fi

log() { echo "==> $*"; }

need_cmd() {
  command -v "$1" >/dev/null 2>&1 || {
    log "ставлю пакеты (curl tar jq)…"
    if command -v apt-get >/dev/null 2>&1; then
      apt-get update -y
      DEBIAN_FRONTEND=noninteractive apt-get install -y curl tar ca-certificates jq
    elif command -v yum >/dev/null 2>&1; then
      yum install -y curl tar ca-certificates jq
    else
      echo "ERROR: установите curl tar jq вручную"
      exit 1
    fi
  }
}

need_cmd curl
need_cmd tar
command -v jq >/dev/null 2>&1 || need_cmd jq

fetch_registration_token() {
  local api="https://api.github.com/repos/${OWNER}/${REPO}/actions/runners/registration-token"
  log "запрашиваю registration token через API…"
  local resp http
  resp="$(curl -sS -w '\n%{http_code}' -X POST \
    -H "Authorization: Bearer ${GH_API_TOKEN}" \
    -H "Accept: application/vnd.github+json" \
    -H "X-GitHub-Api-Version: 2022-11-28" \
    "$api")"
  http="$(echo "$resp" | tail -n1)"
  body="$(echo "$resp" | sed '$d')"
  if [[ "$http" != "201" && "$http" != "200" ]]; then
    echo "ERROR: GitHub API HTTP $http"
    echo "$body"
    echo "Нужен PAT с admin на репозиторий, либо передайте --token с страницы New runner."
    exit 1
  fi
  REG_TOKEN="$(echo "$body" | jq -r .token)"
  local exp
  exp="$(echo "$body" | jq -r .expires_at)"
  if [[ -z "$REG_TOKEN" || "$REG_TOKEN" == "null" ]]; then
    echo "ERROR: пустой token в ответе API"
    exit 1
  fi
  log "registration token получен, expires_at=$exp (~1 час)"
}

if [[ -z "$REG_TOKEN" ]]; then
  if [[ -n "$GH_API_TOKEN" ]]; then
    fetch_registration_token
  else
    echo "ERROR: нет токена."
    echo "Либо: export GH_TOKEN='ghp_...' && sudo -E bash $0"
    echo "Либо: sudo bash $0 --token '...'  (Settings→Actions→Runners→New, ~1 час)"
    exit 1
  fi
fi

# --- приложение: git clone/pull ---
log "приложение: $APP_DIR"
mkdir -p "$(dirname "$APP_DIR")"
if [[ ! -d "$APP_DIR/.git" ]]; then
  if [[ -d "$APP_DIR" ]] && [[ -n "$(ls -A "$APP_DIR" 2>/dev/null || true)" ]]; then
    echo "ERROR: $APP_DIR существует без .git — разберите вручную или укажите другой APP_DIR"
    exit 1
  fi
  log "клонирую $REPO_URL → $APP_DIR"
  git clone "$REPO_URL" "$APP_DIR"
fi

cd "$APP_DIR"
git fetch origin --prune
git checkout "$BRANCH"
git pull --ff-only origin "$BRANCH" || true

if [[ ! -f .env ]]; then
  if [[ -f .env.example ]]; then
    log "WARNING: нет .env — копирую .env.example (заполните DB_*_SQL!)"
    cp .env.example .env
  else
    log "WARNING: нет .env"
  fi
fi

# --- runner ---
log "runner → $RUNNER_DIR (user=$RUNNER_USER)"
mkdir -p "$RUNNER_DIR"
cd "$RUNNER_DIR"

TGZ="actions-runner-linux-x64-${RUNNER_VERSION}.tar.gz"
URL="https://github.com/actions/runner/releases/download/v${RUNNER_VERSION}/${TGZ}"
if [[ ! -f ./config.sh ]]; then
  log "скачиваю $URL"
  curl -fsSL -o "$TGZ" "$URL"
  tar xzf "$TGZ"
  rm -f "$TGZ"
fi

# снять старую регистрацию если есть
if [[ -f .runner ]]; then
  log "удаляю предыдущую регистрацию runner…"
  sudo -u "$RUNNER_USER" ./config.sh remove --token "$REG_TOKEN" 2>/dev/null || true
fi

id -u "$RUNNER_USER" >/dev/null 2>&1 || useradd -m -s /bin/bash "$RUNNER_USER"
chown -R "$RUNNER_USER:$RUNNER_USER" "$RUNNER_DIR"

if getent group www-data >/dev/null 2>&1; then
  usermod -aG www-data "$RUNNER_USER" || true
fi

# права на деплой в APP_DIR
if getent group www-data >/dev/null 2>&1; then
  chown -R "$RUNNER_USER:www-data" "$APP_DIR" || true
else
  chown -R "$RUNNER_USER:$RUNNER_USER" "$APP_DIR" || true
fi
chmod -R u+rwX,g+rwX "$APP_DIR" || true
if [[ -f "$APP_DIR/.env" ]]; then
  chmod 640 "$APP_DIR/.env"
fi

HOST_NAME="$(hostname -s 2>/dev/null || hostname)"
log "регистрирую runner ${HOST_NAME}-inventory…"
sudo -u "$RUNNER_USER" bash -lc "cd '$RUNNER_DIR' && ./config.sh --unattended \
  --url '$REPO_URL' \
  --token '$REG_TOKEN' \
  --name '${HOST_NAME}-inventory' \
  --labels '$LABELS' \
  --work '_work' \
  --replace"

# systemd
if [[ -f ./svc.sh ]]; then
  log "systemd service…"
  # остановить старый если был
  ./svc.sh stop 2>/dev/null || true
  ./svc.sh uninstall 2>/dev/null || true
  ./svc.sh install "$RUNNER_USER"
  ./svc.sh start
  sleep 2
  ./svc.sh status || true
else
  log "WARNING: нет svc.sh — запустите вручную: ./run.sh"
fi

log "проверка labels / процесс"
ps aux | grep -E '[a]ctions.runner|[R]unner.Listener' || true

cat <<EOF

======== ГОТОВО ========
Runner:   $RUNNER_DIR
App:      $APP_DIR @ $(cd "$APP_DIR" && git rev-parse --short HEAD)
User:     $RUNNER_USER

1) https://github.com/${OWNER}/${REPO}/settings/actions/runners  → Idle/Online
2) https://github.com/${OWNER}/${REPO}/actions/workflows/deploy.yml → Run workflow
3) Лог деплоя на сервере: $APP_DIR/src/storage/logs/deploy.log

Если .env пустой — заполните только:
  DB_HOST_SQL / DB_NAME_SQL / DB_USER_SQL / DB_PASSWORD_SQL
EOF
