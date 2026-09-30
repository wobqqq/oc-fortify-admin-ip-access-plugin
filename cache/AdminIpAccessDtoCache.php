<?php

declare(strict_types=1);

namespace Wobqqq\FortifyAdminIpAccess\Cache;

use Illuminate\Support\Facades\Cache;
use Throwable;
use Wobqqq\Fortify\Cache\BasicCache;
use Wobqqq\FortifyAdminIpAccess\Dto\AdminIpAccessDto;
use Wobqqq\FortifyAdminIpAccess\Transformers\FortifyTransformer;

final class AdminIpAccessDtoCache extends BasicCache
{
    public function get(): AdminIpAccessDto
    {
        $cacheKey = $this->cacheKey();

        try {
            $adminIpAccessDto = Cache::remember($cacheKey, self::TTL, FortifyTransformer::adminIpAccessDto(...));
        } catch (Throwable) {
            Cache::forget($cacheKey);
            $adminIpAccessDto = null;
        }

        return $adminIpAccessDto instanceof AdminIpAccessDto ? $adminIpAccessDto : FortifyTransformer::adminIpAccessDto();
    }

    public function clear(): void
    {
        Cache::forget($this->cacheKey());
    }
}
