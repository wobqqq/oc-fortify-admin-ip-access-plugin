<?php

declare(strict_types=1);

namespace Wobqqq\FortifyAdminIpAccess\Listeners;

use Backend\Widgets\Form;
use October\Rain\Events\PriorityDispatcher;
use Request;
use System\Controllers\Settings;
use Wobqqq\Fortify\Dto\WidgetGroupItemDto;
use Wobqqq\Fortify\Enums\FortifyEvent;
use Wobqqq\Fortify\Models\Fortify;
use Wobqqq\FortifyAdminIpAccess\Cache\AdminIpAccessDtoCache;
use Wobqqq\FortifyAdminIpAccess\Services\SettingsService;

final readonly class FortifyListener
{
    public function __construct(
        private SettingsService $settingsService,
        private AdminIpAccessDtoCache $adminIpAccessDtoCache,
    ) {
    }

    public function subscribe(PriorityDispatcher $event): void
    {
        $event->listen(FortifyEvent::SERVICES_WIDGET_GROUP_ITEM_ADMIN_IP_ACCESS->value, function (WidgetGroupItemDto &$widgetGroupItemDto): void {
            $this->settingsService->fillWidgetGroupItem($widgetGroupItemDto);
        });

        $event->listen(FortifyEvent::MODEL_FORTIFY_INIT_SETTINGS_DATA->value, function (Fortify &$fortify): void {
            $this->settingsService->applyDefaults($fortify);
        });

        Fortify::extend(function (Fortify $fortify): void {
            $this->settingsService->applyRules($fortify);
        });

        // Model events, not bindEvent(): the settings instance may predate this listener.
        $event->listen('eloquent.saving: ' . Fortify::class, function (Fortify $fortify): void {
            $this->settingsService->removeEmptyRows($fortify);
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

            $this->settingsService->applyRules($fortify);
            $this->settingsService->applyDefaults($fortify);
            $this->settingsService->addFields($form);
            $this->settingsService->presetCurrentIp($fortify, Request::ip());
        });
    }
}
