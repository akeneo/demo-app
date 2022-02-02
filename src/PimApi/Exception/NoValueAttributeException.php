<?php

declare(strict_types=1);

namespace App\PimApi\Exception;

use Exception;
use Throwable;

class NoValueAttributeException extends Exception
{
    public function __construct($attributeCode, $code = 0, Throwable $previous = null)
    {
        parent::__construct(
            \sprintf('Attribute %s has no value.', $attributeCode),
            $code,
            $previous
        );
    }
}
