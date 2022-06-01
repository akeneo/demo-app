<?php

declare(strict_types=1);

namespace App\Service;

interface DnsLookupInterface
{
    public function ip(string $host): ?string;
}
