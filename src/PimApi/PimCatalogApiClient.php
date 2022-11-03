<?php

declare(strict_types=1);

namespace App\PimApi;

use App\Exception\CatalogDisabledException;
use App\PimApi\Model\Catalog;
use App\Storage\AccessTokenStorageInterface;
use App\Storage\PimURLStorageInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PimCatalogApiClient
{
    public function __construct(
        private HttpClientInterface $client,
        private readonly PimURLStorageInterface $pimURLStorage,
        AccessTokenStorageInterface $accessTokenStorage,
    ) {
        $accessToken = $accessTokenStorage->getAccessToken();
        if (null === $accessToken) {
            throw new \LogicException('Can\'t retrieve access token, please restart the authorization process.');
        }

        $this->client = $this->client->withOptions([
            'headers' => [
                'Content-type' => 'application/json',
                'Authorization' => 'Bearer '.$accessToken,
            ],
        ]);
    }

    public function getCatalog(string $catalogId): Catalog
    {
        $pimUrl = $this->getPimUrl();

        $catalogEndpointUrl = "$pimUrl/api/rest/v1/catalogs/$catalogId";

        $response = $this->client->request('GET', $catalogEndpointUrl)->toArray();

        return new Catalog(
            $response['id'],
            $response['name'],
            $response['enabled'],
        );
    }

    /**
     * @return array<string>
     */
    public function getProductIdentifiers(string $catalogId, int $limit = 100): array
    {
        $pimUrl = $this->getPimUrl();

        $catalogIdentifierEndpointUrl = "$pimUrl/api/rest/v1/catalogs/$catalogId/product-identifiers";

        $response = $this->client->request('GET', $catalogIdentifierEndpointUrl, [
            'query' => ['limit' => $limit],
        ])->toArray();

        return $response['_embedded']['items'];
    }

    private function getPimUrl(): string
    {
        $pimUrl = $this->pimURLStorage->getPimURL();
        if (null === $pimUrl) {
            throw new \LogicException('Can\'t retrieve PIM url, please restart the authorization process.');
        }

        return $pimUrl;
    }

    /**
     * @return array<mixed>
     */
    public function getCatalogProducts(string $catalogId, int $limit = 10, ?string $searchAfter = null, ?string $updatedAfter = null, ?string $updatedBefore = null): array
    {
        $pimUrl = $this->getPimUrl();

        $catalogEndpointUrl = "$pimUrl/api/rest/v1/catalogs/$catalogId/products";

        $response = $this->client->request('GET', $catalogEndpointUrl, [
            'query' => [
                'search_after' => $searchAfter,
                'limit' => $limit,
                'updated_after' => $updatedAfter,
                'updated_before' => $updatedBefore,
            ],
        ])->toArray();

        if (isset($response['message']) || isset($response['error'])) {
            throw new CatalogDisabledException();
        }

        return $response['_embedded']['items'];
    }

    /**
     * @return array<mixed>
     */
    public function getCatalogProduct(string $catalogId, string $productUuid): array
    {
        $pimUrl = $this->getPimUrl();

        $catalogEndpointUrl = "$pimUrl/api/rest/v1/catalogs/$catalogId/products/$productUuid";

        $response = $this->client->request('GET', $catalogEndpointUrl)->toArray();

        if (isset($response['message']) || isset($response['error'])) {
            throw new CatalogDisabledException();
        }

        return $response;
    }
}
