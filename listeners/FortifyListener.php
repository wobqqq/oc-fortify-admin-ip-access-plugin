<?php

declare(strict_types=1);

namespace Wobqqq\FortifyAdminIpAccess\Listeners;

use Backend;
use Backend\Widgets\Form;
use October\Rain\Events\PriorityDispatcher;
use Request;
use Symfony\Component\HttpFoundation\IpUtils;
use System\Controllers\Settings;
use Wobqqq\Fortify\Dto\WidgetGroupItemDto;
use Wobqqq\Fortify\Enums\FortifyEvent;
use Wobqqq\Fortify\Enums\View;
use Wobqqq\Fortify\Enums\WidgetItemColor;
use Wobqqq\Fortify\Models\Fortify;
use Wobqqq\Fortify\Transformers\FortifyTransformer;
use Wobqqq\FortifyAdminIpAccess\Cache\AdminIpAccessDtoCache;
use Wobqqq\FortifyAdminIpAccess\Instances\AdminIpAccessDtoInstance;

final readonly class FortifyListener
{
    private const IPS_KEY = 'admin_ip_access_ips';

    public function __construct(
        private AdminIpAccessDtoCache $adminIpAccessDtoCache,
    ) {
    }

    public function subscribe(PriorityDispatcher $event): void
    {
        $event->listen(FortifyEvent::SERVICES_WIDGET_GROUP_ITEM_ADMIN_IP_ACCESS->value, function (WidgetGroupItemDto &$widgetGroupItemDto): void {
            $this->serveWidgetGroupItem($widgetGroupItemDto);
        });

        $event->listen(FortifyEvent::MODEL_FORTIFY_INIT_SETTINGS_DATA->value, function (Fortify &$fortify): void {
            $this->serveModelInitSettingsData($fortify);
        });

        Fortify::extend(function (Fortify $fortify): void {
            $this->serveModel($fortify);
        });

        // Model events, not bindEvent(): the settings instance may predate this listener.
        $event->listen('eloquent.saving: ' . Fortify::class, function (Fortify $fortify): void {
            $this->filterEmptyIps($fortify);
        });

        $event->listen(
            ['eloquent.saved: ' . Fortify::class, 'eloquent.deleted: ' . Fortify::class],
            function (): void {
                $this->adminIpAccessDtoCache->clear();
            },
        );

        $event->listen('backend.form.extendFields', function (Form $form): void {
            if (!$form->getController() instanceof Settings || !$form->model instanceof Fortify || $form->isNested) {
                return;
            }

            $fortify = $form->model;

            $this->serveModel($fortify);
            $this->serveModelInitSettingsData($fortify);
            $this->serveFields($form);
            $this->presetCurrentIp($fortify);
        });
    }

    private function serveWidgetGroupItem(WidgetGroupItemDto &$widgetGroupItemDto): void
    {
        $settingsLink = FortifyTransformer::widgetItemLinkDto(
            'wobqqq.fortify::lang.buttons.edit',
            Backend::url('system/settings/update/wobqqq/fortify/fortify#primarytab-ip-firewall'),
            'icon-wrench',
        );
        $adminIpAccessDto = AdminIpAccessDtoInstance::instance()->get();
        $color = $adminIpAccessDto->enabled && ($adminIpAccessDto->exactIps !== [] || $adminIpAccessDto->cidrRanges !== [])
            ? WidgetItemColor::SUCCESS
            : WidgetItemColor::DANGER;
        $widgetGroupItemDto = FortifyTransformer::widgetGroupItemDto(
            'wobqqq.fortify::lang.fields.admin_ip_access',
            [$settingsLink],
            $color,
            'icon-ban',
        );
    }

    private function serveModelInitSettingsData(Fortify $fortify): void
    {
        $ipFirewall = is_array($fortify->ip_firewall) ? $fortify->ip_firewall : [];

        $ipFirewall['admin_ip_access_enabled'] ??= false;
        $ipFirewall['admin_ip_access_view'] ??= View::DENIED->value;

        $fortify->ip_firewall = $ipFirewall;
    }

    private function serveModel(Fortify $fortify): void
    {
        $fortify->attributeNames['ip_firewall.admin_ip_access_ips.*.ip'] = 'wobqqq.fortify::lang.fields.ip';

        $fortify->rules['ip_firewall.admin_ip_access_ips.*.ip'] = 'nullable|regex:/^[0-9a-fA-F\.:]+(\/\d{1,3})?$/|max:100';
        $fortify->rules['ip_firewall.admin_ip_access_ips'] = 'nullable|admin_ip_access_current_ip|array|max:100';
        $fortify->rules['ip_firewall.admin_ip_access_view'] = 'required|string|max:100';
    }

    private function serveFields(Form $form): void
    {
        $form->removeField('ip_firewall[admin_ip_access_section]');
        $form->removeField('ip_firewall[admin_ip_access_plugin]');
        $form->addTabFields([
            'ip_firewall[admin_ip_access_section]' => [
                'label' => 'wobqqq.fortify::lang.fields.admin_ip_access',
                'type' => 'section',
                'span' => 'full',
                'tab' => 'wobqqq.fortify::lang.tabs.ip_firewall',
            ],

            'ip_firewall[admin_ip_access_enabled]' => [
                'label' => 'wobqqq.fortify::lang.fields.enabled',
                'span' => 'full',
                'type' => 'switch',
                'tab' => 'wobqqq.fortify::lang.tabs.ip_firewall',
                'default' => false,
                'comment' => 'wobqqq.fortify::lang.comments.admin_ip_access_enabled',
                'commentHtml' => true,
            ],

            'ip_firewall[admin_ip_access_view]' => [
                'label' => 'wobqqq.fortify::lang.fields.view',
                'span' => 'full',
                'required' => true,
                'type' => 'dropdown',
                'default' => 'wobqqq.fortify::denied',
                'tab' => 'wobqqq.fortify::lang.tabs.ip_firewall',
                'comment' => 'wobqqq.fortify::lang.comments.admin_ip_access_view',
                'options' => 'getViewOptions',
                'trigger' => [
                    'action' => 'show',
                    'field' => 'ip_firewall[admin_ip_access_enabled]',
                    'condition' => 'checked',
                ],
            ],

            'ip_firewall[admin_ip_access_ips]' => [
                'label' => 'wobqqq.fortify::lang.fields.ips',
                'type' => 'datatable',
                'span' => 'full',
                'tab' => 'wobqqq.fortify::lang.tabs.ip_firewall',
                'adding' => true,
                'deleting' => true,
                'required' => true,
                'searching' => false,
                'recordsPerPage' => 20,
                'commentAbove' => 'wobqqq.fortify::lang.comments.admin_ip_access_ips',
                'comment' => 'wobqqq.fortify::lang.comments.admin_ip_access_ips_example',
                'commentHtml' => true,
                'trigger' => [
                    'action' => 'show',
                    'field' => 'ip_firewall[admin_ip_access_enabled]',
                    'condition' => 'checked',
                ],
                'columns' => [
                    'ip' => [
                        'type' => 'string',
                        'title' => 'wobqqq.fortify::lang.fields.ip',
                    ],
                ],
            ],
        ]);
    }

    private function presetCurrentIp(Fortify $fortify): void
    {
        $ipFirewall = is_array($fortify->ip_firewall) ? $fortify->ip_firewall : [];
        $ipTable = is_array($ipFirewall[self::IPS_KEY] ?? null) ? $ipFirewall[self::IPS_KEY] : [];

        $ips = [];

        foreach ($ipTable as $row) {
            if (is_array($row) && is_string($row['ip'] ?? null) && trim($row['ip']) !== '') {
                $ips[] = trim($row['ip']);
            }
        }

        $ip = Request::ip();

        if (is_string($ip) && ($ips === [] || !IpUtils::checkIp($ip, $ips))) {
            $ipTable[] = ['ip' => $ip];
            $ipFirewall[self::IPS_KEY] = $ipTable;
            $fortify->ip_firewall = $ipFirewall;
        }
    }

    private function filterEmptyIps(Fortify $fortify): void
    {
        if (!is_array($fortify->ip_firewall) || !is_array($fortify->ip_firewall[self::IPS_KEY] ?? null)) {
            return;
        }

        $ipFirewall = $fortify->ip_firewall;
        $ipFirewall[self::IPS_KEY] = array_values(array_filter(
            $fortify->ip_firewall[self::IPS_KEY],
            static fn (mixed $row): bool => is_array($row) && is_scalar($row['ip'] ?? null) && trim((string)$row['ip']) !== '',
        ));

        $fortify->ip_firewall = $ipFirewall;
    }
}
