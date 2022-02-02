<?php

declare(strict_types=1);

namespace App\PimApi\Normalizer;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use App\PimApi\Model\Attribute;
use App\PimApi\Exception\NotSupportedAttributeTypeException;
use App\PimApi\Exception\NoValueAttributeException;

class AttributeNormalizer
{
    /** @var array<string, mixed> $attributes */
    private array $attributes = [];

    /** @var array<string, mixed> $attributesOptions */
    private array $attributesOptions = [];

    public function __construct(
        private AkeneoPimClientInterface $pimApiClient,
    ) {
    }

    public function denormalizeFromApi(
        string $attributeCode,
        array $rawPimApiAttributeValues,
        string $locale,
        ?string $scope,
    ): Attribute {
        $attributeType = $this->getAttributeType($attributeCode);

        return new Attribute(
            $this->normalizeAttributeLabel($attributeCode, $locale),
            $attributeType,
            $this->normalizeAttributeValue($attributeCode, $attributeType, $rawPimApiAttributeValues, $locale, $scope),
        );
    }

    private function normalizeAttributeLabel(string $attributeCode, string $locale): string
    {
        $attribute = $this->getRawPimApiAttribute($attributeCode);

        foreach ($attribute['labels'] as $labelLocale => $label) {
            if ($locale === $labelLocale) {
                return $label;
            }
        }

        return \sprintf('[%s]', $attributeCode);
    }

    /**
     * @param array<mixed> $values
     */
    private function normalizeAttributeValue(
        string $attributeCode,
        string $attributeType,
        array $values,
        string $locale,
        ?string $scope,
    ): string|bool {
        $bestValue = null;
        foreach ($values as $value) {
            if ($locale === $value['locale'] && $scope === $value['scope']) {
                $bestValue = $value;
                break;
            }

            if (($locale === $value['locale'] && null === $value['scope'])
                || (null === $value['locale'] && $scope === $value['scope'])
                || (null === $value['locale'] && null === $value['scope'])
            ) {
                $bestValue = $value;
            }
        }

        if (null === $bestValue) {
            throw new NoValueAttributeException($attributeCode);
        }

        switch ($attributeType) {
            case 'pim_catalog_identifier':
            case 'pim_catalog_number':
            case 'pim_catalog_date':
            case 'pim_catalog_textarea':
            case 'pim_catalog_text':
                return (string) $value['data'];
            case 'pim_catalog_boolean':
                return (bool) $value['data'];
            case 'pim_catalog_metric':
                return $this->normalizeMetricValue($value);
            case 'pim_catalog_price_collection':
                return $this->normalizePriceValue($value);
            case 'pim_catalog_simpleselect':
                return $this->normalizeSimpleSelectValue($value, $attributeCode, $locale);
            default:
                throw new NotSupportedAttributeTypeException($attributeType);
        }
    }

    private function getAttributeType(string $attributeCode): string
    {
        return $this->getRawPimApiAttribute($attributeCode)['type'];
    }

    /**
     * @return array<mixed>
     */
    private function getRawPimApiAttribute(string $attributeCode): array
    {
        return $this->attributes[$attributeCode] ??= $this->pimApiClient->getAttributeApi()->get($attributeCode);
    }

    /**
     * @return array<mixed>
     */
    private function getAttributeOptions(string $attributeCode): array
    {
        return $this->attributesOptions[$attributeCode] ??= $this->pimApiClient->getAttributeOptionApi()->listPerPage($attributeCode, 100)->getItems();
    }

    /**
     * @param array<string, mixed> $value
     */
    private function normalizeMetricValue(array $value): string
    {
        return \sprintf('%s %s', (string) $value['data']['amount'], (string) $value['data']['unit']);
    }

    /**
     * @param array<string, mixed> $value
     */
    private function normalizePriceValue(array $value): string
    {
        $prices = \array_map(
            fn ($data) => \sprintf('%s %s', $data['amount'], $data['currency']),
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
}
