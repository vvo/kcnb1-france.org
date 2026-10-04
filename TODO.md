# TODO

Kept up to date by whoever works on the site (humans and agents). Remove items once done.

## Migration to OVH Pro (site switched on 2026-10-04)

- [ ] 2026-10-11: check that Sunday's BackWPup backup reached Dropbox from the new hosting.
- [ ] Cancel the Shared CDN of the old hosting: OVH > Mes offres & services
      (`#/billing/autorenew/services`), line `kcnbfrh.cluster024.hosting.ovh.net` / Shared CDN,
      `...` > Résilier. On 2026-10-04 it was still on automatic yearly renewal (01/01/2027), while
      the old Hébergement web and Zones DNS lines already showed "Résiliation demandée".
      Do NOT touch `ftgqcup.cluster131.hosting.ovh.net` (the NEW hosting) or any `kcnb1-france.org` line.
- [ ] Optional: rotate the `site@kcnb1-france.org` password (it was shared in a chat), then update
      `OVH_SMTP_PASSWORD` in prod's `wp-config.php`.
- [ ] Paste the cleaned DNS zone (without `ftp`, `1|www...`, `3|welcome`, `l|fr`) and check the site and MX still resolve.
- [ ] Upgrade WordPress from 6.8.x to 7.1.x: test locally first (theme is old Sage 9, check front + wp-admin
      + ACF + Wordfence), then update on prod from wp-admin right after a fresh BackWPup backup.
      6.8.x keeps getting security fixes through automatic minor updates meanwhile.
- [ ] Remove `~/removed-plugins/health-check` on prod once the site has run fine for a few weeks.
