<?php

declare(strict_types=1);

namespace App\Query;

use Akeneo\Pim\ApiClient\Search\Operator;
use Akeneo\Pim\ApiClient\Search\SearchBuilder;
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

        $label = (string) $this->findAttributeValue(
            $rawProduct,
            $rawFamily['attribute_as_label'],
            AbstractProductQuery::PIM_CATALOG_TEXT,
            $locale,
            $scope
        );

        $values = [];
        $attributes = $this->fetchAttributes([$rawProduct]);

        foreach ($rawProduct['values'] as $attributeIdentifier => $value) {
            $attribute = $attributes[$attributeIdentifier];

            if (!\in_array($attribute['type'], self::SUPPORTED_ATTRIBUTE_TYPES)) {
                continue;
            }

            $attributeValue = $this->findAttributeValue(
                $rawProduct,
                $attributeIdentifier,
                $attribute['type'],
                $locale,
                $scope
            );

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

    /**
     * @param array<RawProduct> $rawProducts
     *
     * @return array<string, mixed>
     */
    private function fetchAttributes(array $rawProducts): array
    {
        $attributesCodes = \array_keys(\array_merge(...\array_column($rawProducts, 'values')));

        $searchBuilder = new SearchBuilder();
        $searchBuilder->addFilter('code', Operator::IN, $attributesCodes);
        $searchFilters = $searchBuilder->getFilters();

        $attributeApiResponsePage = $this->pimApiClient->getAttributeApi()->listPerPage(
            100,
            false,
            [
                'search' => $searchFilters,
            ]
        );

        $rawAttributes = $attributeApiResponsePage->getItems();

        while (null !== $attributeApiResponsePage = $attributeApiResponsePage->getNextPage()) {
            foreach ($attributeApiResponsePage->getItems() as $rawAttribute) {
                $rawAttributes[] = $rawAttribute;
            }
        }

        return \array_combine(\array_column($rawAttributes, 'code'), $rawAttributes);
    }
}
