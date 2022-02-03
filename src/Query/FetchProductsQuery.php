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
final class FetchProductsQuery extends AbstractProductQuery
{
    /**
     * @return array<Product>
     */
    public function fetch(string $locale): array
    {
        $searchBuilder = new SearchBuilder();
        $searchBuilder->addFilter('enabled', '=', true);
        $searchFilters = $searchBuilder->getFilters();

        /** @var array<RawProduct> $rawProducts */
        $rawProducts = $this->pimApiClient->getProductApi()->listPerPage(
            10,
            false,
            [
                'search' => $searchFilters,
                'locales' => $locale,
            ]
        )->getItems();

        $scope = $this->findFirstAvailableScope($rawProducts[0]);

        $families = $this->fetchFamilies($rawProducts);
        $attributes = $this->fetchAttributes($rawProducts);

        $products = [];

        foreach ($rawProducts as $rawProduct) {
            $family = $families[$rawProduct['family']];

            $label = (string) $this->findAttributeValue($rawProduct, $family['attribute_as_label'], $locale, $scope);

            $values = [];

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

            $products[] = new Product($rawProduct['identifier'], $label, $values);
        }

        return $products;
    }

    /**
     * @param array<RawProduct> $rawProducts
     *
     * @return array<string, mixed>
     */
    private function fetchFamilies(array $rawProducts): array
    {
        $familiesCodes = \array_unique(\array_map(
            fn(array $rawProduct) => $rawProduct['family'],
            $rawProducts,
        ));

        $searchBuilder = new SearchBuilder();
        $searchBuilder->addFilter('code', Operator::IN, $familiesCodes);
        $searchFilters = $searchBuilder->getFilters();

        $familyApiResponsePage = $this->pimApiClient->getFamilyApi()->listPerPage(
            100,
            false,
            [
                'search' => $searchFilters,
            ]
        );

        $rawFamilies = $familyApiResponsePage->getItems();

        while (null !== $familyApiResponsePage = $familyApiResponsePage->getNextPage()) {
            \array_merge($rawFamilies, $familyApiResponsePage->getItems());
        }

        return \array_combine(\array_column($rawFamilies, 'code'), $rawFamilies);
    }
}
