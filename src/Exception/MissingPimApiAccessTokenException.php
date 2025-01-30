<?php

declare(strict_types=1);

namespace App\Exception;

final class MissingPimApiAccessTokenException extends \Exception
{
    public function __construct(string $message = 'Missing Pim API access token.', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
