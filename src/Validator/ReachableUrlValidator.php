<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ReachableUrlValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ReachableUrl) {
            throw new UnexpectedTypeException($constraint, ReachableUrl::class);
        }

        if (false === \is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        if (empty($value)) {
            return;
        }

        if (false === \filter_var($value, \FILTER_VALIDATE_URL)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }

        $host = \parse_url($value, \PHP_URL_HOST);
        if (empty($host) || false === \is_string($host)) {
            return;
        }

        $ip = \gethostbyname($host);
        if (false === \filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4 | \FILTER_FLAG_IPV6)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
