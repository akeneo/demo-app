<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use App\Service\DnsLookupInterface;

class FakeDnsLookup implements DnsLookupInterface
{
    public const NON_EXISTENT_DOMAIN = 'a-non-existent-domain.com';

    public function ip(string $host): ?string
    {
        if (self::NON_EXISTENT_DOMAIN === $host) {
            return null;
        }

        return '168.212.226.204';
    }
}
