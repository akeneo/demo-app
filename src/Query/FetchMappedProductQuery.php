<?php

declare(strict_types=1);

namespace App\Query;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Exception\NotFoundHttpException as AkeneoNotFoundHttpException;
use Akeneo\Pim\ApiClient\Search\Operator;
use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\Exception\CatalogDisabledException;
use App\Exception\CatalogProductNotFoundException;
use App\PimApi\Model\Product;
use App\PimApi\Model\ProductValue;
use App\PimApi\PimCatalogApiClient;
use App\PimApi\ProductValueDenormalizer;

/**
 * @phpstan-type RawMappedProduct array{
 *      uuid: string,
 *      title: string,
 *      description: string,
 *      code: string,
 * }
 */
final class FetchMappedProductQuery
{
    public function __construct(
        private readonly PimCatalogApiClient $catalogApiClient,
    ) {
    }

    public function fetch(string $catalogId, string $productUuid): Product
    {
        try {
            /** @var RawMappedProduct $rawMappedProduct */
            $rawMappedProduct = $this->catalogApiClient->getCatalogMappedProduct($catalogId, $productUuid);
        } catch (CatalogDisabledException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new CatalogProductNotFoundException();
        }

        $label = !empty($rawMappedProduct['title']) ? $rawMappedProduct['title'] : $rawMappedProduct['uuid'];

        $values = [
            new ProductValue('mapped_properties.title', 'string', $rawMappedProduct['title']),
            new ProductValue('mapped_properties.uuid', 'string', $rawMappedProduct['uuid']),
            new ProductValue('mapped_properties.description', 'string', $rawMappedProduct['description']),
            new ProductValue('mapped_properties.code', 'string', $rawMappedProduct['code']),
        ];

        return new Product($productUuid, $label, $values);
    }
}
