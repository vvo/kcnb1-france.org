# TODO

Kept up to date by whoever works on the site (humans and agents). Remove items once done.

## Migration to OVH Pro (site switched on 2026-10-04)

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

Dependabot (279 open alerts, triaged 2026-11-24, none exploitable on prod):
- 273 are npm build tools in `themes/kcnb1/package-lock.json` and `themes/twentytwenty/package-lock.json`.
  No `node_modules` on prod, and `twentytwenty` is not even on prod (only in this repo).
- 4 are composer dev tools (phpunit, phpcs, symfony/process): CLI-only. phpunit is absent from prod,
  `vendor/squizlabs` (phpcs) is on prod but its flaw needs a CLI run.
- 2 are runtime, not reachable: `nesbot/carbon` 1.25.3 (needs user input passed to `setLocale`, the theme
  never calls it), `illuminate/view` 5.6.39 (Blade XSS needs `@show` sections with user input, none in views).
- Done 2026-11-24: the 152 kcnb1/composer alerts are dismissed in GitHub as "vulnerable code not used"
  (with a reason), `themes/twentytwenty` is removed from the repo (its 127 alerts close once pushed).
  New Dependabot alerts on these manifests can be dismissed the same way after a quick check.
- [ ] Optional: delete `wp-content/themes/kcnb1/vendor/squizlabs` (phpcs, dev tool) from prod. Ask first, prod write.

For the owner, to stay warned:
- [ ] Free uptime + defacement monitor (UptimeRobot or Better Stack) on the home page with a keyword check.
- [ ] Google Search Console: make sure the owner gets its emails (hacked content / spam alerts).
