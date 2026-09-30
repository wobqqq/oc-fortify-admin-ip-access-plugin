# Admin IP Access

[![CI](https://github.com/wobqqq/oc-fortify-admin-ip-access-plugin/actions/workflows/ci.yml/badge.svg)](https://github.com/wobqqq/oc-fortify-admin-ip-access-plugin/actions/workflows/ci.yml)
[![October CMS](https://img.shields.io/badge/October%20CMS-3.x%20%7C%204.x-e24848)](https://octobercms.com/plugin/wobqqq-fortifyadminipaccess)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777bb4)](composer.json)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%20max-brightgreen)](phpstan.neon.dist)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE.md)

**Admin IP Access** is an extension for October CMS that allows you to restrict access to the admin panel by IP address.

It integrates seamlessly with the main [Fortify](https://octobercms.com/plugin/wobqqq-fortify) plugin and provides an additional layer of protection against unauthorized access.

## 📊 Security Dashboard Widget

Fortify includes a built-in dashboard widget that gives you a real-time overview of your system’s security status.

- Highlights critical vulnerabilities and misconfigurations
- Provides quick access to all security checks and tools
- Helps you identify and fix issues in one place

This widget acts as a central hub, allowing you to monitor and manage your application's security at a glance.

## 🚀 Features

- Restrict admin panel access to specific IP addresses
- Protect against unauthorized login attempts
- Easy IP whitelist management
- Seamless integration with Fortify security dashboard

## 🔗 Related Plugins

- [Fortify](https://octobercms.com/plugin/wobqqq-fortify) – comprehensive security suite
- [IP Blocker](https://octobercms.com/plugin/wobqqq-fortifyipblocker) – manually block specific IP addresses
- [Smart IP Blocker](https://octobercms.com/plugin/wobqqq-fortifysmartipblocker) – automatic IP blocking based on request rate
- [Input Sanitizer](https://octobercms.com/plugin/wobqqq-fortifyinputsanitizer) – block and sanitize malicious input
- [CSP](https://octobercms.com/plugin/wobqqq-fortifycsp) – add Content Security Policy headers to prevent XSS

## 📦 Requirements

- PHP 8.2 or higher
- October CMS 3.x or 4.x
- [Fortify](https://octobercms.com/plugin/wobqqq-fortify)

## 💻 Usage

All configuration and management is handled via the October CMS admin panel.

**Admin Panel:**
Navigate to `Settings -> Fortify` and enable **Admin IP Access**. Add allowed IPs through the interface.

**Console Commands:**

- Add a new IP to the whitelist:

```bash
php artisan wobqqq.fortify:admin-ip-access:add-ip {ip}
```

- Disable Admin IP Access:

```bash
php artisan wobqqq.fortify:admin-ip-access:disable
```

## ⬆️ Upgrading

- **1.0.3** — an administrator whose address is covered by a whitelisted subnet, or written in another IPv6 notation, is recognised. The whitelist rules, including the one that refuses a list without your own address, now apply on every save, and a saved whitelist is applied at once instead of up to an hour later. `add-ip` refuses anything that is not an IP address or a subnet and does not add an address the whitelist already covers.

## ⚠️ Good to know

- Only whitelist static addresses. If your address changes and you are locked out, add the new one over SSH with `php artisan wobqqq.fortify:admin-ip-access:add-ip <ip>`, or turn the module off with `php artisan wobqqq.fortify:admin-ip-access:disable`.
- Behind a load balancer, proxy or CDN, configure October's trusted proxies so that the visitor's IP, not the proxy's, is checked.
- While the module is enabled with an empty whitelist, every address is let in (so a mistake never locks everyone out); the dashboard widget shows the backend as unprotected until the list has an entry.

## 🔒 Security

Please report a vulnerability privately, as described in [SECURITY.md](SECURITY.md).

## 🛠️ Development

The toolchain runs in Docker, the host needs nothing but `docker` and `make`. The module is tested together with the [Fortify core](https://github.com/wobqqq/oc-fortify-plugin), which Composer installs from Packagist.

```bash
make install        # composer install
make code.fix       # composer normalize, Rector, PHP CS Fixer
make code.check     # composer validate/audit, php -l, YAML lint, PHP CS Fixer, Rector, PHPStan (level max)
make test.coverage  # Pest with coverage (90 % minimum)
make ready          # everything above
```

Every pull request runs the same checks on GitHub Actions, plus a syntax check on PHP 8.2 and a run against the latest core. Pushing a tag that matches the last version in `updates/version.yaml` releases it to the October CMS marketplace once CI has passed.

