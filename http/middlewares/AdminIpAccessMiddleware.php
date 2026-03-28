<?php

declare(strict_types=1);

namespace Wobqqq\FortifyAdminIpAccess\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View as IlluminateView;
use Wobqqq\Fortify\Enums\View;
use Wobqqq\FortifyAdminIpAccess\Instances\AdminIpAccessDtoInstance;
use Wobqqq\FortifyAdminIpAccess\Services\AdminIpAccessService;

final class AdminIpAccessMiddleware
{
    public const ALIAS = 'fortify_admin_ip_access';

    public function __construct(private readonly AdminIpAccessService $adminIpAccessService)
    {
    }

    /**
     * @param Request $request
     * @param Closure $next
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response|mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();

        if ($this->adminIpAccessService->check((string)$ip)) {
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
