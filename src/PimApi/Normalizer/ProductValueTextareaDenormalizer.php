<?php

declare(strict_types=1);

namespace App\PimApi\Normalizer;

class ProductValueTextareaDenormalizer extends AbstractProductValueDenormalizer
{
    protected function getAttributeType(): string
    {
        return AbstractProductValueDenormalizer::PIM_CATALOG_TEXTAREA;
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

            return (string) $value['data'];
        }

        return null;
    }
}
