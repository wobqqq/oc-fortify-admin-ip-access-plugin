<?php

declare(strict_types=1);

arch('every file declares strict types')
    ->expect('Wobqqq\FortifyAdminIpAccess')
    ->toUseStrictTypes();

arch('no debugging calls are left behind')
    ->expect(['dd', 'dump', 'var_dump', 'print_r', 'ray', 'die', 'exit'])
    ->not->toBeUsed();

arch('data transfer objects are immutable')
    ->expect('Wobqqq\FortifyAdminIpAccess\Dto')
    ->toBeFinal()
    ->toBeReadonly();
