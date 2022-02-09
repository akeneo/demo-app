<?php

declare(strict_types=1);

namespace App\PimApi\Normalizer;

use App\PimApi\Model\ProductValue;

class ProductValueIdentifierDenormalizer extends AbstractProductValueDenormalizer
{
    /**
     * @param array<mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return ProductValue::class === $type
            && \is_array($data)
            && \array_key_exists('attributeCode', $context)
            && \array_key_exists('attribute', $context);
//            && 'pim_catalog_identifier' === $context['attribute']['type'];
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
     * @param array<mixed> $values
     */
    private function findAttributeValue(
        array $values,
        ?string $locale,
        ?string $scope
    ): string|null {
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
