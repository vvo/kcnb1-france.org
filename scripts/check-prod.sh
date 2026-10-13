#!/usr/bin/env bash
#
# Health check of the live site. READS PRODUCTION ONLY (plain HTTP GETs).
# Fetches every URL of the WordPress sitemap plus a few key endpoints and reports
# any non-200 answer or visible PHP error. Run it before and after any prod change.
#
# Usage: scripts/check-prod.sh

source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/_config.sh"

# The OVH firewall answers 403 to requests without a browser User-Agent.
UA="Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0 Safari/537.36"
get() { curl -s -A "$UA" -m 90 "$@"; }

urls=$(get "$PROD_URL/wp-sitemap.xml" | grep -o '<loc>[^<]*' | sed 's/<loc>//' | while read -r s; do
  get "$s" | grep -o '<loc>[^<]*' | sed 's/<loc>//' || true
done | sort -u)
[ -n "$urls" ] || { echo "Could not read the sitemap at $PROD_URL/wp-sitemap.xml"; exit 1; }

tmp=$(mktemp)
ok=0; bad=0
for u in $urls "$PROD_URL/wp-login.php" "$PROD_URL/feed/" "$PROD_URL/wp-json/"; do
  code=$(get -o "$tmp" -w '%{http_code}' "$u" || true)
  errs=$(grep -c "Fatal error\|Uncaught \|critical error\|erreur critique\|Deprecated:\|Warning:\|Notice:" "$tmp" || true)
  if [ "$code" = 200 ] && [ "$errs" = 0 ]; then ok=$((ok + 1)); else bad=$((bad + 1)); echo "  $code php-errors=$errs $u"; fi
done
rm -f "$tmp"

echo "checked $((ok + bad)) URLs: $ok ok, $bad problems"
[ "$bad" = 0 ]
