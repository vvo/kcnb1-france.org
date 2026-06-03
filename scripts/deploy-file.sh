#!/usr/bin/env bash
#
# The ONLY sanctioned way to push to production: one (or a few) explicit files,
# each backed up first, uploaded with scp. No rsync, no recursion, no --delete,
# no chmod. See the caution history in README / CLAUDE.md.
#
# Usage:
#   scripts/deploy-file.sh <path> [<path> ...]
#   scripts/deploy-file.sh --yes <path> ...     # skip the confirmation prompt
#
# Paths are repo-relative and must live under wordpress/. Example:
#   scripts/deploy-file.sh wordpress/wp-content/themes/kcnb1/resources/views/partials/content-front-page.blade.php

source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/_config.sh"
cd "$ROOT"

ASSUME_YES=0
[ "${1:-}" = "--yes" ] && { ASSUME_YES=1; shift; }

[ "$#" -ge 1 ] || { echo "Usage: scripts/deploy-file.sh [--yes] <path> [<path> ...]"; exit 1; }
[ "$#" -le 10 ] || { echo "Refusing to deploy more than 10 files at once. Deploy in small batches."; exit 1; }

# Validate every path up front: regular file, under wordpress/, no globs left.
for f in "$@"; do
  [ -f "$f" ] || { echo "Not a regular file: $f"; exit 1; }
  case "$f" in
    wordpress/*) : ;;
    *) echo "Path must be under wordpress/: $f"; exit 1 ;;
  esac
done

remote_path() { echo "${PROD_WWW}/${1#wordpress/}"; }

echo "About to deploy to ${PROD_SSH}:"
for f in "$@"; do echo "  $f  ->  $(remote_path "$f")"; done

# Show a diff against the current prod copy for text files (binary just noted).
echo
for f in "$@"; do
  rp="$(remote_path "$f")"
  if file "$f" | grep -qiE 'text|empty'; then
    echo "=== diff (prod -> local) for $f ==="
    ssh -o ConnectTimeout=20 "$PROD_SSH" "cat '$rp' 2>/dev/null" 2>/dev/null \
      | diff - "$f" && echo "(no changes)" || true
  else
    echo "=== $f is binary; will upload as-is ==="
  fi
done

if [ "$ASSUME_YES" != 1 ]; then
  echo
  read -r -p "Proceed? This writes to PRODUCTION. [y/N] " a
  [[ "$a" =~ ^[Yy]$ ]] || { echo "Aborted."; exit 1; }
fi

ts="$(date +%Y%m%d-%H%M%S)"
for f in "$@"; do
  rp="$(remote_path "$f")"
  bak=".deploy-backups/${ts}/${f#wordpress/}"
  mkdir -p "$(dirname "$bak")"
  echo "==> backing up prod copy -> $bak"
  ssh -o ConnectTimeout=20 "$PROD_SSH" "cat '$rp' 2>/dev/null" > "$bak" 2>/dev/null || true
  echo "==> uploading $f"
  scp -o ConnectTimeout=20 "$f" "${PROD_SSH}:${rp}"
done

echo
echo "==> prod homepage health: $(curl -s -o /dev/null -w '%{http_code}' "$PROD_URL/")"
echo "Done. Backups in .deploy-backups/${ts}/"
echo "Rollback a file with:"
echo "  scp .deploy-backups/${ts}/<path-under-wordpress> ${PROD_SSH}:${PROD_WWW}/<path-under-wordpress>"
