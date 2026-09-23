# Copilot instructions for dla-opac-ng

## What this repository is

This repo is **`dla_opac_ng`**, a TYPO3 v12 CMS extension (not a standalone app) that implements the
online catalog (OPAC) frontend for the Deutsches Literaturarchiv Marbach
(https://www.dla-marbach.de/katalog). It provides search/detail views, facets, media players, etc.,
built on top of a sibling TYPO3 extension **`dla/find`** (a fork of `subugoe/find`), which is *not*
part of this repo and lives at https://github.com/dla-marbach/typo3-find.

Repository layout:
- `Classes/` – PHP source (Controller, Ajax, Middleware, Service, Cli, ViewHelpers). PSR-4 root `Dla\DlaOpacNg\`.
- `Configuration/` – TCA, TypoScript, Services.yaml, CLI command registration.
- `Resources/` – Fluid templates/partials/layouts, public CSS/JS/fonts.
- `Tests/` – PHPUnit unit tests + Fluid-partial rendering tests + a recorded Solr mock server
  (`Tests/Fixtures/Solr`) so tests don't need a live Solr connection.
- `dla-find/` – **empty, gitignored placeholder**; the real `typo3-find` extension is cloned here by
  the dev-container/CI setup, not committed.
- `dla-opac-tests/` – **not in this repo**; Playwright E2E tests cloned as a sibling checkout.
- `t3example/` – gitignored, generated full TYPO3 install used only for local/CI test running.
- `Taskfile.yml` – all developer workflows (`task <name>`), driven by `go-task` + DDEV + Docker.
- `.devcontainer/` – Codespaces setup: installs DDEV, `task`, clones `dla-find` and
  `dla-opac-tests` as sibling repos, installs Playwright.
- `.github/workflows/phpunit.yml` and `psalm.yml` – CI (PHPUnit via DDEV; Psalm static/security analysis
  directly on this repo, no DDEV needed).

## ⚠️ Important: the normal dev workflow will NOT work in this sandbox

The documented workflow (`task install`, `task test:php`, `task cache`, etc.) spins up a full TYPO3
site inside **DDEV** (Docker) and clones the sibling `dla-find`/`dla-opac-tests` repos. In the Copilot
cloud agent sandbox this **reliably fails** because of restricted network egress:

- `https://ddev.com` and `https://taskfile.dev` (used by their install scripts) are **blocked** →
  `ddev`/`task` binaries cannot be installed via the documented one-liners.
- `https://api.github.com` is **blocked/returns 403**, while plain `https://github.com` (web + git
  clone over HTTPS) generally works. Composer downloads dist zipballs for GitHub-hosted packages
  (e.g. `phpunit/*`, `sebastian/*`, `typo3/testing-framework`) via the GitHub API, so
  `composer install`/`composer require` for this extension's dev dependencies fails with
  **`Could not authenticate against github.com`** unless a GitHub token is configured for Composer
  (`composer config -g github-oauth.github.com <token>` / `COMPOSER_AUTH` env var) — no such token is
  configured by default.
- Docker itself **does work** in the sandbox (`docker run hello-world` succeeds), and
  `git clone https://github.com/...` works, and `packagist.org` is reachable.

**Practical consequence:** do not assume you can run `task install` / `task test:php` /
`task test` (Playwright) end-to-end here. Before relying on any of them, do a quick connectivity probe
(`curl -I https://ddev.com`, `curl -I https://taskfile.dev`, `curl -I https://api.github.com`) — if
blocked, prefer these fallbacks:
- **Static analysis / linting**: Psalm (`psalm.xml` at repo root, error level 8) is the fastest
  reliable check and mirrors CI (`.github/workflows/psalm.yml`) — it does not need DDEV, only
  `composer install --ignore-platform-reqs` for this package plus dev deps, which will hit the same
  GitHub-auth issue for `vimeo/psalm`/`typo3/testing-framework` unless a Composer GitHub token is
  available. If a token is available (e.g. via `GITHUB_TOKEN`/`COMPOSER_AUTH`), configure it first:
  `composer config -g github-oauth.github.com "$GITHUB_TOKEN"`.
- **Reading/reviewing PHPUnit unit tests** (`Tests/Unit/**`, `Tests/Support/**`): these are pure PHP
  unit tests (bootstrap is `typo3/testing-framework`'s `UnitTestsBootstrap.php`, no DB/HTTP calls
  needed beyond the local Solr mock in `Tests/Fixtures/Solr`), so if you can get `composer install`
  to succeed (token configured, or vendor deps already cached), `vendor/bin/phpunit -c Tests/phpunit.xml`
  can run directly without DDEV/TYPO3/Solr.
- If none of the above is possible, reason carefully about correctness by reading the code and
  existing tests, and clearly state in your summary that automated verification could not be run due
  to sandbox network restrictions (document exactly which command failed and why).

If you *do* have working DDEV/Docker + unblocked network (e.g. in an actual Codespace or CI runner),
the intended workflow is:
```
task install        # one-time: sets up DDEV, TYPO3, imports fixtures
task test:php        # PHPUnit for dla_opac_ng + dla-find, against recorded Solr fixtures (no tunnel needed)
task test -- "katalog/"   # Playwright E2E tests (needs a running site)
task cache            # after editing TypoScript/PHP: flush cache since code is symlinked in
```

## Key conventions worth knowing

- PHP namespace root: `Dla\DlaOpacNg\` → `Classes/`. Tests namespace: `Dla\DlaOpacNg\Tests\` → `Tests/`.
- Requires PHP `^8.1`, TYPO3 `^12` (`cms-core`, `cms-extbase`, `cms-fluid`, `cms-frontend`), plus
  `fluidtypo3/vhs` and `dla/find` (also published on Packagist as `dla/find`).
- Fluid partials live in `Resources/Private/Partials/`; there are dedicated rendering tests for them
  in `Tests/Unit/Partials/**` using `Tests/Support/FluidPartialTestCase.php`, which builds a minimal
  Fluid rendering context (no full TYPO3 bootstrap) and swaps in lightweight overrides for
  `f:translate` and `f:link.action` (see `Tests/Support/FluidViewHelperOverrides/`).
- Solr-backed partials/tests use `Tests/Support/SolrFixture.php`, which builds real Solarium result
  objects from recorded cassettes in `Tests/Fixtures/Solr/cassettes` via the mock server
  (`Tests/Fixtures/Solr/MockSolrServer.php`); regenerate cassettes only via
  `Tests/Fixtures/Solr/recorder.php` (requires a live Solr tunnel, add new scenarios there first).
- The MediaAccess player partial disables browser media downloads (`controlslist="nodownload"`) and
  uses Video.js `audioOnlyMode` + a `dla-mediaplayer-audio` CSS class for compact audio controls
  (`Resources/Private/Partials/MediaAccess/Player.html`, `Resources/Public/CSS/opac-ng.css`).
- Extension code in the local dev TYPO3 install (`t3example/packages/dla-dla_opac_ng`) is a **symlink**
  to a hardlinked copy of this repo's `Classes/Configuration/Resources/Tests`; after editing code you
  only need `task cache` (not a full reinstall) to see changes take effect.
- `dla-find` releases are versioned separately; changes there require a new tag/release at
  https://github.com/dla-marbach/typo3-find/releases before this repo's composer constraint picks
  them up.

## CI

- `phpunit.yml`: checks out this repo + `dla-marbach/typo3-find` side-by-side, installs DDEV/Task, runs
  `task install` then `task test:php`.
- `psalm.yml`: runs Psalm directly against this repo (no DDEV) with security analysis enabled, uploads
  SARIF to GitHub code scanning.
