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
- [ ] Remove `~/removed-plugins/health-check` on prod once the site has run fine for a few weeks.
- [ ] Fathom Analytics plugin is inactive on prod: active on 2026-10-04, already inactive in the 2026-10-13
      snapshot taken before the PHP/WP upgrade. Ask whether that was on purpose; if not, reactivate it in wp-admin.
- [ ] Delete migration leftovers in prod's home (outside `www`, not public): `db2.sql.gz`, `kcnb1-db.sql.gz`,
      `newdb-before-resync.sql.gz`, `site2.tgz`, `stage/`, `wp-config.php.from-cloudweb`. Keep `wp-cli.phar`.
