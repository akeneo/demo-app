<?php

declare(strict_types=1);

namespace App\Query\Product;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use App\PimApi\Model\Product;
use App\PimApi\Normalizer\ProductNormalizer;

final class FetchProductQuery
{
    public function __construct(
        private AkeneoPimClientInterface $pimApiClient,
        private ProductNormalizer $productNormalizer,
    ) {
    }

    public function fetch(string $identifier, string $locale): Product
    {
        $rawPimApiProduct = $this->pimApiClient->getProductApi()->get($identifier);
        $scope = $this->findFirstAvailableScope($rawPimApiProduct);

        return $this->productNormalizer->denormalizeFromApi($rawPimApiProduct, $locale, $scope, true);
    }

    /**
     * @param array<mixed> $rawPimApiProduct
     */
    private function findFirstAvailableScope(array $rawPimApiProduct): ?string
    {
        foreach ($rawPimApiProduct['values'] as $values) {
            foreach ($values as $value) {
                if (null !== $value['scope']) {
                    return $value['scope'];
                }
            }
        }

        return null;
    }
}
