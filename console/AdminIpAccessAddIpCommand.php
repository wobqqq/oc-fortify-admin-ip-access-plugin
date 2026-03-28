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
    protected $signature = 'wobqqq.fortify:admin-ip-access:add-ip {ip}';

    /** @var string */
    protected $description = 'Add an IP to the whitelist to allow access to the admin panel.';

    public function handle(AdminIpAccessService $adminIpAccessService): void
    {
        /** @var string|null $ip */
        $ip = $this->argument('ip');

        $adminIpAccessService->addIp((string)$ip);

        $this->info(sprintf('IP %s has been successfully added to the whitelist.', $ip));
    }
}
