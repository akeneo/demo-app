<?php

declare(strict_types=1);

namespace App\Exception;

final class CatalogDisabledException extends \Exception
{
    public function __construct(string $message = 'Catalog disabled', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
