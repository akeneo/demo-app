<?php

declare(strict_types=1);

namespace App\Query;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Search\Operator;
use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\PimApi\Model\Product;
use App\PimApi\ProductValueDenormalizer;

/**
 * @phpstan-type RawProduct array{identifier: string, family: string, values: array<string, array{array{locale: string|null, scope: string|null, data: mixed}}>}
 */
final class FetchProductsQuery
{
    public function __construct(
        private AkeneoPimClientInterface $pimApiClient,
        private ProductValueDenormalizer $productValueDenormalizer,
    ) {
    }

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

        $products = [];

        foreach ($rawProducts as $rawProduct) {
            $rawFamily = $families[$rawProduct['family']];

            $label = isset($rawProduct['values'][$rawFamily['attribute_as_label']])
                ? (string) $this->productValueDenormalizer->denormalize(
                    $rawProduct['values'][$rawFamily['attribute_as_label']],
                    $locale,
                    $scope,
                )
                : $rawProduct['identifier']
            ;

            $values = [];

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
            fn (array $rawProduct) => $rawProduct['family'],
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
            foreach ($familyApiResponsePage->getItems() as $rawFamily) {
                $rawFamilies[] = $rawFamily;
            }
        }

        return \array_combine(\array_column($rawFamilies, 'code'), $rawFamilies);
    }

    /**
     * @param RawProduct $product
     */
    private function findFirstAvailableScope(array $product): ?string
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
