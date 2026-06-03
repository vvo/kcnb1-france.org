# Working on kcnb1-france.org

WordPress (Sage 9 theme) for a French rare-disease charity. Runs locally in Docker,
served over HTTPS by [portless](https://www.npmjs.com/package/portless).

## Production safety (read this first)

- **Never bulk-deploy.** No `rsync` of directories, no `--delete`, no DB import to prod,
  no `chmod`. A past bulk rsync wiped the live site and left files at `777`, which got it
  hacked. The old deploy recipe was deleted on purpose.
- **The only way to push to prod is `scripts/deploy-file.sh <file>`** — one (or a few)
  explicit files, backed up first, uploaded with `scp` at 644. Ask the user before running it.
- **Pulling *down* from prod is fine** (read-only there): `scripts/sync-from-prod.sh`.
- Prod is OVH shared hosting: `kcnbfrh@sshcloud.cluster024.hosting.ovh.net`, WordPress root
  at `~/www`. DB creds are read live from prod's `wp-config.php`, never stored in the repo.

## Local development

```sh
scripts/setup.sh            # writes wp-config.php, starts Docker, builds theme, portless route
scripts/sync-from-prod.sh   # fill DB + uploads from prod (reads only)
```

Local URL: **https://kcnb1-france.localhost** (portless, trusted cert, no port). It proxies
to nginx on host port 44000. `wp-config.php` trusts portless's `X-Forwarded-Host/-Proto`.

- **Theme build needs Node 14.18.0** (`.nvmrc`); it's Sage 9 / webpack 3 and won't run on
  modern Node. Use `fnm exec --using=14.18.0 npm run build` (or `npm start` to watch).
- **Local WP core must match prod's version** (currently 6.8.5). A mismatched core makes
  every page 404. To bump: download that version from wordpress.org, replace `wp-admin/`,
  `wp-includes/`, and root `wp-*.php`, leave `wp-content/` and `wp-config.php` alone.
- Prod-only/caching/security plugins are switched off locally via `DEV_DISABLED_PLUGINS`
  in `wp-config.php` (honored by the `my-plugin-disabler` mu-plugin).

## Theme gotchas (Sage 9)

- Views: `wp-content/themes/kcnb1/resources/views/`. Blade compiles to
  `wp-content/uploads/cache/`; it recompiles on source mtime change. If a change doesn't
  show, delete `wp-content/uploads/cache/*.php`.
- `@asset('images/x.png')` resolves via `dist/assets.json`. Locally there's no manifest, so
  it falls back to the bare `dist/` path. **Prod's manifest uses hashed filenames**, but
  unknown assets fall back to the bare path too — so an unhashed file uploaded to
  `dist/images/` is served fine without touching the manifest.
- **Prod serves an old (Sept 2025) compiled `main.css`.** It lacks working Bootstrap
  negative-margin utilities (`mt-n*`), so those silently do nothing on prod even though they
  work locally. For one-off theme spacing tweaks, prefer an **inline `style="..."`** so the
  result doesn't depend on rebuilding/redeploying the whole CSS bundle.
- **Prod has WP Super Cache active.** The plain homepage is cached; query-string URLs
  (e.g. `?loterie=1`) bypass it, which is the reliable way to preview a change on prod.

## Example: the lottery banner

`resources/views/partials/content-front-page.blade.php` shows a date-gated TONBEAULOT
banner (auto-visible a set window each year, `?loterie=1` to preview anytime). Image lives
in `resources/assets/images/` (source) and is built into `dist/images/`. It was shipped to
prod with `scripts/deploy-file.sh` (template + image only).

## Conventions

- French content/UI. Match the surrounding Blade/markup style.
- Commit only when asked. This repo is deployed by hand, file by file — keep diffs small.
