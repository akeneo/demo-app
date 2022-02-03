<?php

declare(strict_types=1);

namespace App\Query;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Search\Operator;
use Akeneo\Pim\ApiClient\Search\SearchBuilder;

/**
 * @phpstan-type RawProduct array{identifier: string, family: string, values: array<string, array{array{locale: string|null, scope: string|null, data: mixed}}>}
 */
class AbstractProductQuery
{
    protected const SUPPORTED_ATTRIBUTE_TYPES = [
        'pim_catalog_identifier',
        'pim_catalog_text',
        'pim_catalog_textarea',
        'pim_catalog_number',
        'pim_catalog_boolean',
//        'pim_catalog_date',
//        'pim_catalog_currency',
//        'pim_catalog_price_collection',
//        'pim_catalog_simple_select',
    ];

    public function __construct(
        protected AkeneoPimClientInterface $pimApiClient,
    ) {
    }

    /**
     * @param array<RawProduct> $rawProducts
     *
     * @return array<string, mixed>
     */
    protected function fetchAttributes(array $rawProducts): array
    {
        $attributesCodes = \array_keys(\array_merge(...\array_column($rawProducts, 'values')));

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
    protected function findAttributeValue(
        array $product,
        string $attributeIdentifier,
        string $locale,
        ?string $scope
    ): string|bool|null {
        foreach ($product['values'][$attributeIdentifier] ?? [] as $value) {
            if (null !== $value['locale'] && $value['locale'] !== $locale) {
                continue;
            }

            if (null !== $value['scope'] && $value['scope'] !== $scope) {
                continue;
            }

            return $value['data'];
        }

        return null;
    }

    /**
     * @param RawProduct $product
     */
    protected function findFirstAvailableScope(array $product): ?string
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
}
