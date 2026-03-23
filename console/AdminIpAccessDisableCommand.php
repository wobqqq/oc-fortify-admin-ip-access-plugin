<?php

declare(strict_types=1);

namespace Wobqqq\FortifyAdminIpAccess\Console;

use Illuminate\Console\Command;
use Wobqqq\FortifyAdminIpAccess\Services\AdminIpAccessService;

final class AdminIpAccessDisableCommand extends Command
{
    /** @var string */
    protected $name = 'wobqqq.fortify:admin-ip-access:disable';

    /** @var string */
    protected $description = 'Disable Admin Ip Access.';

    public function handle(AdminIpAccessService $adminIpAccessService): void
    {
        $adminIpAccessService->disable();

        $this->info('Admin Ip Access disabled.');
    }
}
