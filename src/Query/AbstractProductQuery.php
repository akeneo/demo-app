<?php

declare(strict_types=1);

namespace App\Query;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;

/**
 * @phpstan-type RawProduct array{identifier: string, family: string, values: array<string, array{array{locale: string|null, scope: string|null, data: mixed}}>}
 */
class AbstractProductQuery
{
    public const PIM_CATALOG_IDENTIFIER = 'pim_catalog_identifier';
    public const PIM_CATALOG_TEXT = 'pim_catalog_text';
    public const PIM_CATALOG_TEXTAREA = 'pim_catalog_textarea';
    public const PIM_CATALOG_NUMBER = 'pim_catalog_number';
    public const PIM_CATALOG_BOOLEAN = 'pim_catalog_boolean';
    public const PIM_CATALOG_DATE = 'pim_catalog_date';
    public const PIM_CATALOG_PRICE_COLLECTION = 'pim_catalog_price_collection';
    public const PIM_CATALOG_SIMPLE_SELECT = 'pim_catalog_simpleselect';

    public const SUPPORTED_ATTRIBUTE_TYPES = [
        self::PIM_CATALOG_IDENTIFIER,
        self::PIM_CATALOG_TEXT,
        self::PIM_CATALOG_TEXTAREA,
        self::PIM_CATALOG_NUMBER,
        self::PIM_CATALOG_BOOLEAN,
        self::PIM_CATALOG_DATE,
        self::PIM_CATALOG_PRICE_COLLECTION,
        self::PIM_CATALOG_SIMPLE_SELECT,
    ];

    /** @var array<string, mixed> */
    private array $attributesOptions = [];

    public function __construct(
        protected AkeneoPimClientInterface $pimApiClient,
    ) {
    }

    /**
     * @param RawProduct $product
     */
    protected function findAttributeValue(
        array $product,
        string $attributeIdentifier,
        string $attributeType,
        string $locale,
        ?string $scope
    ): string|bool|int|float|null {
        foreach ($product['values'][$attributeIdentifier] ?? [] as $value) {
            if (null !== $value['locale'] && $value['locale'] !== $locale) {
                continue;
            }

            if (null !== $value['scope'] && $value['scope'] !== $scope) {
                continue;
            }

            switch ($attributeType) {
                case self::PIM_CATALOG_IDENTIFIER:
                case self::PIM_CATALOG_NUMBER:
                case self::PIM_CATALOG_TEXTAREA:
                case self::PIM_CATALOG_TEXT:
                    return (string) $value['data'];
                case self::PIM_CATALOG_BOOLEAN:
                    return (bool) $value['data'];
                case self::PIM_CATALOG_DATE:
                    return $this->normalizeDate($value);
                case self::PIM_CATALOG_PRICE_COLLECTION:
                    return $this->normalizePriceValue($value);
                case self::PIM_CATALOG_SIMPLE_SELECT:
                    return $this->normalizeSimpleSelectValue($value, $attributeIdentifier, $locale);
            }
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

    /**
     * @param array<string, mixed> $value
     */
    private function normalizeDate(array $value): string
    {
        return (new \DateTime($value['data']))->format('m/d/Y');
    }

    /**
     * @param array<string, mixed> $value
     */
    private function normalizePriceValue(array $value): string
    {
        $prices = \array_map(
            fn (array $data) => \sprintf('%s %s', $data['amount'], $data['currency']),
            $value['data'],
        );

        return \implode('; ', $prices);
    }

    /**
     * @param array<string, mixed> $value
     */
    private function normalizeSimpleSelectValue(array $value, string $attributeCode, string $locale): string
    {
        $options = $this->getAttributeOptions($attributeCode);

        foreach ($options as $option) {
            if ($value['data'] === $option['code']) {
                foreach ($option['labels'] as $labelLocale => $label) {
                    if ($locale === $labelLocale) {
                        return $label;
                    }
                }
            }
        }

        return \sprintf('[%s]', $value['data']);
    }

    /**
     * @return array<string, mixed>
     */
    private function getAttributeOptions(string $attributeCode): array
    {
        return $this->attributesOptions[$attributeCode] ??= $this->pimApiClient->getAttributeOptionApi()->listPerPage($attributeCode, 100)->getItems();
    }
}
