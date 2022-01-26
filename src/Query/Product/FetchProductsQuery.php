<?php

declare(strict_types=1);

namespace App\Query\Product;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\Dto\Product\Product;

final class FetchProductsQuery
{
    public function __construct(
        private AkeneoPimClientInterface $pimApiClient,
    ) {
    }

    /**
     * @return Product[]
     */
    public function __invoke(string $locale, int $limit = 10): array
    {
        $searchBuilder = new SearchBuilder();
        $searchBuilder->addFilter('enabled', '=', true);
        $searchFilters = $searchBuilder->getFilters();

        $firstPage = $this->pimApiClient->getProductApi()->listPerPage(100, true, ['search' => $searchFilters]);

        $products = [];

        foreach ($firstPage->getItems() as $item) {
            if ($limit <= \count($products)) {
                break;
            }

            $product = new Product(
                $item['identifier'],
                $this->getProductLabel($item, $locale),
            );

            $products[] = $product;
        }

        return $products;
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
        static $familiesAttributesAsLabel = [];

        if (!\array_key_exists($familyCode, $familiesAttributesAsLabel)) {
            $family = $this->pimApiClient->getFamilyApi()->get($familyCode);
            $familiesAttributesAsLabel[$familyCode] = $family['attribute_as_label'];
        }

        return $familiesAttributesAsLabel[$familyCode];
    }
}
