#!/usr/bin/env bash
# Обёртка: полный сетап в setup-self-hosted-runner.sh
exec bash "$(cd "$(dirname "$0")" && pwd)/setup-self-hosted-runner.sh" "$@"
