<?php

declare(strict_types=1);

namespace App\Query;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use App\PimApi\Model\Product;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @phpstan-import-type RawProduct from ProductDenormalizer
 */
final class FetchProductQuery
{
    public function __construct(
        private AkeneoPimClientInterface $pimApiClient,
        private DenormalizerInterface $denormalizer,
    ) {
    }

    public function fetch(string $identifier, string $locale): Product
    {
        /** @var RawProduct $rawProduct */
        $rawProduct = $this->pimApiClient->getProductApi()->get($identifier);

        return $this->denormalizer->denormalize(
            $rawProduct,
            Product::class,
            null,
            [
                'locale' => $locale,
                'withProductValues' => true,
            ],
        );
    }
}
