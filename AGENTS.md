# AGENTS.md

Guidance for AI coding agents (Claude Code, Codex, Junie, Cursor) working in this repository.

## What this is

**Admin IP Access** (`Wobqqq.FortifyAdminIpAccess`) is a free module of the Fortify security suite for October CMS 3.x/4.x (built and tested against 4.4 on Laravel 12, PHP 8.2+). It opens the backend only to the IP addresses and subnets on a whitelist, answering any other address 403 with the page the administrator chose; the site's frontend is not affected.

It requires the core plugin [`Wobqqq.Fortify`](https://github.com/wobqqq/oc-fortify-plugin): the settings live in the core's `Wobqqq\Fortify\Models\Fortify` record under the `ip_firewall.admin_ip_access_*` key and appear on **Settings → Fortify**, and the module draws its own item on the core's dashboard widget.

This is a **security product installed on production sites**. A bug here locks administrators or visitors out, or silently leaves a site unprotected. Security and safe upgrades come before everything else.

## The self-check gate (run before every commit)

Everything runs in Docker; the host needs no PHP.

```bash
make install        # composer install inside the php container (the core comes from Packagist, as `wobqqq/fortify-plugin`)
make code.fix       # composer normalize, rector, php-cs-fixer
make code.check     # validate, normalize --dry-run, audit, php -l, yaml-lint, cs, rector, PHPStan max
make test           # Pest
make test.coverage  # Pest with pcov, fails below 90 %
make ready          # all of the above
```

`make ready` must pass. PHPStan runs at `level: max` with strict rules and **no baseline**: fix the type, never add an ignore. Advisories reported by `composer audit` are fixed by updating the package, never ignored.

## How the code is laid out

| Path | Holds |
|------|-------|
| `Plugin.php` | Wiring: console commands, the `admin_ip_access_current_ip` validation rule, the listener, the middleware. |
| `services/AdminIpAccessService.php` | The whitelist check (exact addresses, IPv4 and IPv6 subnets), adding an address, disabling, the middleware registration on `backend.middleware_group`. |
| `http/middlewares/AdminIpAccessMiddleware.php` | Runs the check on every backend request. |
| `transformers/FortifyTransformer.php` | Turns the stored settings into `AdminIpAccessDto`, with a safe page fallback. |
| `cache/, instances/` | The cached DTO (cleared on every settings save) and its per-request memo. |
| `listeners/FortifyListener.php` | Wires the events to `SettingsService` and clears the module's cache when the settings are saved or deleted. |
| `services/SettingsService.php` | The settings form fields, their validation rules and defaults, the administrator's IP preset, the empty rows filter, the dashboard item. |
| `validator/rules/AdminIpAccessCurrentIpRule.php` | Refuses a whitelist that no longer covers the administrator saving it. |
| `console/` | `add-ip` and `disable`, the recovery path. |
| `updates/version.yaml` | The version history the marketplace reads from `main`. |

### Working with the core

- The core is a separate plugin that sites update on their own schedule. Use only the core's public API (listed in the core's AGENTS.md: the `Fortify` settings model, `FortifyEvent`, `View`, `WidgetItemColor`, the widget DTOs and `FortifyTransformer::widget*Dto()`, `BasicCache::cacheKey()`/`TTL`). A new core API is used only behind a check (`method_exists`, `enum_exists`) with a fallback, so the module keeps working on every released core.
- Settings are validated by rules the module adds to the core model. Add them in `Fortify::extend()` **and** when the settings form is built: the settings instance may exist before the module extends the model.
- Caches are cleared on the `eloquent.saved` / `eloquent.deleted` events of the core model, never with `bindEvent()` on an instance, for the same reason.

## Architecture

Read the architecture skills before changing how the module is structured: `application-layer`, `dependency-injection`, `error-handling`, `validation`, `events`, `testing-architecture`, `domain-layer-cqrs` and `plugin-boundaries`. The listener only wires events: the settings section (defaults, rules, fields, the dashboard line) lives in `services/SettingsService.php`.

## Upgrading installed sites safely

Read the `plugin-upgrades` skill before changing anything that reaches a site that already runs the module: a new version in `updates/version.yaml` for every shipped change, an update script for every change to what is stored, a new cache key for every change to a cached object's shape, and defaults that cannot lock anyone out.

## Security rules (always)

Read the `fortify-security` skill for the full checklist. For this module in particular:

- The IP is `Request::ip()`: behind a proxy or a CDN it is the proxy's unless October's trusted proxies are configured. Never read `X-Forwarded-For` yourself.
- An address is compared with `IpUtils::checkIp()`, never as a string: subnets and every IPv6 notation of an address must match.
- The administrator saving the whitelist stays on it (the validation rule and `presetCurrentIp()`): a change must not make it possible to save a list that locks out the person saving it.
- An enabled module with an empty whitelist lets everyone in on purpose, so that a mistake never locks every administrator out; the dashboard shows it as unprotected.
- Every backend request runs the check: keep it to the cached DTO and `IpUtils`, no database query.

Recovery from the console, for an administrator who locked themselves out:

- `php artisan wobqqq.fortify:admin-ip-access:add-ip {ip}` — adds an address or a subnet to the whitelist.
- `php artisan wobqqq.fortify:admin-ip-access:disable` — turns the module off.

## Tests

Pest 4 on Orchestra Testbench (Laravel 12) with the real `october/rain` and the core plugin from Composer. The licensed October modules are not installable in CI, so `tests/Stubs/October.php` reproduces the classes the plugins touch with October 4.4's behaviour (keep it identical to the core's copy). `tests/TestCase.php` boots the core and the module like October does. Read the `plugin-testing` skill.

## Git workflow

- `main` is protected: **never push to it and never force-push.** Every change goes through a pull request:
  1. branch off the latest `main`, named after the change (`fix/…`, `feat/…`, `chore/…`, `docs/…`);
  2. commit on the branch and `git push -u origin <branch>`;
  3. open a pull request with the template filled in (what changes, what it means for sites that upgrade);
  4. merge only once CI is green, then delete the branch.
- A release is a tag pushed on a merged commit of `main` (the `plugin-upgrades` skill says how); the tag is the only thing pushed outside a pull request.
- Code, comments, commit messages, pull requests, issues and documentation are written in **English**.

## Conventions

- `declare(strict_types=1);` in every PHP file; PSR-12 via php-cs-fixer (`(int)$x` without a space, imported classes).
- Code documents itself: names over comments. A comment explains a non-obvious *why*, in one sentence.
- DTOs are `final readonly`; services and transformers are `final`.
- October patterns over Laravel ones: model validation, form fields added in `backend.form.extendFields`, `Plugin.php` registration, the core's `lang` keys (read the `octobercms-*` skills).
- Commits: imperative subject saying what the change does for the site, a body with the why.
