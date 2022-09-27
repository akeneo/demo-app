<?php

declare(strict_types=1);

namespace App\Query;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Search\Operator;
use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\PimApi\Model\Product;
use App\PimApi\PimCatalogApiClient;
use App\PimApi\ProductValueDenormalizer;

/**
 * @phpstan-type RawProduct array{uuid: string, family: string|null, values: array<string, array{array{locale: string|null, scope: string|null, data: mixed}}>}
 * @phpstan-type RawFamily array{code: string, attribute_as_label: string}
 */
final class FetchProductsQuery
{
    public function __construct(
        private AkeneoPimClientInterface $pimApiClient,
        private ProductValueDenormalizer $productValueDenormalizer,
        private readonly PimCatalogApiClient $catalogApiClient,
    ) {
    }

    /**
     * @return array<Product>
     */
    public function fetch(string $locale, string $catalogId): array
    {
        /** @var array<RawProduct> $rawProducts */
        $rawProducts = $this->catalogApiClient->getCatalogProducts($catalogId, 10);

        if (0 === count($rawProducts)) {
            return [];
        }

        $scope = $this->findFirstAvailableScope($rawProducts[0]);

        $families = $this->fetchFamilies($rawProducts);

        $products = [];

        foreach ($rawProducts as $rawProduct) {
            $familyAttributeAsLabel = $this->findAttributeAsLabel($rawProduct, $families);

            $label = $this->findLabel($familyAttributeAsLabel, $rawProduct, $locale, $scope);

            $values = [];

            $products[] = new Product($rawProduct['uuid'], $label, $values);
        }

        return $products;
    }

    /**
     * @param array<RawProduct> $rawProducts
     *
     * @return array<string, RawFamily>
     */
    private function fetchFamilies(array $rawProducts): array
    {
        $familiesCodes = \array_filter(\array_unique(\array_map(
            fn (array $rawProduct) => $rawProduct['family'],
            $rawProducts,
        )));

        if (empty($familiesCodes)) {
            return [];
        }

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

    /**
     * @param RawProduct $product
     */
    private function findLabel(?string $attributeAsLabel, array $product, string $locale, ?string $scope): string
    {
        if (null === $attributeAsLabel || !isset($product['values'][$attributeAsLabel])) {
            return '['.$product['uuid'].']';
        }

        $label = $this->productValueDenormalizer->denormalize(
            $product['values'][$attributeAsLabel],
            $locale,
            $scope,
        );

        return (string) ($label ?? '['.$product['uuid'].']');
    }

    /**
     * @param RawProduct $product
     * @param array<string, RawFamily> $families
     */
    private function findAttributeAsLabel(array $product, array $families): ?string
    {
        if (null === $product['family']) {
            return null;
        }

        if (!isset($families[$product['family']])) {
            return null;
        }

        return $families[$product['family']]['attribute_as_label'];
    }
}
