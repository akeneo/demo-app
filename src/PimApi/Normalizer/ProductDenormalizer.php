<?php

declare(strict_types=1);

namespace App\PimApi\Normalizer;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use App\PimApi\Model\Product;
use App\PimApi\Model\ProductValue;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @phpstan-type RawProduct array{identifier: string, family: string, values: array<string, array{array{locale: string|null, scope: string|null, data: mixed}}>}
 */
class ProductDenormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    /** @var array<string, mixed> */
    private array $families = [];

    private ?DenormalizerInterface $denormalizer = null;

    public function __construct(
        private AkeneoPimClientInterface $pimApiClient,
    ) {
    }

    public function setDenormalizer(DenormalizerInterface $denormalizer): void
    {
        $this->denormalizer = $denormalizer;
    }

    /**
     * @param array<mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return Product::class === $type && \is_array($data);
    }

    /**
     * @param array<mixed> $context
     *
     * @return Product
     */
    public function denormalize(mixed $data, string $type, string $format = null, array $context = [])
    {
        $locale = $context['locale'] ?? null;
        $withProductValues = $context['withProductValues'] ?? false;
        $scope = $this->findFirstAvailableScope($data);

        $familyIdentifier = $data['family'];
        $rawFamily = $this->getFamily($familyIdentifier);

        $label = (string) $this->findAttributeValue($data, $rawFamily['attribute_as_label'], $locale, $scope);

        $productValues = [];

        if (true === $withProductValues && null != $this->denormalizer) {
            foreach ($data['values'] as $attributeCode => $values) {
                $productValue = $this->denormalizer->denormalize($values, ProductValue::class, null, [
                    'attributeCode' => $attributeCode,
                    'locale' => $locale,
                    'scope' => $scope,
                ]);

                if (null === $productValue) {
                    continue;
                }

                $productValues[] = $productValue;
            }
        }

        return new Product($data['identifier'], $label, $productValues);
    }

    /**
     * @return array<mixed>
     */
    private function getFamily(string $familyCode): array
    {
        return $this->families[$familyCode] ??= $this->pimApiClient->getFamilyApi()->get($familyCode);
    }

    /**
     * @param RawProduct $product
     */
    private function findAttributeValue(
        array $product,
        string $attributeIdentifier,
        ?string $locale,
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
}
