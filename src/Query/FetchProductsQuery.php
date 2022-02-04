<?php

declare(strict_types=1);

namespace App\Query;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\PimApi\Model\Product;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @phpstan-import-type RawProduct from \App\PimApi\Normalizer\ProductDenormalizer
 */
final class FetchProductsQuery
{
    public function __construct(
        private AkeneoPimClientInterface $pimApiClient,
        private DenormalizerInterface $denormalizer,
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

        $products = [];

        foreach ($rawProducts as $rawProduct) {
            $products[] = $this->denormalizer->denormalize(
                $rawProduct,
                Product::class,
                null,
                [
                    'locale' => $locale,
                    'withProductValues' => false,
                ],
            );
        }

        return $products;
    }
}
