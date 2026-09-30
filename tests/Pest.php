<?php

declare(strict_types=1);

use Wobqqq\Fortify\Models\Fortify;
use Wobqqq\FortifyAdminIpAccess\Tests\TestCase;

pest()->extend(TestCase::class)->in('Unit', 'Feature');

/**
 * @param array<string, mixed> $settings
 */
function configureAdminIpAccess(array $settings): void
{
    Fortify::set('ip_firewall', array_merge([
        'admin_ip_access_enabled' => true,
        'admin_ip_access_view' => 'wobqqq.fortify::denied',
        'admin_ip_access_ips' => [['ip' => '198.51.100.20']],
    ], $settings));

    TestCase::bootPlugins();
}
