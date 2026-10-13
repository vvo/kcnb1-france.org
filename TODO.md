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

For the owner (in wp-admin / OVH):
- [ ] Turn on Wordfence two-factor login for `vvoyer` (Wordfence > Login Security). No user has 2FA today,
      and the site gets ~1000 failed logins per month.
- [ ] Confirm the three editors created on 2025-09-11 (`Juliette`, `Laetitia`, `Lilya`) are expected.
- [ ] Change the SSH/FTP password of `ftgqcup` (OVH > Hébergement > FTP-SSH), the DB password, and the
      `site@` mailbox password: all three were pasted in a chat. SSH keeps working with the key.
      Send the new DB / mailbox passwords so `wp-config.php` gets updated.

Proposed next (owner to decide):
- [ ] Add `define('DISALLOW_FILE_EDIT', true);` to prod `wp-config.php` (no PHP editor in wp-admin).
- [ ] Remove unused plugins to shrink the attack surface: Sucuri (duplicates Wordfence), Akismet
      (comments closed, 0 comments), Advanced Database Cleaner, Regenerate Thumbnails; maybe AMP.
- [ ] Free uptime + defacement monitor (UptimeRobot or Better Stack) on the home page with a keyword check.
- [ ] Google Search Console: make sure the owner gets its emails (hacked content / spam alerts).
