<?php

declare(strict_types=1);

namespace Wobqqq\FortifyAdminIpAccess\Dto;

use InvalidArgumentException;

final readonly class IpRangeDto
{
    private function __construct(
        public string $value,
        public bool $isSubnet,
    ) {
    }

    /**
     * @throws InvalidArgumentException when the value is neither an IP address nor a CIDR subnet
     */
    public static function fromString(string $value): self
    {
        $value = trim($value);
        [$address, $mask] = array_pad(explode('/', $value, 2), 2, null);

        if (filter_var($address, FILTER_VALIDATE_IP) === false) {
            throw new InvalidArgumentException(sprintf('%s is not an IP address or a subnet.', $value));
        }

        $maxMask = filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false ? 128 : 32;

        if ($mask !== null && (!ctype_digit($mask) || (int)$mask > $maxMask)) {
            throw new InvalidArgumentException(sprintf('%s is not an IP address or a subnet.', $value));
        }

        return new self($value, $mask !== null);
    }
}
