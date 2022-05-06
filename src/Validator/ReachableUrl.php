<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

class ReachableUrl extends Constraint
{
    public $message = 'The url should be reachable.';
}
