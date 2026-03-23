<?php

declare(strict_types=1);

namespace Wobqqq\FortifyAdminIpAccess\Cache;

use Illuminate\Support\Facades\Cache;
use Wobqqq\Fortify\Cache\BasicCache;
use Wobqqq\FortifyAdminIpAccess\Dto\AdminIpAccessDto;
use Wobqqq\FortifyAdminIpAccess\Transformers\FortifyTransformer;

final class AdminIpAccessDtoCache extends BasicCache
{
    public function get(): AdminIpAccessDto
    {
        $cacheKey = $this->cacheKey();

        /** @var AdminIpAccessDto $adminIpAccessDto */
        $adminIpAccessDto = Cache::remember($cacheKey, self::TTL, function () {
            return FortifyTransformer::adminIpAccessDto();
        });

        return $adminIpAccessDto;
    }

    public function clear(): void
    {
        Cache::forget($this->cacheKey());
    }
}
