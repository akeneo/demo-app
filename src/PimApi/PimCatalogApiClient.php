<?php

declare(strict_types=1);

namespace App\PimApi;

use App\Exception\CatalogDisabledException;
use App\PimApi\Exception\PimApiException;
use App\PimApi\Model\Catalog;
use App\Storage\AccessTokenStorageInterface;
use App\Storage\PimURLStorageInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PimCatalogApiClient
{
    public function __construct(
        private HttpClientInterface $client,
        private readonly PimURLStorageInterface $pimURLStorage,
        private readonly AccessTokenStorageInterface $accessTokenStorage,
    ) {
    }

    private function getClient(): HttpClientInterface
    {
        $accessToken = $this->accessTokenStorage->getAccessToken();
        if (null === $accessToken) {
            throw new \LogicException('Can\'t retrieve access token, please restart the authorization process.');
        }

        $this->client = $this->client->withOptions([
            'headers' => [
                'Content-type' => 'application/json',
                'Authorization' => 'Bearer '.$accessToken,
            ],
        ]);

        return $this->client;
    }

    public function getCatalog(string $catalogId): Catalog
    {
        $pimUrl = $this->getPimUrl();

        $catalogEndpointUrl = "$pimUrl/api/rest/v1/catalogs/$catalogId";
        $response = $this->getClient()->request('GET', $catalogEndpointUrl);

        if (200 !== $response->getStatusCode()) {
            throw new PimApiException($response->getStatusCode().': Couldn\'t get catalog');
        }

        $response = $response->toArray();

        return new Catalog(
            $response['id'],
            $response['name'],
            $response['enabled'],
        );
    }

    /**
     * @return array<Catalog>
     */
    public function getCatalogs(): array
    {
        $pimUrl = $this->getPimUrl();

        $catalogEndpointUrl = "$pimUrl/api/rest/v1/catalogs";
        $response = $this->getClient()->request('GET', $catalogEndpointUrl);

        if (200 !== $response->getStatusCode()) {
            throw new PimApiException($response->getStatusCode().': Couldn\'t get catalogs');
        }

        $response = $response->toArray();

        $catalogList = [];
        foreach ($response['_embedded']['items'] as $catalogItem) {
            $catalogList[] = new Catalog(
                $catalogItem['id'],
                $catalogItem['name'],
                $catalogItem['enabled'],
            );
        }

        return $catalogList;
    }

    public function createCatalog(string $name): Catalog
    {
        $pimUrl = $this->getPimUrl();
        $response = $this->getClient()->request('POST', "$pimUrl/api/rest/v1/catalogs", [
            'json' => [
                'name' => $name,
            ],
        ]);

        if (201 !== $response->getStatusCode()) {
            throw new PimApiException($response->getStatusCode().': Couldn\'t create catalog');
        }

        $response = $response->toArray();

        return new Catalog(
            $response['id'],
            $response['name'],
            $response['enabled'],
        );
    }

    public function setProductMappingSchema(string $catalogId, string $productMappingSchema): void
    {
        $pimUrl = $this->getPimUrl();

        $catalogEndpointUrl = "$pimUrl/api/rest/v1/catalogs/$catalogId/mapping-schemas/product";
        $response = $this->getClient()->request('PUT', $catalogEndpointUrl, [
            'body' => $productMappingSchema,
        ]);

        if (204 !== $response->getStatusCode()) {
            throw new PimApiException($response->getStatusCode().': Couldn\'t update product mapping schema');
        }
    }

    /**
     * @return array<string>
     */
    public function getProductIdentifiers(string $catalogId, int $limit = 100): array
    {
        $pimUrl = $this->getPimUrl();

        $catalogIdentifierEndpointUrl = "$pimUrl/api/rest/v1/catalogs/$catalogId/product-identifiers";

        $response = $this->getClient()->request('GET', $catalogIdentifierEndpointUrl, [
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

        $response = $this->getClient()->request('GET', $catalogEndpointUrl, [
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

        $response = $this->getClient()->request('GET', $catalogEndpointUrl)->toArray();

        if (isset($response['message']) || isset($response['error'])) {
            throw new CatalogDisabledException();
        }

        return $response;
    }
}
