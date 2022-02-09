<?php

declare(strict_types=1);

namespace App\PimApi\Normalizer;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use App\PimApi\Model\ProductValue;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;

abstract class AbstractProductValueDenormalizer implements ContextAwareDenormalizerInterface
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

    public function __construct(
        protected AkeneoPimClientInterface $pimApiClient,
    ) {
    }

    /**
     * @param array<mixed> $context
     *
     * @return ProductValue|null
     */
    public function denormalize(mixed $data, string $type, string $format = null, array $context = [])
    {
        $scope = $context['scope'] ?? null;
        $locale = $context['locale'] ?? null;

        $attribute = $context['attribute'];

        $attributeValue = $this->findAttributeValue($data, $locale, $scope, $attribute['code']);

        if (null === $attributeValue) {
            return null;
        }

        return new ProductValue(
            $attribute['labels'][$locale] ?? \sprintf('[%s]', $attribute['code']),
            $attribute['type'],
            $attributeValue,
        );
    }

    /**
     * @param array<mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return ProductValue::class === $type
            && \is_array($data)
            && \array_key_exists('attributeCode', $context)
            && \array_key_exists('attribute', $context)
            && $this->getAttributeType() === $context['attribute']['type'];
    }

    /**
     * @param array<mixed> $values
     */
    abstract protected function findAttributeValue(
        array $values,
        ?string $locale,
        ?string $scope,
        string $attributeCode,
    ): string|bool|int|float|null;

    abstract protected function getAttributeType(): string;
}
