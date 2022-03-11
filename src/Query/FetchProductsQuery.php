<?php

declare(strict_types=1);

namespace App\Query;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Exception\UnprocessableEntityHttpException;
use Akeneo\Pim\ApiClient\Search\Operator;
use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\PimApi\Model\Product;
use App\PimApi\ProductValueDenormalizer;
use Psr\Log\LoggerInterface;

/**
 * @phpstan-type RawProduct array{identifier: string, family: string|null, values: array<string, array{array{locale: string|null, scope: string|null, data: mixed}}>}
 * @phpstan-type RawFamily array{code: string, attribute_as_label: string}
 */
final class FetchProductsQuery
{
    public function __construct(
        private AkeneoPimClientInterface $pimApiClient,
        private ProductValueDenormalizer $productValueDenormalizer,
        private LoggerInterface $logger,
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

        try {
            /** @var array<RawProduct> $rawProducts */
            $rawProducts = $this->pimApiClient->getProductApi()->listPerPage(
                10,
                false,
                [
                    'search' => $searchFilters,
                    'locales' => $locale,
                ]
            )->getItems();
        } catch (UnprocessableEntityHttpException $exception) {
            // The UnprocessableEntity error can be triggered when searching a locale we don't have access to
            // We log, and try again without any locale.

            $this->logger->error($exception->getMessage());

            /** @var array<RawProduct> $rawProducts */
            $rawProducts = $this->pimApiClient->getProductApi()->listPerPage(
                10,
                false,
                [
                    'search' => $searchFilters,
                ]
            )->getItems();
        }

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

            $products[] = new Product($rawProduct['identifier'], $label, $values);
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
            return '['.$product['identifier'].']';
        }

        $label = $this->productValueDenormalizer->denormalize(
            $product['values'][$attributeAsLabel],
            $locale,
            $scope,
        );

        return (string) ($label ?? '['.$product['identifier'].']');
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
