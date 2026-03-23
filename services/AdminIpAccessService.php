<?php

declare(strict_types=1);

namespace Wobqqq\FortifyAdminIpAccess\Services;

use Arr;
use Config;
use Symfony\Component\HttpFoundation\IpUtils;
use Wobqqq\Fortify\Models\Fortify;
use Wobqqq\FortifyAdminIpAccess\Http\Middlewares\AdminIpAccessMiddleware;
use Wobqqq\FortifyAdminIpAccess\Instances\AdminIpAccessDtoInstance;

final class AdminIpAccessService
{
    private static bool $addMiddleware = false;

    public function addMiddleware(): void
    {
        if (self::$addMiddleware) {
            return;
        }

        self::$addMiddleware = true;

        $adminIpAccessDto = AdminIpAccessDtoInstance::instance()->get();

        if (!$adminIpAccessDto->enabled) {
            return;
        }

        $this->overrideConfig();
    }

    public function check(string $ip): bool
    {
        $adminIpAccessDto = AdminIpAccessDtoInstance::instance()->get();

        if (!$adminIpAccessDto->enabled) {
            return true;
        }

        if (empty($adminIpAccessDto->cidrRanges) && empty($adminIpAccessDto->exactIps)) {
            return true;
        }

        if (isset($adminIpAccessDto->exactIps[$ip])) {
            return true;
        }

        if (!empty($adminIpAccessDto->cidrRanges) && IpUtils::checkIp($ip, $adminIpAccessDto->cidrRanges)) {
            return true;
        }

        return false;
    }

    public function addIp(string $ip): void
    {
        $ip = trim($ip);

        if (empty($ip)) {
            return;
        }

        /** @var array<string, mixed>|\Illuminate\Support\Collection<int, mixed> $ipFirewall */
        $ipFirewall = Fortify::get('ip_firewall');

        if ($ipFirewall instanceof \Illuminate\Support\Collection) {
            $ipFirewall = $ipFirewall->toArray();
        }

        $ipFirewall = !is_array($ipFirewall) ? [] : $ipFirewall;

        /** @var array<int, array<string, string>> $adminIpAccessIps */
        $adminIpAccessIps = Arr::get($ipFirewall, 'admin_ip_access_ips', []);
        $adminIpAccessIps[] = ['ip' => $ip];

        $ipFirewall['admin_ip_access_ips'] = $adminIpAccessIps;

        Fortify::set('ip_firewall', $ipFirewall);
    }

    public function enable(): void
    {
        /** @var array<string, mixed>|\Illuminate\Support\Collection<int, mixed> $ipFirewall */
        $ipFirewall = Fortify::get('ip_firewall');

        if ($ipFirewall instanceof \Illuminate\Support\Collection) {
            $ipFirewall = $ipFirewall->toArray();
        }

        $ipFirewall = !is_array($ipFirewall) ? [] : $ipFirewall;

        $ipFirewall['admin_ip_access_enabled'] = true;

        Fortify::set('ip_firewall', $ipFirewall);
    }

    public function disable(): void
    {
        /** @var array<string, mixed>|\Illuminate\Support\Collection<int, mixed> $ipFirewall */
        $ipFirewall = Fortify::get('ip_firewall');

        if ($ipFirewall instanceof \Illuminate\Support\Collection) {
            $ipFirewall = $ipFirewall->toArray();
        }

        $ipFirewall = !is_array($ipFirewall) ? [] : $ipFirewall;

        $ipFirewall['admin_ip_access_enabled'] = false;

        Fortify::set('ip_firewall', $ipFirewall);
    }

    private function overrideConfig(): void
    {
        /** @var string|null|array<int, string> $middleware */
        $middleware = Config::get('backend.middleware_group', []);

        if (is_string($middleware)) {
            $middleware = [$middleware];
        }

        if (empty($middleware)) {
            $middleware = [];
        }

        $middleware[] = AdminIpAccessMiddleware::ALIAS;
        /** @var array<int, string> $middleware */
        $middleware = array_unique($middleware);
        $middleware = array_filter($middleware);

        Config::set('backend.middleware_group', $middleware);
    }
}
