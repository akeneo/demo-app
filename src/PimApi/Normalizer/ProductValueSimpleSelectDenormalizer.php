<?php

declare(strict_types=1);

namespace App\PimApi\Normalizer;

class ProductValueSimpleSelectDenormalizer extends AbstractProductValueDenormalizer
{
    protected function getAttributeType(): string
    {
        return AbstractProductValueDenormalizer::PIM_CATALOG_SIMPLE_SELECT;
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

            $options = $this->getAttributeOptions($attributeCode);

            foreach ($options as $option) {
                if ($value['data'] === $option['code']) {
                    foreach ($option['labels'] as $labelLocale => $label) {
                        if ($locale === $labelLocale) {
                            return $label;
                        }
                    }
                }
            }

            return \sprintf('[%s]', $value['data']);
        }

        return null;
    }

    /**
     * @return array<mixed>
     */
    private function getAttributeOptions(string $attributeCode): array
    {
        return $this->pimApiClient->getAttributeOptionApi()->listPerPage($attributeCode, 100)->getItems();
    }
}
