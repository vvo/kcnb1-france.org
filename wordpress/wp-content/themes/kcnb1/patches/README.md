# vendor PHP 8 compatibility patch

`vendor/` is not in git (theme `.gitignore`) and cannot be rebuilt with Composer: the
locked packages (Sage 9 / Illuminate 5.6 from 2019) are gone from Packagist or no longer
install on a modern PHP. So instead of upgrading them we patch the copy we have.

`vendor-php8-compat.patch` is the diff between the pristine prod `vendor/` and the
patched one. It only touches things PHP 8.x deprecates or removes, no behavior change:

- `ReflectionParameter::getClass()` (removed in PHP 8) replaced by a small helper in
  `illuminate/container/Container.php`, used by `Container.php` and `BoundMethod.php`.
- `#[\ReturnTypeWillChange]` on the `ArrayAccess` / `Countable` / `IteratorAggregate` /
  `JsonSerializable` methods of Collection, Container, Config Repository and View.
- `Type $x = null` parameters rewritten as `?Type $x = null` (PHP 8.4 deprecation).
- `e(null)` in `illuminate/support/helpers.php` casts to string before `htmlspecialchars`.

Verified on PHP 8.4: every public URL and the whole wp-admin render with an empty
debug.log (see the commit that added this file).

## Apply to a fresh vendor (e.g. after restoring prod from a backup)

```sh
cd wordpress/wp-content/themes/kcnb1/vendor
patch -p1 < ../patches/vendor-php8-compat.patch
```

## Deploy to prod

Prod vendor is byte-identical to the pre-patch copy (checked 2026-09-18), so the same
command works over SSH. Do it right after switching the PHP version in the OVH panel,
or before: the patched code also runs fine on PHP 8.0.
