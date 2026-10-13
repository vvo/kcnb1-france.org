# TODO

Kept up to date by whoever works on the site (humans and agents). Remove items once done.

## Migration to OVH Pro (site switched on 2026-10-04)

- [ ] Cancel the Shared CDN of the old hosting: OVH > Mes offres & services
      (`#/billing/autorenew/services`), line `kcnbfrh.cluster024.hosting.ovh.net` / Shared CDN,
      `...` > Résilier. On 2026-10-04 it was still on automatic yearly renewal (01/01/2027), while
      the old Hébergement web and Zones DNS lines already showed "Résiliation demandée".
      Do NOT touch `ftgqcup.cluster131.hosting.ovh.net` (the NEW hosting) or any `kcnb1-france.org` line.
- [ ] Optional: rotate the `site@kcnb1-france.org` password (it was shared in a chat), then update
      `OVH_SMTP_PASSWORD` in prod's `wp-config.php`.
- [ ] Fathom Analytics plugin is inactive on prod: active on 2026-10-04, already inactive in the 2026-10-13
      snapshot taken before the PHP/WP upgrade. Ask whether that was on purpose; if not, reactivate it in wp-admin.

## Security (audit 2026-10-13)

Fixes proposed, waiting for the owner's go (prod writes):
- [ ] Root `.htaccess`: `Options -Indexes` (directory listing is on: `/wp-content/themes/kcnb1/.history/`
      lists files), block `xmlrpc.php` (enabled, `system.multicall` allows brute-force amplification),
      deny theme dev files (`composer.json/.lock`, `vendor/composer/installed.json`, `package.json`,
      `*.md`, `*.blade.php`) which expose library versions and template source.
- [ ] Delete dev junk on prod: `themes/kcnb1/.history/`, `themes/kcnb1/composer.json.backup`.
- [ ] DB: delete orphan admin capability for non-existent user_id 9999 (`wp_usermeta` 79, 80), a
      leftover of the old hack. Delete leftover options of removed plugins (`WPMR`, `WPMR_checksums`
      3.4 MB, `rank_math*`) and their cron hooks (`wpmr_hourly`, `wpmr_daily`), plus empty `uploads/rank-math/`.
- [ ] Old Cloud Web (until end of November): still serves a stale copy (WP 6.8.10, PHP 8.0) on
      `kcnbfrh.cluster024.hosting.ovh.net`. Put a deny-all `.htaccess` in its `~/www`.

For the owner (in wp-admin / OVH):
- [ ] Turn on Wordfence two-factor login for `vvoyer` (Wordfence > Login Security). No user has 2FA today,
      and the site gets ~1000 failed logins per month.
- [ ] Wordfence > All options > alert emails go to `gatien.sancho@qwenty.fr`: replace with your address
      if that person no longer looks after the site. Consider lowering "lock out after" from 20 failures to 5.
- [ ] Confirm the three editors created on 2025-09-11 (`Juliette`, `Laetitia`, `Lilya`) are expected.
- [ ] Change the SSH/FTP password of `ftgqcup` (OVH > Hébergement > FTP-SSH), the DB password, and the
      `site@` mailbox password: all three were pasted in a chat. SSH keeps working with the key.
      Send the new DB / mailbox passwords so `wp-config.php` gets updated.
