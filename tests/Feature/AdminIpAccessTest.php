<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Wobqqq\Fortify\Models\Fortify;
use Wobqqq\FortifyAdminIpAccess\Http\Middlewares\AdminIpAccessMiddleware;
use Wobqqq\FortifyAdminIpAccess\Services\AdminIpAccessService;
use Wobqqq\FortifyAdminIpAccess\Tests\TestCase;

function visitBackendFrom(string $ip): Response
{
    $request = Request::create('/admin', 'GET', [], [], [], ['REMOTE_ADDR' => $ip]);
    $response = app(AdminIpAccessMiddleware::class)->handle($request, static fn (): Response => new Response('backend'));

    return $response instanceof Response ? $response : throw new UnexpectedValueException('No response.');
}

it('guards the backend only, and only while enabled', function (): void {
    expect(Config::get('backend.middleware_group'))->toBe('web');

    configureAdminIpAccess([]);

    expect(Config::get('backend.middleware_group'))->toBe(['web', AdminIpAccessMiddleware::ALIAS])
        ->and(Config::get('cms.middleware_group'))->toBe('web');
});

it('lets in the whitelisted addresses, subnets and IPv6 notations', function (string $ip): void {
    configureAdminIpAccess(['admin_ip_access_ips' => [['ip' => '198.51.100.20'], ['ip' => '10.0.0.0/8'], ['ip' => '2001:db8::1'], ['ip' => '']]]);

    expect(visitBackendFrom($ip)->getContent())->toBe('backend');
})->with(['198.51.100.20', '10.20.30.40', '2001:db8::1', '2001:0db8:0000:0000:0000:0000:0000:0001']);

it('answers any other address 403 with the configured page', function (): void {
    configureAdminIpAccess([]);

    $response = visitBackendFrom('203.0.113.7');

    expect($response->getStatusCode())->toBe(403)
        ->and($response->getContent())->toContain('Access denied');
});

it('falls back to the denied page when the configured one does not exist', function (): void {
    configureAdminIpAccess(['admin_ip_access_view' => 'acme.theme::missing']);

    expect(visitBackendFrom('203.0.113.7')->getContent())->toContain('Access denied');
});

it('lets everyone in while the whitelist is empty rather than lock every administrator out', function (): void {
    configureAdminIpAccess(['admin_ip_access_ips' => []]);

    expect(visitBackendFrom('203.0.113.7')->getStatusCode())->toBe(200);
});

it('lets everyone in while disabled', function (): void {
    configureAdminIpAccess(['admin_ip_access_enabled' => false]);

    expect(app(AdminIpAccessService::class)->check('203.0.113.7'))->toBeTrue();
});

it('adds an address or a subnet from the console', function (string $ip): void {
    configureAdminIpAccess([]);

    expect(Artisan::call('wobqqq.fortify:admin-ip-access:add-ip', ['ip' => $ip]))->toBe(0)
        ->and(Artisan::output())->toContain('successfully added')
        ->and(Fortify::get('ip_firewall.admin_ip_access_ips'))->toBe([['ip' => '198.51.100.20'], ['ip' => $ip]]);
})->with(['203.0.113.7', '192.0.2.0/24', '2001:db8::/32']);

it('does not add an address the whitelist already lets in', function (string $ip): void {
    configureAdminIpAccess(['admin_ip_access_ips' => [['ip' => '198.51.100.20'], ['ip' => '10.0.0.0/8']]]);

    expect(Artisan::call('wobqqq.fortify:admin-ip-access:add-ip', ['ip' => $ip]))->toBe(0)
        ->and(Artisan::output())->toContain('already on the whitelist')
        ->and(Fortify::get('ip_firewall.admin_ip_access_ips'))->toHaveCount(2);
})->with(['198.51.100.20', '10.1.2.3', '10.0.0.0/8']);

it('refuses what is not an address or a subnet', function (string $ip): void {
    configureAdminIpAccess([]);

    expect(Artisan::call('wobqqq.fortify:admin-ip-access:add-ip', ['ip' => $ip]))->toBe(1)
        ->and(Artisan::output())->toContain('is not an IP address or a subnet')
        ->and(Fortify::get('ip_firewall.admin_ip_access_ips'))->toHaveCount(1);
})->with(['not-an-ip', '10.0.0.0/33', '2001:db8::/129', '10.0.0.0/x', '300.1.1.1']);

it('turns itself off from the console and keeps the whitelist', function (): void {
    configureAdminIpAccess([]);

    expect(Artisan::call('wobqqq.fortify:admin-ip-access:disable'))->toBe(0)
        ->and(Fortify::get('ip_firewall.admin_ip_access_enabled'))->toBeFalse()
        ->and(Fortify::get('ip_firewall.admin_ip_access_ips'))->toBe([['ip' => '198.51.100.20']]);

    TestCase::bootPlugins();

    expect(visitBackendFrom('203.0.113.7')->getStatusCode())->toBe(200);
});
