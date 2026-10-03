<?php

declare(strict_types=1);

namespace Wobqqq\FortifyAdminIpAccess\Console;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Wobqqq\FortifyAdminIpAccess\Dto\IpRangeDto;
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
        $argument = $this->argument('ip');

        try {
            $ipRange = IpRangeDto::fromString(is_string($argument) ? $argument : '');
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if (!$adminIpAccessService->addIp($ipRange)) {
            $this->info(sprintf('IP %s is already on the whitelist.', $ipRange->value));

            return self::SUCCESS;
        }

        $this->info(sprintf('IP %s has been successfully added to the whitelist.', $ipRange->value));

        return self::SUCCESS;
    }
}
