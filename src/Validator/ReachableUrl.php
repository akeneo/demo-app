<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ReachableUrl extends Constraint
{
    public string $message = 'The url should be reachable.';
}
