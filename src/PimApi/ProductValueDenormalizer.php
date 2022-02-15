<?php

declare(strict_types=1);

namespace App\PimApi;

class ProductValueDenormalizer
{
    private const PIM_CATALOG_IDENTIFIER = 'pim_catalog_identifier';
    private const PIM_CATALOG_TEXT = 'pim_catalog_text';
    private const PIM_CATALOG_TEXTAREA = 'pim_catalog_textarea';
    private const PIM_CATALOG_NUMBER = 'pim_catalog_number';
    private const PIM_CATALOG_BOOLEAN = 'pim_catalog_boolean';
    private const PIM_CATALOG_DATE = 'pim_catalog_date';
    private const PIM_CATALOG_PRICE_COLLECTION = 'pim_catalog_price_collection';

    private const SUPPORTED_ATTRIBUTE_TYPES = [
        self::PIM_CATALOG_IDENTIFIER,
        self::PIM_CATALOG_TEXT,
        self::PIM_CATALOG_TEXTAREA,
        self::PIM_CATALOG_NUMBER,
        self::PIM_CATALOG_BOOLEAN,
        self::PIM_CATALOG_DATE,
        self::PIM_CATALOG_PRICE_COLLECTION,
    ];

    /**
     * @param array{array{locale: string|null, scope: string|null, data: mixed}} $rawValues
     */
    public function denormalize(array $rawValues, string $locale, ?string $scope, ?string $type = null): string|bool|int|float|null
    {
        foreach ($rawValues as $value) {
            if (null !== $value['locale'] && $value['locale'] !== $locale) {
                continue;
            }

            if (null !== $value['scope'] && $value['scope'] !== $scope) {
                continue;
            }

            switch ($type) {
                case self::PIM_CATALOG_IDENTIFIER:
                case self::PIM_CATALOG_NUMBER:
                case self::PIM_CATALOG_TEXTAREA:
                case self::PIM_CATALOG_TEXT:
                    return (string) $value['data'];
                case self::PIM_CATALOG_BOOLEAN:
                    return (bool) $value['data'];
                case self::PIM_CATALOG_DATE:
                    return (new \DateTime($value['data']))->format('m/d/Y');
                case self::PIM_CATALOG_PRICE_COLLECTION:
                    $prices = \array_map(
                        fn (array $data) => \sprintf('%s %s', $data['amount'], $data['currency']),
                        $value['data'],
                    );

                    return \implode('; ', $prices);
                default:
                    return is_string($value['data']) ? $value['data'] : null;
            }
        }

        return null;
    }

    public function isSupported(string $type): bool
    {
        return in_array($type, self::SUPPORTED_ATTRIBUTE_TYPES);
    }
}
