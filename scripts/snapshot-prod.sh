#!/usr/bin/env bash
#
# Rollback point before any production change. READS PRODUCTION ONLY.
# Saves the prod DB and all code (everything except uploads and caches) under
# .prod-snapshots/<timestamp>/ on this machine.
#
# Usage: scripts/snapshot-prod.sh

source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/_config.sh"
cd "$ROOT"

dir=".prod-snapshots/$(date +%Y%m%d-%H%M%S)"
mkdir -p "$dir"

echo "==> DB -> $dir/db.sql.gz"
ssh -o ConnectTimeout=20 "$PROD_SSH" 'bash -s' "$PROD_WWW" > "$dir/db.sql.gz" 2>/dev/null <<'REMOTE'
  set -e
  cd "$HOME/$1"
  get() { sed -n "s/.*'$1',[[:space:]]*'\([^']*\)'.*/\1/p" wp-config.php; }
  mysqldump --no-tablespaces --single-transaction --default-character-set=utf8mb4 \
    -h "$(get DB_HOST)" -u "$(get DB_USER)" -p"$(get DB_PASSWORD)" "$(get DB_NAME)" | gzip
REMOTE
gunzip -c "$dir/db.sql.gz" | tail -1 | grep -q "Dump completed" || { echo "DB dump incomplete"; exit 1; }

echo "==> code -> $dir/code.tgz"
ssh -o ConnectTimeout=20 "$PROD_SSH" \
  "cd '$PROD_WWW' && tar czf - --exclude=wp-content/uploads --exclude=wp-content/cache --exclude=wp-content/wflogs . ~/.ovhconfig" \
  > "$dir/code.tgz" 2>/dev/null
tar tzf "$dir/code.tgz" | grep -q "wp-includes/version.php" || { echo "code archive incomplete"; exit 1; }

du -sh "$dir"/*
echo "Snapshot ready: $dir"
