<?php

declare(strict_types=1);

namespace Wobqqq\FortifyAdminIpAccess\Console;

use Illuminate\Console\Command;
use Wobqqq\FortifyAdminIpAccess\Services\AdminIpAccessService;

final class AdminIpAccessAddIpCommand extends Command
{
    /** @var string */
    protected $name = 'wobqqq.fortify:admin-ip-access:add-ip';

    /** @var string */
    protected $signature = 'wobqqq.fortify:admin-ip-access:add-ip {ip : An IP address or a subnet such as 192.0.2.0/24}';

    /** @var string */
    protected $description = 'Add an IP to the whitelist to allow access to the admin panel.';

    public function handle(AdminIpAccessService $adminIpAccessService): int
    {
        $ip = $this->argument('ip');
        $ip = is_string($ip) ? trim($ip) : '';

        if (!$this->isIpOrSubnet($ip)) {
            $this->error(sprintf('%s is not an IP address or a subnet.', $ip));

            return self::FAILURE;
        }

        if (!$adminIpAccessService->addIp($ip)) {
            $this->info(sprintf('IP %s is already on the whitelist.', $ip));

            return self::SUCCESS;
        }

        $this->info(sprintf('IP %s has been successfully added to the whitelist.', $ip));

        return self::SUCCESS;
    }

    private function isIpOrSubnet(string $value): bool
    {
        [$address, $mask] = array_pad(explode('/', $value, 2), 2, null);

        if (filter_var($address, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        $maxMask = filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false ? 128 : 32;

        return $mask === null || (ctype_digit($mask) && (int)$mask <= $maxMask);
    }
}
