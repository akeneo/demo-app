<?php

declare(strict_types=1);

namespace App\Query\Product;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\PimApi\Model\Product;
use App\PimApi\Normalizer\ProductNormalizer;

final class FetchProductsQuery
{
    public function __construct(
        private AkeneoPimClientInterface $pimApiClient,
        private ProductNormalizer $productNormalizer,
    ) {
    }

    /**
     * @return Product[]
     */
    public function fetch(string $locale): array
    {
        $searchBuilder = new SearchBuilder();
        $searchBuilder->addFilter('enabled', '=', true);
        $searchFilters = $searchBuilder->getFilters();

        $firstPage = $this->pimApiClient->getProductApi()->listPerPage(10, true, ['search' => $searchFilters]);
        $rawPimApiProducts = $firstPage->getItems();

        $scope = $this->findFirstAvailableScope($rawPimApiProducts);

        $products = [];

        foreach ($rawPimApiProducts as $item) {
            $product = $this->productNormalizer->denormalizeFromApi($item, $locale, $scope);

            $products[] = $product;
        }

        return $products;
    }

    /**
     * @param array<mixed> $rawPimApiProducts
     */
    private function findFirstAvailableScope(array $rawPimApiProducts): ?string
    {
        foreach ($rawPimApiProducts as $rawPimApiProduct) {
            foreach ($rawPimApiProduct['values'] as $values) {
                foreach ($values as $value) {
                    if (null !== $value['scope']) {
                        return $value['scope'];
                    }
                }
            }
        }

        return null;
    }
}
