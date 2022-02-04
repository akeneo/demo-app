<?php

declare(strict_types=1);

namespace App\PimApi\Normalizer;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use App\PimApi\Model\ProductValue;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;

class ProductValueDenormalizer implements ContextAwareDenormalizerInterface
{
    private const SUPPORTED_ATTRIBUTE_TYPES = [
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

    /** @var array<string, mixed> */
    private array $attributes = [];

    public function __construct(
        protected AkeneoPimClientInterface $pimApiClient,
    ) {
    }

    /**
     * @param array<mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return ProductValue::class === $type
            && \is_array($data)
            && \array_key_exists('attributeCode', $context);
    }

    /**
     * @param array<mixed> $context
     *
     * @return ProductValue|null
     */
    public function denormalize(mixed $data, string $type, string $format = null, array $context = [])
    {
        $attributeCode = $context['attributeCode'];
        $scope = $context['scope'] ?? null;
        $locale = $context['locale'] ?? null;

        $attribute = $this->getAttribute($attributeCode);

        if (!\in_array($attribute['type'], self::SUPPORTED_ATTRIBUTE_TYPES)) {
            return null;
        }

        $attributeValue = $this->findAttributeValue($data, $locale, $scope);

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
     * @return array<mixed>
     */
    private function getAttribute(string $attributeCode): array
    {
        return $this->attributes[$attributeCode] ??= $this->pimApiClient->getAttributeApi()->get($attributeCode);
    }

    /**
     * @param array<mixed> $values
     */
    private function findAttributeValue(
        array $values,
        ?string $locale,
        ?string $scope
    ): string|bool|null {
        foreach ($values as $value) {
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
}
