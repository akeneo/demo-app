<?php

declare(strict_types=1);

namespace App\Service;

class DnsLookup implements DnsLookupInterface
{
    public function ip(string $host): ?string
    {
        $ip = \gethostbyname($host);

        if (!\filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4 | \FILTER_FLAG_IPV6)) {
            return null;
        }

        return $ip;
    }
}
