<?php

declare(strict_types=1);

namespace Wobqqq\FortifyAdminIpAccess\Listeners;

use Arr;
use Backend;
use Backend\Widgets\Form;
use October\Rain\Events\Dispatcher;
use Request;
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
    public function __construct(
        private AdminIpAccessDtoCache $adminIpAccessDtoCache,
    ) {
    }


    /**
     * @param Dispatcher $event
     * @return void
     */
    public function subscribe($event): void
    {
        $event->listen(FortifyEvent::SERVICES_WIDGET_GROUP_ITEM_ADMIN_IP_ACCESS->value, function (WidgetGroupItemDto &$widgetGroupItemDto) {
            $this->serveWidgetGroupItem($widgetGroupItemDto);
        });

        $event->listen(FortifyEvent::MODEL_FORTIFY_INIT_SETTINGS_DATA->value, function (Fortify &$fortify) {
            $this->serveModelInitSettingsData($fortify);
        });

        Fortify::extend(function (Fortify $fortify) {
            $this->serveModel($fortify);

            $fortify->bindEvent('model.beforeSave', function () use ($fortify) {
                $this->filterEmptyIPs($fortify, 'admin_ip_access_ips');
            });

            $fortify->bindEvent('model.afterSave', function () {
                $this->adminIpAccessDtoCache->clear();
            });

            $fortify->bindEvent('model.afterDelete', function () {
                $this->adminIpAccessDtoCache->clear();
            });
        });

        $event->listen('backend.form.extendFields', function (Form $form) {
            if (!$form->getController() instanceof Settings || !$form->model instanceof Fortify || $form->isNested) {
                return;
            }

            /** @var Fortify $fortify */
            $fortify = $form->model;

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
        $color = $adminIpAccessDto->enabled === true
        && (count($adminIpAccessDto->exactIps) > 0 || count($adminIpAccessDto->cidrRanges)  > 0)
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
        $ipFirewall = (isset($fortify->ip_firewall) && is_array($fortify->ip_firewall)) ? $fortify->ip_firewall : [];

        if (!empty($ipFirewall)) {
            return;
        }

        $ipFirewall['admin_ip_access_enabled'] = false;
        $ipFirewall['admin_ip_access_view'] = View::DENIED->value;
        /** @noinspection PhpUndefinedFieldInspection */
        /** @phpstan-ignore-next-line */
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
        if (isset($fortify->ip_firewall) && is_array($fortify->ip_firewall)) {
            /** @var array<string, mixed> $ipFirewall */
            $ipFirewall = $fortify->ip_firewall;
        } else {
            $ipFirewall = [];
        }

        /** @var array<int, mixed> $ipTable */
        $ipTable = Arr::get($ipFirewall, 'admin_ip_access_ips', []);

        $ips = array_column($ipTable, 'ip');

        $ip = Request::ip();

        if (!in_array($ip, $ips)) {
            $ipTable[] = ['ip' => $ip];

            $ipFirewall['admin_ip_access_ips'] = $ipTable;
            /** @noinspection PhpUndefinedFieldInspection */
            /** @phpstan-ignore-next-line */
            $fortify->ip_firewall = $ipFirewall;
        }
    }

    private function filterEmptyIPs(Fortify $fortify, string $fieldName): void
    {
        if (isset($fortify->ip_firewall) && is_array($fortify->ip_firewall)) {
            /** @var array<string, mixed> $ipFirewall */
            $ipFirewall = $fortify->ip_firewall;
            /** @var array<int, array<string, string|null>> $ipsTable */

            $ipsTable = Arr::get($ipFirewall, $fieldName, []);
            foreach ($ipsTable as $key => $row) {
                /** @var string|null $ip */
                $ip = Arr::get($row, 'ip');
                $ip = trim((string)$ip);

                if (empty($ip)) {
                    unset($ipsTable[$key]);
                }
            }

            $ipFirewall[$fieldName] = $ipsTable;
            $fortify->ip_firewall = $ipFirewall;
        }
    }
}
