<?php

declare(strict_types=1);

namespace Wobqqq\FortifyAdminIpAccess\Validator\Rules;

use Arr;
use Lang;
use Request;

final class AdminIpAccessCurrentIpRule
{
    /**
     * @param string $attribute
     * @param mixed $value
     * @param array<mixed, mixed> $params
     * @return bool
     */
    public function validate(string $attribute, $value, $params): bool
    {
        /** @var \Illuminate\Contracts\Foundation\Application $app */
        $app = app();
        $isConsole = $app->runningInConsole();
        $currenIp = Request::ip();

        if ($isConsole || empty($currenIp)) {
            return true;
        }

        if (empty($value) || !is_array($value)) {
            return true;
        }

        /** @var array<int, array<string, string|null>> $ips */
        $ips = $value;

        foreach ($ips as $ip) {
            $ip = Arr::get($ip, 'ip');

            if ($currenIp === $ip) {
                return true;
            }
        }

        return false;
    }

    public function message(): string
    {
        /** @var string $message */
        $message = Lang::get(
            'wobqqq.fortify::lang.validator_rules.admin_ip_access_current_ip',
            ['ip' => Request::ip()],
        );

        return $message;
    }
}
