# TODO

Kept up to date by whoever works on the site (humans and agents). Remove items once done.

## Migration to OVH Pro (site switched on 2026-10-04)

- [ ] 2026-10-11: check that Sunday's BackWPup backup reached Dropbox from the new hosting.
- [ ] Delete `~/www/serveur.txt` on the new hosting (temporary marker used during the switch).
- [ ] Bump the local WordPress core from 6.8.5 to prod's version (6.8.10), see AGENTS.md.
- [ ] Decide what to do with the ~854 plugin files synced from prod and still uncommitted.
- [ ] 2026-10-05 morning: cancel the old Cloud Web hosting in OVH > Mes offres & services
      (`#/billing/autorenew/services`), `...` > Résilier.
      - First check the email detach went through: Mes commandes shows the "MX FULL" order as paid,
        and the `kcnb1-france.org` / MX Plan line now has a renewal date. If not, STOP: cancelling
        the hosting would delete the mailboxes.
      - Cancel these 3 lines, all named `kcnbfrh.cluster024.hosting.ovh.net`:
        - Hébergement web (renewal 01/01/2027)
        - Shared CDN (renewal 01/01/2027), if not cancelled together with the hosting
        - Zones DNS (renewal 01/09/2027), technical zone of the old hosting
      - Do NOT touch: `ftgqcup.cluster131.hosting.ovh.net` (the NEW hosting), and the three
        `kcnb1-france.org` lines (Domaine, Zones DNS = the real DNS zone, MX Plan = the mailboxes).
- [ ] Optional: rotate the `site@kcnb1-france.org` password (it was shared in a chat), then update
      `OVH_SMTP_PASSWORD` in prod's `wp-config.php`.
- [ ] Optional: remove the leftover DNS records `ftp`, `1|www...`, `3|welcome`, `l|fr`.
- [ ] Optional: remove the health-check plugin on prod.
