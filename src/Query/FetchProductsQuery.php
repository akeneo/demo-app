<?php

declare(strict_types=1);

namespace App\Query;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Search\Operator;
use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\PimApi\Model\Product;

/**
 * @phpstan-import-type RawProduct from AbstractProductQuery
 */
final class FetchProductsQuery extends AbstractProductQuery
{
    public function __construct(
        protected AkeneoPimClientInterface $pimApiClient,
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
            $family = $families[$rawProduct['family']];

            $label = (string) $this->findAttributeValue($rawProduct, $family['attribute_as_label'], $locale, $scope);

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
}
