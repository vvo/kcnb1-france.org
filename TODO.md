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
- [ ] PHP 8.4 + WordPress 7.1.2 on prod (prod is on PHP 8.3, WP 6.8.11 as of 2026-10-13). Validated locally on 2026-10-05
      (PHP 8.4, MySQL 8.4, WP 7.1.2, prod DB): 44 public pages identical text to prod, wp-admin pages,
      post edit/save, media upload, ACF, Wordfence, BackWPup all fine, empty debug.log.
      1. wp-admin > BackWPup > run the job now ("Exécuter maintenant"), wait until it reaches Dropbox.
      2. OVH > new hosting > Informations générales > Configuration > PHP 8.4, keep the rest. Validate.
         Agent: wait ~5 min, check `php -v` over SSH and that the site answers 200.
      3. wp-admin > Mises à jour > "Mettre à jour vers la version 7.1.2". Then also update translations.
         Agent: crawl all pages, check wp-admin, send a test mail, check prod debug log is empty.
      Rollback: PHP back to 8.3 in OVH (one click); WordPress from the BackWPup archive of step 1.
      Not PHP 8.5 yet (too new for the 2019 theme vendor).
- [ ] Remove `~/removed-plugins/health-check` on prod once the site has run fine for a few weeks.
