<?php

declare(strict_types=1);

namespace App\PimApi\Exception;

use Exception;
use Throwable;

class NotSupportedAttributeTypeException extends Exception
{
    public function __construct($attributeTypeName, $code = 0, Throwable $previous = null)
    {
        parent::__construct(
            \sprintf('Attribute type %s is not supported by the demo app', $attributeTypeName),
            $code,
            $previous
        );
    }
}
