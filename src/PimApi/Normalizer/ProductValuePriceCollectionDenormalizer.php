<?php

declare(strict_types=1);

namespace App\PimApi\Normalizer;

class ProductValuePriceCollectionDenormalizer extends AbstractProductValueDenormalizer
{
    protected function getAttributeType(): string
    {
        return AbstractProductValueDenormalizer::PIM_CATALOG_PRICE_COLLECTION;
    }

    protected function findAttributeValue(
        array $values,
        ?string $locale,
        ?string $scope,
        string $attributeCode,
    ): string|bool|int|float|null {
        foreach ($values as $value) {
            if (null !== $value['locale'] && $value['locale'] !== $locale) {
                continue;
            }

            if (null !== $value['scope'] && $value['scope'] !== $scope) {
                continue;
            }

            $prices = \array_map(
                fn (array $data) => \sprintf('%s %s', $data['amount'], $data['currency']),
                $value['data'],
            );

            return \implode('; ', $prices);
        }

        return null;
    }
}
