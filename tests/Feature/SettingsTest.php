<?php

declare(strict_types=1);

use Backend\Widgets\Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use System\Controllers\Settings;
use System\Models\SettingModel;
use Wobqqq\Fortify\Dto\WidgetGroupItemDto;
use Wobqqq\Fortify\Enums\FortifyEvent;
use Wobqqq\Fortify\Enums\WidgetItemColor;
use Wobqqq\Fortify\Models\Fortify;
use Wobqqq\Fortify\Transformers\FortifyTransformer as CoreTransformer;
use Wobqqq\FortifyAdminIpAccess\Cache\AdminIpAccessDtoCache;
use Wobqqq\FortifyAdminIpAccess\Instances\AdminIpAccessDtoInstance;
use Wobqqq\FortifyAdminIpAccess\Validator\Rules\AdminIpAccessCurrentIpRule;

function adminIpAccessSettingsForm(string $ip = '198.51.100.20'): Form
{
    app()->instance('request', Request::create('/admin/system/settings', 'GET', [], [], [], ['REMOTE_ADDR' => $ip]));

    $form = new Form(new Settings(), Fortify::instance());
    Event::dispatch('backend.form.extendFields', [$form]);

    return $form;
}

function adminIpAccessSettings(string $ip = '198.51.100.20'): Fortify
{
    $model = adminIpAccessSettingsForm($ip)->model;

    return $model instanceof Fortify ? $model : throw new UnexpectedValueException('The form is not the Fortify settings.');
}

it('adds its section to the Fortify settings form', function (): void {
    expect(adminIpAccessSettingsForm()->tabFields)->toHaveKeys([
        'ip_firewall[admin_ip_access_section]',
        'ip_firewall[admin_ip_access_enabled]',
        'ip_firewall[admin_ip_access_view]',
        'ip_firewall[admin_ip_access_ips]',
    ]);
});

it('starts disabled with the denied page and the administrator on the whitelist', function (): void {
    expect(adminIpAccessSettings('198.51.100.20')->ip_firewall)->toMatchArray([
        'admin_ip_access_enabled' => false,
        'admin_ip_access_view' => 'wobqqq.fortify::denied',
        'admin_ip_access_ips' => [['ip' => '198.51.100.20']],
    ]);
});

it('fills in its defaults when another module already stored firewall settings', function (): void {
    Fortify::set('ip_firewall', ['ip_blocker_enabled' => true]);

    expect(adminIpAccessSettings()->ip_firewall)->toMatchArray([
        'ip_blocker_enabled' => true,
        'admin_ip_access_enabled' => false,
        'admin_ip_access_view' => 'wobqqq.fortify::denied',
    ]);
});

it('does not add the administrator again when a subnet already covers them', function (): void {
    Fortify::set('ip_firewall', ['admin_ip_access_ips' => [['ip' => '198.51.100.0/24']]]);

    expect(adminIpAccessSettings('198.51.100.20')->ip_firewall)
        ->toHaveKey('admin_ip_access_ips', [['ip' => '198.51.100.0/24']]);
});

it('refuses a whitelist that locks out the administrator saving it', function (mixed $ips, bool $passes): void {
    $model = adminIpAccessSettings();
    (new ReflectionProperty(app(), 'isRunningInConsole'))->setValue(app(), false);

    $data = [
        'config' => ['password_policy_min_length' => 12, 'session_lifetime' => 30],
        'ip_firewall' => ['admin_ip_access_view' => 'wobqqq.fortify::denied', 'admin_ip_access_ips' => $ips],
    ];

    expect(Validator::make($data, $model->rules)->passes())->toBe($passes);
})->with([
    [[['ip' => '198.51.100.20']], true],
    [[['ip' => '198.51.100.0/24']], true],
    [[['ip' => '203.0.113.1']], false],
    [[['ip' => '198.51.100.20; drop']], false],
    [[], true],
]);

it('explains the refusal with the administrator\'s escaped address', function (): void {
    app()->instance('request', Request::create('/', 'POST', [], [], [], ['REMOTE_ADDR' => '198.51.100.20']));

    expect((new AdminIpAccessCurrentIpRule())->message())->toContain('198.51.100.20');
});

it('drops the empty rows of the whitelist when it is saved', function (): void {
    configureAdminIpAccess(['admin_ip_access_ips' => [['ip' => ' '], ['ip' => '198.51.100.20'], ['ip' => null], 'broken']]);

    expect(Fortify::get('ip_firewall.admin_ip_access_ips'))->toBe([['ip' => '198.51.100.20']]);
});

it('applies a saved whitelist at once, even to a settings instance created before the module', function (): void {
    configureAdminIpAccess([]);
    AdminIpAccessDtoInstance::forgetInstance();
    expect(app(AdminIpAccessDtoCache::class)->get()->exactIps)->toBe(['198.51.100.20' => 1]);

    October\Rain\Extension\Container::clearExtensions();
    SettingModel::clearInternalCache();
    Fortify::set('ip_firewall', ['admin_ip_access_enabled' => true, 'admin_ip_access_ips' => [['ip' => '203.0.113.9']]]);

    expect(app(AdminIpAccessDtoCache::class)->get()->exactIps)->toBe(['203.0.113.9' => 1]);
});

it('shows on the dashboard whether a whitelist protects the backend', function (array $settings, WidgetItemColor $color): void {
    /** @var array<string, mixed> $settings */
    configureAdminIpAccess($settings);

    $item = CoreTransformer::widgetGroupItemDto('placeholder');
    Event::dispatch(FortifyEvent::SERVICES_WIDGET_GROUP_ITEM_ADMIN_IP_ACCESS->value, [&$item]);

    expect($item)->toBeInstanceOf(WidgetGroupItemDto::class)
        ->and($item->color)->toBe($color);
})->with([
    [[], WidgetItemColor::SUCCESS],
    [['admin_ip_access_ips' => []], WidgetItemColor::DANGER],
    [['admin_ip_access_enabled' => false], WidgetItemColor::DANGER],
]);

it('rebuilds a cached whitelist the previous version wrote in another shape', function (): void {
    configureAdminIpAccess([]);

    Cache::shouldReceive('remember')->once()->andThrow(new TypeError('Cannot assign string to property'));
    Cache::shouldReceive('forget')->once();

    expect(app(AdminIpAccessDtoCache::class)->get()->exactIps)->toBe(['198.51.100.20' => 1]);
});
