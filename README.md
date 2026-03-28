# Admin IP Access

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
- October CMS 3.0 or higher

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
