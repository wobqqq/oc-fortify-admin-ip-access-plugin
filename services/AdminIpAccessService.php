<?php

declare(strict_types=1);

namespace Wobqqq\FortifyAdminIpAccess\Services;

use App;
use Config;
use Illuminate\Support\Collection;
use October\Rain\Router\CoreRouter;
use Symfony\Component\HttpFoundation\IpUtils;
use Wobqqq\Fortify\Models\Fortify;
use Wobqqq\FortifyAdminIpAccess\Dto\IpRangeDto;
use Wobqqq\FortifyAdminIpAccess\Http\Middlewares\AdminIpAccessMiddleware;
use Wobqqq\FortifyAdminIpAccess\Instances\AdminIpAccessDtoInstance;

final class AdminIpAccessService
{
    private const IPS_KEY = 'admin_ip_access_ips';

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

        /** @var CoreRouter $coreRoute */
        $coreRoute = App::make('router');
        $coreRoute->aliasMiddleware(AdminIpAccessMiddleware::ALIAS, AdminIpAccessMiddleware::class);

        $this->overrideConfig();
    }

    /**
     * An empty whitelist lets everyone in: locking every administrator out would be worse.
     */
    public function check(string $ip): bool
    {
        $adminIpAccessDto = AdminIpAccessDtoInstance::instance()->get();

        if (!$adminIpAccessDto->enabled || ($adminIpAccessDto->cidrRanges === [] && $adminIpAccessDto->exactIps === [])) {
            return true;
        }

        if (isset($adminIpAccessDto->exactIps[$ip])) {
            return true;
        }

        return IpUtils::checkIp($ip, array_merge(array_keys($adminIpAccessDto->exactIps), $adminIpAccessDto->cidrRanges));
    }

    /**
     * @return bool false when the whitelist already lets the address in
     */
    public function addIp(IpRangeDto $ipRange): bool
    {
        $ip = $ipRange->value;

        $ipFirewall = $this->ipFirewall();
        $rows = is_array($ipFirewall[self::IPS_KEY] ?? null) ? $ipFirewall[self::IPS_KEY] : [];

        $listed = [];

        foreach ($rows as $row) {
            if (is_array($row) && is_string($row['ip'] ?? null) && trim($row['ip']) !== '') {
                $listed[] = trim($row['ip']);
            }
        }

        if (in_array($ip, $listed, true) || (!$ipRange->isSubnet && $listed !== [] && IpUtils::checkIp($ip, $listed))) {
            return false;
        }

        $rows[] = ['ip' => $ip];
        $ipFirewall[self::IPS_KEY] = array_values($rows);

        Fortify::set('ip_firewall', $ipFirewall);

        return true;
    }

    public function disable(): void
    {
        $ipFirewall = $this->ipFirewall();
        $ipFirewall['admin_ip_access_enabled'] = false;

        Fortify::set('ip_firewall', $ipFirewall);
    }

    /**
     * @return array<string, mixed>
     */
    private function ipFirewall(): array
    {
        $ipFirewall = Fortify::get('ip_firewall');

        if ($ipFirewall instanceof Collection) {
            $ipFirewall = $ipFirewall->toArray();
        }

        /** @var array<string, mixed> $ipFirewall */
        $ipFirewall = is_array($ipFirewall) ? $ipFirewall : [];

        return $ipFirewall;
    }

    private function overrideConfig(): void
    {
        $middleware = Config::get('backend.middleware_group', []);
        $middleware = is_string($middleware) ? [$middleware] : (is_array($middleware) ? $middleware : []);
        $middleware = array_filter($middleware, static fn (mixed $name): bool => is_string($name) && $name !== '');

        $middleware[] = AdminIpAccessMiddleware::ALIAS;

        Config::set('backend.middleware_group', array_values(array_unique($middleware)));
    }
}
