# Laravel UI Kit — working notes

`shipbytes/laravel-ui-kit` on Packagist. Auth pages, working 2FA and a config-driven admin
shell for Laravel 12 (Livewire 4, Fortify, Tailwind v4): a core plus seven optional
modules, installed by `php artisan ui-kit:install`. The documentation people read is
**https://shipbytes.dev/docs/laravel-ui-kit**, and most of it is NOT in this repo (see
"The docs site" below). Never name the application this was extracted from.

## Layout

```
src/Console/            the Artisan commands; InstallModuleCommand applies the registry,
                        DoctorCommand verifies an install
src/Support/ModuleRegistry.php
                        the ONE place a module says what installing it must do
resources/, routes/     what gets published into the app; the route stubs carry
                        /* ui-kit:...-start/end */ markers the installer patches between
tests/                  PHPUnit incl. ModuleRegistryTest, PatchingIdempotencyTest and an
                        end-to-end installer test
scripts/screenshots.mjs builds a fresh app, seeds it, photographs every screen
docs/images/            the screenshots, COMMITTED (<screen>-light.png / -dark.png)
CHANGELOG.md            Keep a Changelog; the site renders it
```

## Commands

```bash
composer install
vendor/bin/phpunit            # must be green before any commit
vendor/bin/pint --test
vendor/bin/phpstan analyse
npm install && npm run screenshots    # regenerates docs/images/; needs php, composer, a Chromium
```

CI (`.github/workflows/tests.yml`) runs lint, static analysis and the suite on PHP
8.2–8.4, then a `fresh-app` job that creates a real Laravel 12 app, installs the kit with
every module, builds assets, caches routes, runs the doctor and smoke-tests the pages.
Do not merge anything red, and prefer a branch + PR so that job runs before master moves.

## Rules when changing the kit

1. **Everything an install must do goes through `ModuleRegistry`.** Keys: `composer`,
   `admin_routes`, `user_routes`, `admin_nav`, `admin_middleware_swap`, `artisan_publish`,
   `artisan_seed`, `storage_link`, `post_install_notes`. If a module needs a line in a
   routes file, declare it there; never document "add this line yourself". Route patches
   are idempotent by route name or whole-line match — add a `PatchingIdempotencyTest` case
   for a new shape of line. The impersonation module shipped two releases without its
   `Route::impersonate();` line because it relied on a README instruction.
2. **A doctor check for every install-time guarantee.** If the installer promises it,
   `ui-kit:doctor` verifies it, with a hint that says how to repair it.
3. **A module is not done until:** registry entry, tests, README section under
   "Module deep-dives", a `CHANGELOG.md` entry under `[Unreleased]`, fresh screenshots if a
   screen changed, and the matching page on the docs site (below). Same change series.
4. **Screenshots are the proof.** Re-run `npm run screenshots` whenever a screen's look
   changes and commit the PNGs. The site shows `<name>-light.png` and `<name>-dark.png` as a
   pair through its `<Screenshot name="…">` component, so keep the naming.

## The docs site (shipbytes.dev)

The site repo is `shipbytes/shipbytes.dev` (local: `/var/www/shipbytes.dev`). At build time
it clones this repo's **master** and takes only three things from it: `docs/images/`,
`CHANGELOG.md` (rendered at /docs/laravel-ui-kit/changelog) and the latest tag. The prose
(quickstart, core, modules, one page per module, customising, upgrading, troubleshooting)
is hand-written MDX in the site repo at `src/app/(docs)/docs/laravel-ui-kit/`. README.md is
not read by the site.

So a behaviour change here needs a matching edit there, in the same sitting:
the module's page under `modules/<slug>/page.mdx`, and a `troubleshooting/page.mdx` entry
if the change removes or adds a failure mode. Push to the site's main branch deploys it.

**Merging to master here rebuilds the site automatically** (GitHub push webhook → the
rebuild Worker → the site's deploy workflow, ~2 minutes). Only pushes to master count;
branches and tags are ignored. Check https://shipbytes.dev/docs/laravel-ui-kit/changelog
afterwards.

## Releasing

Packagist is immutable per version and only sees tags, so users on `^0.2` get nothing
until a tag exists. Pre-1.0, a breaking change bumps the minor.

```bash
# 1. CHANGELOG.md: rename [Unreleased] to [X.Y.Z] - YYYY-MM-DD, add an empty [Unreleased]
#    above it, and add the compare link at the bottom.
git commit -am "Release X.Y.Z"
git push origin master
git tag -a vX.Y.Z -m "vX.Y.Z" && git push origin vX.Y.Z
gh release create vX.Y.Z --title vX.Y.Z --notes-file <the section>   # releases page is linked from the docs
```

Packagist picks the tag up within minutes through the GitHub hook; confirm with
`curl -s https://repo.packagist.org/p2/shipbytes/laravel-ui-kit.json`.
