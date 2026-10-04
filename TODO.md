# TODO

Kept up to date by whoever works on the site (humans and agents). Remove items once done.

## Migration to OVH Pro (site switched on 2026-10-04)

- [ ] 2026-10-11: check that Sunday's BackWPup backup reached Dropbox from the new hosting.
- [ ] Delete `~/www/serveur.txt` on the new hosting (temporary marker used during the switch).
- [ ] Bump the local WordPress core from 6.8.5 to prod's version (6.8.10), see AGENTS.md.
- [ ] Decide what to do with the ~854 plugin files synced from prod and still uncommitted.
- [ ] Cancel the Shared CDN of the old hosting: OVH > Mes offres & services
      (`#/billing/autorenew/services`), line `kcnbfrh.cluster024.hosting.ovh.net` / Shared CDN,
      `...` > Résilier. On 2026-10-04 it was still on automatic yearly renewal (01/01/2027), while
      the old Hébergement web and Zones DNS lines already showed "Résiliation demandée".
      Do NOT touch `ftgqcup.cluster131.hosting.ovh.net` (the NEW hosting) or any `kcnb1-france.org` line.
- [ ] Move billing of the new hosting (`ftgqcup.cluster131`, Pro, renews 18/09/2027) and the
      MX Plan (`kcnb1-france.org`, MXPLAN 1000) to the association's own OVH account and payment
      method. Today they are billed to Vincent's card via contact `cm867438-ovh`. Done in
      OVH > Mes contacts > change the "Facturation" contact to the association's account
      (the association needs its own OVH account first; the other account must accept the request).
      Also consider the domain `kcnb1-france.org` and its DNS zone, same billing contact.
- [ ] Optional: rotate the `site@kcnb1-france.org` password (it was shared in a chat), then update
      `OVH_SMTP_PASSWORD` in prod's `wp-config.php`.
- [ ] Optional: remove the leftover DNS records `ftp`, `1|www...`, `3|welcome`, `l|fr`.
- [ ] Optional: remove the health-check plugin on prod.
