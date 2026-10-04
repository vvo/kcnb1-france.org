# TODO

Kept up to date by whoever works on the site (humans and agents). Remove items once done.

## Migration to OVH Pro (site switched on 2026-10-04)

- [ ] 2026-10-11: check that Sunday's BackWPup backup reached Dropbox from the new hosting.
- [ ] Delete `~/www/serveur.txt` on the new hosting (temporary marker used during the switch).
- [ ] Bump the local WordPress core from 6.8.5 to prod's version (6.8.10), see AGENTS.md.
- [ ] Decide what to do with the ~854 plugin files synced from prod and still uncommitted.
- [ ] Before end of November 2026: cancel the old Cloud Web hosting (`kcnbfrh.cluster024`),
      including its automatic renewal planned for January 2027. Detach its domains first if OVH asks.
      The emails no longer depend on it (detached to a standalone MX FULL).
- [ ] Optional: rotate the `site@kcnb1-france.org` password (it was shared in a chat), then update
      `OVH_SMTP_PASSWORD` in prod's `wp-config.php`.
- [ ] Optional: remove the leftover DNS records `ftp`, `1|www...`, `3|welcome`, `l|fr`.
- [ ] Optional: remove the health-check plugin on prod.
