<?php

namespace App\Exception;

final class CatalogProductNotFoundException extends \Exception
{
    public function __construct(string $message = '', int $code = 0, \Throwable|null $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
