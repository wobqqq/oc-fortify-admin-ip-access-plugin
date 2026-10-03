<?php

declare(strict_types=1);

use Wobqqq\FortifyAdminIpAccess\Dto\IpRangeDto;

it('accepts addresses and subnets', function (string $value, bool $isSubnet): void {
    $ipRange = IpRangeDto::fromString(" {$value} ");

    expect($ipRange->value)->toBe($value)->and($ipRange->isSubnet)->toBe($isSubnet);
})->with([
    ['192.0.2.7', false],
    ['192.0.2.0/24', true],
    ['2001:db8::1', false],
    ['2001:db8::/32', true],
]);

it('refuses anything else', function (string $value): void {
    expect(fn (): IpRangeDto => IpRangeDto::fromString($value))->toThrow(InvalidArgumentException::class);
})->with(['', 'example.com', '192.0.2.0/33', '2001:db8::/129', '192.0.2.0/x', '192.0.2.0/']);
