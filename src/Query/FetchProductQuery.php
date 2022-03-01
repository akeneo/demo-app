<?php

declare(strict_types=1);

namespace App\Query;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Search\Operator;
use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\PimApi\Model\Product;
use App\PimApi\Model\ProductValue;
use App\PimApi\ProductValueDenormalizer;

/**
 * @phpstan-type RawProduct array{identifier: string, family: string, values: array<string, array{array{locale: string|null, scope: string|null, data: mixed}}>}
 */
final class FetchProductQuery
{
    public function __construct(
        private AkeneoPimClientInterface $pimApiClient,
        private ProductValueDenormalizer $productValueDenormalizer,
    ) {
    }

    public function fetch(string $identifier, string $locale): Product
    {
        /** @var RawProduct $rawProduct */
        $rawProduct = $this->pimApiClient->getProductApi()->get($identifier);
        $scope = $this->findFirstAvailableScope($rawProduct);

        $familyIdentifier = $rawProduct['family'];
        $rawFamily = $this->pimApiClient->getFamilyApi()->get($familyIdentifier);

        $attributes = $this->fetchAttributes($rawProduct);

        $label = $this->findLabel($rawFamily['attribute_as_label'], $rawProduct, $locale, $scope);

        $values = [];

        foreach ($rawProduct['values'] as $attributeIdentifier => $value) {
            $attribute = $attributes[$attributeIdentifier];

            if (!$this->productValueDenormalizer->isSupported($attribute['type'])) {
                continue;
            }

            $attributeValue = $this->productValueDenormalizer->denormalize(
                $value,
                $locale,
                $scope,
                $attributes[$attributeIdentifier]['type'],
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
     * @param RawProduct $rawProduct
     *
     * @return array<string, mixed>
     */
    private function fetchAttributes(array $rawProduct): array
    {
        $attributesCodes = \array_keys($rawProduct['values']);

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

    /**
     * @param RawProduct $product
     */
    private function findLabel(string $attributeAsLabel, array $product, string $locale, ?string $scope): string
    {
        if (!isset($product['values'][$attributeAsLabel])) {
            return '['.$product['identifier'].']';
        }

        $label = $this->productValueDenormalizer->denormalize(
            $product['values'][$attributeAsLabel],
            $locale,
            $scope,
        );

        return (string) ($label ?? '['.$product['identifier'].']');
    }
}
