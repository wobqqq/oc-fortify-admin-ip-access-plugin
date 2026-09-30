<?php

declare(strict_types=1);

namespace Wobqqq\FortifyAdminIpAccess\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View as IlluminateView;
use Wobqqq\Fortify\Enums\View;
use Wobqqq\FortifyAdminIpAccess\Instances\AdminIpAccessDtoInstance;
use Wobqqq\FortifyAdminIpAccess\Services\AdminIpAccessService;

final readonly class AdminIpAccessMiddleware
{
    public const ALIAS = 'fortify_admin_ip_access';

    public function __construct(private AdminIpAccessService $adminIpAccessService)
    {
    }

    /**
     * @param Closure(Request): mixed $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if ($this->adminIpAccessService->check((string)$request->ip())) {
            return $next($request);
        }

        $adminIpAccessDto = AdminIpAccessDtoInstance::instance()->get();

        $view = IlluminateView::exists($adminIpAccessDto->view)
            ? $adminIpAccessDto->view
            : View::DENIED->value;

        /** @var \Illuminate\Routing\ResponseFactory $response */
        $response = response();

        return $response->view($view, [], 403);
    }
}
