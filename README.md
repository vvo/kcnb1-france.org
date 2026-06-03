# kcnb1-france.org

> [!CAUTION]
> **Never write to the production website (files or database). No deploys.**
>
> The old deploy instructions in this repo (rsync to OVH, DB import) once wiped the
> live site and left files at `777`, which led to the site getting hacked. Do not run
> them. They have been removed on purpose.
>
> Production work, if ever needed, happens manually and deliberately, one small reviewed
> step at a time. Pulling data *down* from production for local use is fine (read-only on
> prod): `ssh` + `mysqldump` piped to your machine, downloading uploads. Pushing anything
> *up* is off limits.

The site runs locally in Docker, behind [portless](https://www.npmjs.com/package/portless)
which gives it a stable HTTPS URL with no port number:

**https://kcnb1-france.localhost**

## Requirements

- [Docker](https://docs.docker.com/install/)
- [fnm](https://github.com/Schniz/fnm) (or any way to run Node `14.18.0`, pinned in `.nvmrc` for the theme build)
- [portless](https://www.npmjs.com/package/portless) (`npm i -g portless`), the local HTTPS proxy

## Quick start

```sh
scripts/setup.sh
```

That writes `wp-config.php`, starts Docker, builds the theme, and registers the portless
route. Then open **https://kcnb1-france.localhost**.

A fresh checkout has an empty database. Fill it (and the media) with a copy of production:

```sh
scripts/sync-from-prod.sh        # DB + uploads (reads prod only, never writes)
```

## Refreshing from production

`scripts/sync-from-prod.sh` dumps the prod DB over SSH, imports it locally, rewrites URLs
to `kcnb1-france.localhost`, and downloads the uploads. It only ever reads production.

```sh
scripts/sync-from-prod.sh            # everything
scripts/sync-from-prod.sh --db       # database only
scripts/sync-from-prod.sh --uploads  # media only
```

The local WordPress core must match production's version (currently 6.8.5) or the DB will
404 every page. If you bump prod, update the local core the same way: download that version
from wordpress.org and replace `wp-admin/`, `wp-includes/`, and the root `wp-*.php` files,
leaving `wp-content/` and `wp-config.php` alone.

## Working on the theme

```sh
cd wordpress/wp-content/themes/kcnb1
fnm use           # -> 14.18.0
npm start         # webpack watch; `npm run build` for a one-off
```

The theme is Sage 9 (webpack 3), which only runs on the pinned old Node.

## Deploying

No bulk deploys, ever (see the caution banner). The previous rsync/DB-import recipe broke
and compromised the live site and was removed.

The one sanctioned way to push a change is **one file at a time**, backed up first:

```sh
scripts/deploy-file.sh wordpress/wp-content/themes/kcnb1/resources/views/partials/content-front-page.blade.php
```

It shows a diff against the current prod copy, asks before writing, uploads with `scp` at
644 (never `chmod`), and saves a rollback copy under `.deploy-backups/`. Pass several files
to ship a small set (e.g. a template plus its image). Theme templates render live; no build
runs on prod. Remember the prod gotchas in `CLAUDE.md` (old compiled CSS, WP Super Cache).
