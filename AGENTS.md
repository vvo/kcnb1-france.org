# Working on kcnb1-france.org

WordPress site (Sage 9 theme) of a French rare-disease charity. Runs locally in Docker,
served over HTTPS by [portless](https://www.npmjs.com/package/portless). Production is a
single OVH shared hosting, edited by hand.

Keep `TODO.md` up to date: tick what you finish, add what you discover. Remove items once done.

## Production

- OVH **Pro** shared hosting since 2026-10-04, cluster131.
  - SSH: `ftgqcup@ssh.cluster131.hosting.ovh.net` (key auth), WordPress root `~/www`.
  - PHP 8.4 for the web since 2026-10-13, set by `app.engine.version` in `~/.ovhconfig` (takes effect in
    seconds). The SSH `php` CLI is 8.3. WP-CLI is at `~/wp-cli.phar` (`cd ~/www && php ~/wp-cli.phar ...`).
  - WordPress 7.1.x, minor/security versions auto-update.
  - MySQL 8.4, host `ftgqcupkcnb1new.mysql.db`. Creds live only in prod's `wp-config.php`.
  - IPs: `146.59.209.152` / `2001:41d0:301::31`. No CDN. OVH firewall on: it answers 403
    to requests without a browser User-Agent, so pass `-A "Mozilla/5.0 ..."` to `curl`.
  - SSL: OVH Let's Encrypt, renews itself.
  - Outbound connections are blocked from the SSH shell (SMTP, HTTP): WP-CLI cannot download
    updates, upload the zip and run `wp core update ~/wordpress-x.zip`. PHP served over the web can reach out. To test mail, drop a token-named PHP file in `~/www`, curl it, delete it.
- The old Cloud Web (`kcnbfrh@sshcloud.cluster024.hosting.ovh.net`) is cancelled and stops end of
  November 2026. It is closed: `Require all denied` in its `.htaccess`, `DISABLE_WP_CRON` in its config.
- DNS zone at OVH (ns106/dns106). MX, SPF, DKIM point to OVH mail and must not change.
- Email accounts (`contact@`, `vincent@`, `site@`, redirect `postmaster@`) are a standalone
  MX Plan (MX FULL), independent of the hosting.
- Backups: BackWPup, weekly full backup to Dropbox (`Apps/InpsydeBackWPup/Association-KCNB1-France/`).

## Production safety (read this first)

- **Never bulk-deploy.** No `rsync` of directories, no `--delete`, no DB import to prod,
  no `chmod`. A past bulk rsync wiped the live site and left files at `777`, which got it hacked.
- **The only way to push files to prod is `scripts/deploy-file.sh <file>`**: a few explicit files,
  backed up first, uploaded with `scp` at 644.
- **Pulling down from prod is fine** (read-only there): `scripts/sync-from-prod.sh`.
- Ask the user before any write to prod (files, DB, `~/.ovhconfig`, OVH panel). Reading is always fine.
- **Outgoing email goes through OVH authenticated SMTP** via the `ovh-smtp` mu-plugin. It reads
  `OVH_SMTP_USER` (`site@kcnb1-france.org`) and `OVH_SMTP_PASSWORD` from prod's `wp-config.php`;
  without them it does nothing, so local dev is unaffected. Every `wp_mail()` is forced to that From.
  Keep both constants if `wp-config.php` is ever rebuilt.
- Prod has `WP_DEBUG` off: the debug.log used to bloat backups by 270 MB.
- `.user.ini` (Wordfence WAF) hardcodes the absolute path `/home/ftgqcup/www/wordfence-waf.php`.

## How to change production

Every prod change, however small, follows these steps:

1. `scripts/check-prod.sh`: every sitemap URL plus key endpoints must answer 200 with no PHP error.
   If it already fails, stop and report before changing anything.
2. `scripts/snapshot-prod.sh`: DB + all code into `.prod-snapshots/<timestamp>/` on this machine.
   That is the rollback point. The weekly Dropbox backup is the second one.
3. Test the same change locally first when it touches code, PHP or WordPress versions (local runs the
   same PHP/MySQL/WP; refresh it with `scripts/sync-from-prod.sh --db`).
4. Make the change.
5. `scripts/check-prod.sh` again. For deeper checks (PHP notices, mail), drop a PHP file named with a
   random 32-hex token in `~/www`, `curl` it with a browser User-Agent, then delete it right away.
6. Update `AGENTS.md` (versions, facts) and `TODO.md`, commit.

Recipes:

- **PHP version**: edit `app.engine.version` in `~/.ovhconfig` (the OVH panel writes the same file).
  Check with a token file printing `PHP_VERSION`. Stay one version behind the newest: the 2019
  theme vendor is patched by hand (`themes/kcnb1/patches/`), test a new PHP locally first.
- **WordPress core (major)**: SSH can't download. Download the `fr_FR` zip locally, check its `.sha1`,
  `scp` it, then `cd ~/www && php ~/wp-cli.phar core update ~/wordpress.zip --locale=fr_FR`,
  `core update-db`, `core verify-checksums --locale=fr_FR`, delete the zip. Minor versions install
  themselves; expect ~1 min of "maintenance" 503 right after a core change. Then bump the local core.
- **Plugins**: update from wp-admin (web PHP can download), one at a time, `check-prod.sh` after each.
- **Restore**: code from `code.tgz` (single files with `scp`), DB by importing `db.sql.gz` with the
  creds from prod's `wp-config.php`. Ask the user first, a DB import overwrites everything.

## Security

The site was hacked before (files left at `777`, then used to host spam pages). Assume it is a target.

- In place: Wordfence (WAF loaded via `.user.ini`, scheduled scans, login captcha, lockout after 5 failures, alerts to
  `wouaren@gmail.com`), OVH firewall, PHP blocked in `wp-content/uploads/` by `uploads/.htaccess`.
- Every plugin has WordPress auto-updates on. Keep the plugin list short: each plugin is attack surface.
  Active (2026-10-13): ACF Pro, AddToAny, AMP (AMP pages are live and advertised to Google, keep it),
  Autoptimize, BackWPup, Duplicate Post, Favicon, Wordfence, WP Super Cache. Fathom installed but inactive.
- `DISALLOW_FILE_EDIT` is set in prod `wp-config.php`: no theme/plugin PHP editor in wp-admin.
  Page and post content (including the HTML/code view of the editor) is unaffected.
- Root `.htaccess` starts with a `# BEGIN kcnb1 hardening` block: no directory listing, `xmlrpc.php`
  denied, theme dev files denied (`composer.*`, `package*.json`, `installed.json`, `*.md`, `*.blade.php`,
  `theme.json`...), security headers, no `X-Powered-By`. Keep it above the plugin blocks.
- Audit baseline (2026-10-13): core verifies against checksums, every wordpress.org plugin matches the
  official zip of its version, theme and mu-plugins match this repo, no PHP outside expected places in
  uploads (only Blade cache in `uploads/cache/`), no spam or foreign content in
  the DB, no world-writable file. Cleaned the same day: an orphan admin capability (user_id 9999, a
  leftover of the old hack) and options/cron of removed plugins (Malcure WPMR, Rank Math). Admins: only `vvoyer`. Editors: `mcassard`, `Juliette`, `Laetitia`, `Lilya`.
- To re-audit: `wp core verify-checksums`, diff `wp-content/plugins/<p>` against
  `downloads.wordpress.org/plugin/<p>.<version>.zip` (fetched locally, SSH can't download), list
  `find wp-content/uploads -name "*.php*"`, list admins and users, grep posts for spam/CJK and
  `wp_options` for `<script`/`eval(`, check Wordfence `wp_wfissues` for `status='new'`.
- Never commit secrets: `wp-config.php` is gitignored, passwords live in prod's `wp-config.php` and
  the owner's password manager. The repo is public. A gitleaks hook runs on commit.

## Local development

```sh
scripts/setup.sh            # writes wp-config.php, starts Docker, builds theme, portless route
scripts/sync-from-prod.sh   # fill DB + uploads from prod (reads only)
```

Local URL: **https://kcnb1-france.localhost**. It proxies to nginx on host port 44000.

- **Theme build needs Node 14.18.0** (`.nvmrc`), Sage 9 / webpack 3. Use
  `fnm exec --using=14.18.0 npm run build` (or `npm start` to watch).
- **Local PHP is 8.4 and MySQL 8.4** (Docker), prod PHP 8.4. The theme runs clean on both thanks to the vendor patch in
  `wp-content/themes/kcnb1/patches/` (already applied on prod). Do not `composer update` the
  theme: its 2019 dependencies no longer resolve. If `vendor/` is ever reinstalled, reapply the patch.
- **Local WP core must match prod's version** (prod auto-updates minor versions, 7.1.3 on 2026-10-13). A
  mismatched core makes every page 404. To bump: download that version from wordpress.org, replace
  `wp-admin/`, `wp-includes/`, and root `wp-*.php`, leave `wp-content/` and `wp-config.php` alone.
- Prod-only, caching and security plugins are off locally via `DEV_DISABLED_PLUGINS` in
  `wp-config.php` (honored by the `my-plugin-disabler` mu-plugin).

## Theme gotchas (Sage 9)

- Views: `wp-content/themes/kcnb1/resources/views/`. Blade compiles to `wp-content/uploads/cache/`.
  If a change doesn't show, delete `wp-content/uploads/cache/*.php`.
- `@asset('images/x.png')` resolves via `dist/assets.json`. Prod's manifest uses hashed filenames,
  but unknown assets fall back to the bare `dist/` path, so an unhashed file uploaded to
  `dist/images/` works without touching the manifest.
- **Prod serves an old (Sept 2025) compiled `main.css`.** Bootstrap negative-margin utilities
  (`mt-n*`) do nothing there. For one-off spacing tweaks, use inline `style="..."`.
- **Prod has WP Super Cache active.** Query-string URLs (e.g. `?loterie=1`) bypass it, which is
  the reliable way to preview a change on prod.

## Example: the lottery banner

`resources/views/partials/content-front-page.blade.php` shows a date-gated TONBEAULOT banner
(auto-visible a set window each year, `?loterie=1` to preview anytime). The image lives in
`resources/assets/images/` and is built into `dist/images/`. It was shipped with
`scripts/deploy-file.sh` (template + image only).

## Conventions

- French content and UI. Match the surrounding Blade/markup style.
- Commit only when asked. Keep diffs small: prod is deployed by hand, file by file.
