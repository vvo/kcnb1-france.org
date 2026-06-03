#!/usr/bin/env bash
# Shared config for the local-dev scripts. Sourced by setup.sh and sync-from-prod.sh.
# Nothing here ever writes to production.

set -euo pipefail

# --- Production (READ ONLY, never deploy) ---
PROD_SSH="kcnbfrh@sshcloud.cluster024.hosting.ovh.net"
PROD_WWW="www"                       # path to the WordPress root on prod, relative to $HOME
PROD_URL="https://kcnb1-france.org"  # live URL, rewritten to LOCAL_URL on import

# --- Local ---
LOCAL_HOST="kcnb1-france.localhost"  # portless hostname
LOCAL_URL="https://${LOCAL_HOST}"
PORTLESS_NAME="kcnb1-france"         # portless alias name -> https://kcnb1-france.localhost
NGINX_PORT="44000"                   # host port docker publishes nginx on, behind portless
NODE_VERSION="14.18.0"               # pinned for the old Sage 9 / webpack 3 theme build
THEME_DIR="wordpress/wp-content/themes/kcnb1"

# Repo root, regardless of where the script is called from.
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

# Run a command in the db container via compose (no hardcoded container name).
db() { docker compose exec -T db "$@"; }
# mysql/mysqldump as root (local-only password from docker-compose.yml).
db_mysql() { db mysql -u root -ppassword --default-character-set=utf8mb4 "$@"; }

# Pick a node that can run portless (>=18). The repo's .nvmrc pins node 14 for the
# theme build, which is too old for portless, so resolve a separate one via fnm.
portless_cmd() {
  if command -v portless >/dev/null 2>&1; then
    portless "$@"
  elif command -v fnm >/dev/null 2>&1; then
    fnm exec --using=20 npx -y portless@0.13.0 "$@"
  else
    npx -y portless@0.13.0 "$@"
  fi
}
