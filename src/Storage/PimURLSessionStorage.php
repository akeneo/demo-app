<?php

declare(strict_types=1);

namespace App\Storage;

use Symfony\Component\HttpFoundation\RequestStack;

/*
 * **************************************************
 * * /!\ DO NOT USE in a production environment /!\ *
 * **************************************************
 *
 * This storage class is a simple implementation for the demo app purpose only.
 * Each PIM URL should be stored securely in your storage solution, like a database.
 */
class PimURLSessionStorage implements PimURLStorageInterface
{
    public function __construct(
        private RequestStack $requestStack
    ) {
    }

    public function getPimURL(): ?string
    {
        return $this->requestStack->getSession()->get('pim_url');
    }
}
