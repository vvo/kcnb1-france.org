#!/usr/bin/env bash
#
# Replicate production locally. READS PRODUCTION ONLY — never writes to it.
#   - dumps the prod DB over SSH (creds read live from prod wp-config, never stored)
#     and imports it into the local Docker MySQL, then rewrites URLs to the local host
#   - rsyncs prod uploads down (download only)
#
# Usage:
#   scripts/sync-from-prod.sh            # DB + uploads
#   scripts/sync-from-prod.sh --db       # DB only
#   scripts/sync-from-prod.sh --uploads  # uploads only
#   scripts/sync-from-prod.sh --yes      # skip the "overwrite local DB?" prompt

source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/_config.sh"
cd "$ROOT"

DO_DB=1; DO_UPLOADS=1; ASSUME_YES=0
if [ "${1:-}" = "--db" ]; then DO_UPLOADS=0; shift; fi
if [ "${1:-}" = "--uploads" ]; then DO_DB=0; shift; fi
[ "${1:-}" = "--yes" ] && ASSUME_YES=1

step() { printf '\n\033[1;34m==>\033[0m %s\n' "$1"; }
wp() { docker compose exec -T php php /tmp/wp-cli.phar --allow-root --path=/wordpress "$@"; }

if [ "$DO_DB" = 1 ]; then
  step "Dumping prod DB over SSH (read-only on prod)"
  ssh "$PROD_SSH" 'bash -s' "$PROD_WWW" > prod-db.sql.gz <<'REMOTE'
    set -e
    cd "$HOME/$1"
    cfg=wp-config.php
    DB_NAME=$(sed -n "s/.*'DB_NAME',[[:space:]]*'\([^']*\)'.*/\1/p" "$cfg")
    DB_USER=$(sed -n "s/.*'DB_USER',[[:space:]]*'\([^']*\)'.*/\1/p" "$cfg")
    DB_PASSWORD=$(sed -n "s/.*'DB_PASSWORD',[[:space:]]*'\([^']*\)'.*/\1/p" "$cfg")
    DB_HOST=$(sed -n "s/.*'DB_HOST',[[:space:]]*'\([^']*\)'.*/\1/p" "$cfg")
    mysqldump --no-tablespaces --default-character-set=utf8mb4 --add-drop-table \
      -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" 2>/dev/null | gzip
REMOTE
  echo "  downloaded $(du -h prod-db.sql.gz | cut -f1)"

  if [ "$ASSUME_YES" != 1 ] && [ -t 0 ]; then
    read -r -p "This overwrites the local 'wordpress' database. Continue? [y/N] " a
    [[ "$a" =~ ^[Yy]$ ]] || { echo "Aborted."; exit 1; }
  fi

  step "Importing into local DB"
  gunzip -c prod-db.sql.gz | db_mysql wordpress

  step "Ensuring wp-cli is available in the php container"
  docker compose exec -T php sh -c 'test -f /tmp/wp-cli.phar || curl -sSL -o /tmp/wp-cli.phar https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar'

  step "Rewriting ${PROD_URL} -> ${LOCAL_URL} (serialize-safe, guid left intact)"
  wp search-replace "$PROD_URL" "$LOCAL_URL" --all-tables-with-prefix --skip-columns=guid --report-changed-only 2>/dev/null || true
  wp search-replace "${PROD_URL/https:/http:}" "$LOCAL_URL" --all-tables-with-prefix --skip-columns=guid --report-changed-only 2>/dev/null || true
fi

if [ "$DO_UPLOADS" = 1 ]; then
  step "Downloading prod uploads (read-only on prod, resumable)"
  mkdir -p wordpress/wp-content/uploads
  # -rtz only: no perms/owner copied (local umask applies), works with macOS openrsync.
  rsync -rtz --partial --progress \
    -e "ssh -o ConnectTimeout=20" \
    "${PROD_SSH}:${PROD_WWW}/wp-content/uploads/" wordpress/wp-content/uploads/
  echo "  local uploads: $(du -sh wordpress/wp-content/uploads | cut -f1)"
fi

step "Done — ${LOCAL_URL}"
