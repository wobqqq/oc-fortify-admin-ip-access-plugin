<?php

declare(strict_types=1);

namespace Wobqqq\FortifyAdminIpAccess\Instances;

use October\Rain\Support\Traits\Singleton;
use Wobqqq\FortifyAdminIpAccess\Cache\AdminIpAccessDtoCache;
use Wobqqq\FortifyAdminIpAccess\Dto\AdminIpAccessDto;

final class AdminIpAccessDtoInstance
{
    use Singleton;

    private ?AdminIpAccessDto $adminIpAccessDto = null;

    public function get(): AdminIpAccessDto
    {
        if ($this->adminIpAccessDto instanceof AdminIpAccessDto) {
            return $this->adminIpAccessDto;
        }

        /** @var AdminIpAccessDtoCache $adminIpAccessDtoCache */
        $adminIpAccessDtoCache = app(AdminIpAccessDtoCache::class);

        return $this->adminIpAccessDto = $adminIpAccessDtoCache->get();
    }
}
