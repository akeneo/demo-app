<?php

declare(strict_types=1);

namespace App\Query;

use App\PimApi\Model\Product;
use App\PimApi\Model\ProductValue;

/**
 * @phpstan-import-type RawProduct from AbstractProductQuery
 */
final class FetchProductQuery extends AbstractProductQuery
{
    public function fetch(string $identifier, string $locale): Product
    {
        /** @var RawProduct $rawProduct */
        $rawProduct = $this->pimApiClient->getProductApi()->get($identifier);
        $scope = $this->findFirstAvailableScope($rawProduct);

        $familyIdentifier = $rawProduct['family'];
        $rawFamily = $this->pimApiClient->getFamilyApi()->get($familyIdentifier);

        $label = (string) $this->findAttributeValue($rawProduct, $rawFamily['attribute_as_label'], $locale, $scope);

        $values = [];
        $attributes = $this->fetchAttributes([$rawProduct]);

        foreach ($rawProduct['values'] as $attributeIdentifier => $value) {
            $attribute = $attributes[$attributeIdentifier];

            if (!\in_array($attribute['type'], self::SUPPORTED_ATTRIBUTE_TYPES)) {
                continue;
            }

            $attributeValue = $this->findAttributeValue($rawProduct, $attributeIdentifier, $locale, $scope);

            if (null === $attributeValue) {
                continue;
            }

            $values[] = new ProductValue(
                $attribute['labels'][$locale] ?? \sprintf('[%s]', $attribute['code']),
                $attribute['type'],
                $attributeValue,
            );
        }

        return new Product($identifier, $label, $values);
    }
}
