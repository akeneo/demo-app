<?php

declare(strict_types=1);

namespace App\Exception;

final class MissingPimUrlException extends \Exception
{
    public function __construct(string $message = 'Missing PIM URL.', int $code = 0, \Throwable|null $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
