<?php

declare(strict_types=1);

namespace Wobqqq\FortifyAdminIpAccess\Dto;

final readonly class AdminIpAccessDto
{
    public function __construct(
        public bool   $enabled,
        public string $view,
        /** @var array<int, string> $cidrRanges */
        public array  $cidrRanges = [],
        /** @var array<string, int> $exactIps */
        public array  $exactIps = [],
    ) {
    }
}
