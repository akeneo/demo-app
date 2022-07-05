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
 * Each catalog id should be stored securely in your storage solution, like a database.
 */
class CatalogIdSessionStorage implements CatalogIdStorageInterface
{
    private const CATALOG_ID_SESSION_KEY = 'akeneo_pim_catalog_id';

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getCatalogId(): ?string
    {
        return $this->requestStack->getSession()->get(self::CATALOG_ID_SESSION_KEY);
    }

    public function setCatalogId(string $catalogId): void
    {
        $this->requestStack->getSession()->set(self::CATALOG_ID_SESSION_KEY, $catalogId);
    }
}
