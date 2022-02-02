<?php

declare(strict_types=1);

namespace App\Query;

/**
 * @phpstan-type RawProduct array{identifier: string, family: string, values: array<string, array{array{locale: string|null, scope: string|null, data: mixed}}>}
 */
class AbstractProductQuery
{
    protected const SUPPORTED_ATTRIBUTE_TYPES = [
        'pim_catalog_identifier',
        'pim_catalog_text',
        'pim_catalog_textarea',
        'pim_catalog_number',
        'pim_catalog_price_collection',
        'pim_catalog_boolean',
    ];

    /**
     * @param RawProduct $product
     */
    protected function findAttributeValue(
        array $product,
        string $attributeIdentifier,
        string $locale,
        ?string $scope
    ): string|bool|null {
        foreach ($product['values'][$attributeIdentifier] ?? [] as $value) {
            if (null !== $value['locale'] && $value['locale'] !== $locale) {
                continue;
            }

            if (null !== $value['scope'] && $value['scope'] !== $scope) {
                continue;
            }

            return $value['data'];
        }

        return null;
    }

    /**
     * @param RawProduct $product
     */
    protected function findFirstAvailableScope(array $product): ?string
    {
        foreach ($product['values'] as $values) {
            foreach ($values as $value) {
                if (null !== $value['scope']) {
                    return $value['scope'];
                }
            }
        }

        return null;
    }
}
