<?php

declare(strict_types=1);

namespace App\Query;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Exception\NotFoundHttpException as AkeneoNotFoundHttpException;
use Akeneo\Pim\ApiClient\Search\Operator;
use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\Exception\CatalogDisabledException;
use App\Exception\CatalogProductNotFoundException;
use App\PimApi\Model\Product;
use App\PimApi\Model\ProductValue;
use App\PimApi\PimCatalogApiClient;
use App\PimApi\ProductValueDenormalizer;

/**
 * @phpstan-type RawProduct array{uuid: string, family: string|null, values: array<string, array{array{locale: string|null, scope: string|null, data: mixed}}>}
 * @phpstan-type RawFamily array{code: string, attribute_as_label: string}
 */
final class FetchProductQuery
{
    public function __construct(
        private readonly AkeneoPimClientInterface $pimApiClient,
        private readonly ProductValueDenormalizer $productValueDenormalizer,
        private readonly PimCatalogApiClient $catalogApiClient,
    ) {
    }

    public function fetch(string $catalogId, string $productUuid, string $locale): Product
    {
        try {
            /** @var RawProduct $rawProduct */
            $rawProduct = $this->catalogApiClient->getCatalogProduct($catalogId, $productUuid);
        } catch (\Exception $e) {
            throw new CatalogProductNotFoundException();
        }

        /* @phpstan-ignore-next-line */
        if (isset($rawProduct['message'])) {
            throw new CatalogDisabledException();
        }

        $scope = $this->findFirstAvailableScope($rawProduct);
        $familyAttributeAsLabel = $this->findAttributeAsLabel($rawProduct);
        $attributes = $this->fetchAttributes($rawProduct);

        $label = $this->findLabel($familyAttributeAsLabel, $rawProduct, $locale, $scope);

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

        return new Product($productUuid, $label, $values);
    }

    /**
     * @param RawProduct $rawProduct
     *
     * @return array<string, mixed>
     */
    private function fetchAttributes(array $rawProduct): array
    {
        $attributesCodes = \array_keys($rawProduct['values']);

        if (empty($attributesCodes)) {
            return [];
        }

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
     */
    private function findAttributeAsLabel(array $product): ?string
    {
        if (null === $product['family']) {
            return null;
        }

        try {
            /** @var RawFamily $rawFamily */
            $rawFamily = $this->pimApiClient->getFamilyApi()->get($product['family']);
        } catch (AkeneoNotFoundHttpException $e) {
            return null;
        }

        return $rawFamily['attribute_as_label'];
    }
}
