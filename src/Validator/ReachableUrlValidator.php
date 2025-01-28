<?php

declare(strict_types=1);

namespace App\Validator;

use App\Service\DnsLookupInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ReachableUrlValidator extends ConstraintValidator
{
    public function __construct(private DnsLookupInterface $dnsLookup)
    {
    }

    /**
     * @return void
     */
    public function validate(mixed $value, Constraint $constraint)
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
        if (empty($host)) {
            return;
        }

        if (null === $this->dnsLookup->ip($host)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
