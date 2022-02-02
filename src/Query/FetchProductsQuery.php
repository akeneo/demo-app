<?php

declare(strict_types=1);

namespace App\Query;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\PimApi\Model\Product;
use App\PimApi\Model\ProductValue;

/**
 * @phpstan-import-type RawProduct from AbstractProductQuery
 */
final class FetchProductsQuery extends AbstractProductQuery
{
    public function __construct(
        private AkeneoPimClientInterface $pimApiClient,
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

        /** @var RawProduct[] $rawProducts */
        $rawProducts = $this->pimApiClient->getProductApi()->listPerPage(
            10,
            false,
            [
                'search' => $searchFilters,
                'locales' => $locale,
            ]
        )->getItems();

        $scope = $this->findFirstAvailableScope($rawProducts[0]);

        $products = [];
        $families = [];
        $attributes = [];

        foreach ($rawProducts as $rawProduct) {
            $familyIdentifier = $rawProduct['family'];
            $family = $families[$familyIdentifier] ??= $this->pimApiClient->getFamilyApi()->get($familyIdentifier);

            $label = (string) $this->findAttributeValue($rawProduct, $family['attribute_as_label'], $locale, $scope);

            $values = [];

            foreach ($rawProduct['values'] as $attributeIdentifier => $value) {
                $attribute = $attributes[$attributeIdentifier]
                    ??= $this->pimApiClient->getAttributeApi()->get($attributeIdentifier);

                if (!in_array($attribute['type'], self::SUPPORTED_ATTRIBUTE_TYPES)) {
                    continue;
                }

                $attributeValue = $this->findAttributeValue($rawProduct, $attributeIdentifier, $locale, $scope);

                if (null === $attributeValue) {
                    continue;
                }

                $values[] = new ProductValue(
                    $attribute['labels'][$locale] ?? sprintf('[%s]', $attribute['code']),
                    $attribute['type'],
                    $attributeValue,
                );
            }

            $products[] = new Product($rawProduct['identifier'], $label, $values);
        }

        return $products;
    }
}
