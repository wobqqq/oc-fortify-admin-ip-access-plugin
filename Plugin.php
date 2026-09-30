<?php

declare(strict_types=1);

namespace Wobqqq\FortifyAdminIpAccess;

use Event;
use System\Classes\PluginBase;
use Validator;
use Wobqqq\FortifyAdminIpAccess\Console\AdminIpAccessAddIpCommand;
use Wobqqq\FortifyAdminIpAccess\Console\AdminIpAccessDisableCommand;
use Wobqqq\FortifyAdminIpAccess\Listeners\FortifyListener;
use Wobqqq\FortifyAdminIpAccess\Services\AdminIpAccessService;
use Wobqqq\FortifyAdminIpAccess\Validator\Rules\AdminIpAccessCurrentIpRule;

final class Plugin extends PluginBase
{
    /** @var array<int, string> */
    public $require = ['Wobqqq.Fortify'];

    public function register(): void
    {
        $this->registerConsoleCommand('wobqqq.fortify:admin-ip-access:add-ip', AdminIpAccessAddIpCommand::class);
        $this->registerConsoleCommand('wobqqq.fortify:admin-ip-access:disable', AdminIpAccessDisableCommand::class);
    }

    public function boot(): void
    {
        $this->registerEvents();
        $this->registerValidatorRules();
        $this->runService();
    }

    private function registerEvents(): void
    {
        Event::subscribe(FortifyListener::class);
    }

    private function registerValidatorRules(): void
    {
        Validator::extend('admin_ip_access_current_ip', AdminIpAccessCurrentIpRule::class);
    }

    private function runService(): void
    {
        /** @var AdminIpAccessService $adminIpAccessService */
        $adminIpAccessService = app(AdminIpAccessService::class);
        $adminIpAccessService->addMiddleware();
    }
}
