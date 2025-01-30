<?php

declare(strict_types=1);

namespace App\Exception;

final class CatalogNotFoundException extends \Exception
{
    public function __construct(string $message = 'Catalog not found', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
