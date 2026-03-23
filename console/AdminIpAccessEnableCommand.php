<?php

declare(strict_types=1);

namespace Wobqqq\FortifyAdminIpAccess\Console;

use Illuminate\Console\Command;
use Wobqqq\FortifyAdminIpAccess\Services\AdminIpAccessService;

final class AdminIpAccessEnableCommand extends Command
{
    /** @var string */
    protected $name = 'wobqqq.fortify:admin-ip-access:enable';

    /** @var string */
    protected $description = 'Enable Admin Ip Access.';

    public function handle(AdminIpAccessService $adminIpAccessService): void
    {
        $adminIpAccessService->enable();

        $this->info('Admin Ip Access enabled.');
    }
}
