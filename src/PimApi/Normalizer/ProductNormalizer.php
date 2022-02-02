<?php

declare(strict_types=1);

namespace App\PimApi\Normalizer;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use App\PimApi\Exception\NotSupportedAttributeTypeException;
use App\PimApi\Exception\NoValueAttributeException;
use App\PimApi\Model\Product;

class ProductNormalizer
{
    /** @var array<string, string> $familiesAttributesAsLabel */
    private array $familiesAttributesAsLabel = [];

    public function __construct(
        private AttributeNormalizer      $attributeBuilder,
        private AkeneoPimClientInterface $pimApiClient,
    ) {
    }

    public function denormalizeFromApi(
        array $rawPimApiProduct,
        string $locale,
        ?string $scope,
        bool $withAttributes = false,
    ): Product {
        return new Product(
            $rawPimApiProduct['identifier'],
            $this->getProductLabel($rawPimApiProduct, $locale),
            $withAttributes ? $this->buildAttributes($rawPimApiProduct, $locale, $scope) : [],
        );
    }

    /**
     * @param array<mixed> $product
     */
    private function getProductLabel(array $product, string $locale): string
    {
        $attributeAsLabel = $this->getFamilyAttributeAsLabel($product['family']);

        foreach ($product['values'][$attributeAsLabel] as $value) {
            if ($locale === $value['locale'] || null === $value['locale']) {
                return $value['data'];
            }
        }

        return \sprintf('[%s]', $product['identifier']);
    }

    private function getFamilyAttributeAsLabel(string $familyCode): string
    {
        return $this->familiesAttributesAsLabel[$familyCode] ??= $this->pimApiClient->getFamilyApi()->get($familyCode)['attribute_as_label'];
    }

    /**
     * @param array<mixed> $rawPimApiProduct
     *
     * @return array<string, mixed>
     */
    private function buildAttributes(array $rawPimApiProduct, string $locale, ?string $scope): array
    {
        $attributes = [];

        foreach ($rawPimApiProduct['values'] as $attributeCode => $values) {
            try {
                $attributes[] = $this->attributeBuilder->denormalizeFromApi($attributeCode, $values, $locale, $scope);
            } catch (NotSupportedAttributeTypeException | NoValueAttributeException $exception) {
                // do nothing
                continue;
            }
        }

        return $attributes;
    }
}
