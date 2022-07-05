<?php

declare(strict_types=1);

namespace App\Query;

use App\Storage\AccessTokenStorageInterface;
use App\Storage\PimURLStorageInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FetchOrCreateDefaultDemoCatalogQuery
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly PimURLStorageInterface $pimURLStorage,
        private readonly AccessTokenStorageInterface $accessTokenStorage
    ) {
    }

    public function fetch(): string
    {
        $pimUrl = $this->getPimUrl();

        $catalogId = $this->fetchAnyExistingCatalogId($pimUrl);
        if (null === $catalogId) {
            $catalogId = $this->createAppCatalog($pimUrl);
        }

        return $catalogId;
    }

    private function getPimUrl(): string
    {
        $pimUrl = $this->pimURLStorage->getPimURL();
        if (null === $pimUrl) {
            throw new \LogicException('Can\'t retrieve PIM url, please restart the authorization process.');
        }

        return $pimUrl;
    }

    private function fetchAnyExistingCatalogId(string $pimUrl): ?string
    {
        $accessToken = $this->getAccessToken();
        $catalogEndpointUrl = $pimUrl.'/api/rest/v1/catalogs';
        $response = $this->client->request('GET', $catalogEndpointUrl, [
            'headers' => [
                'Content-type' => 'application/json',
                'Authorization' => 'Bearer '.$accessToken,
            ],
        ])->toArray();

        $catalogList = $response['_embedded']['items'] ?? [];

        return empty($catalogList) ? null : $catalogList[0]['id'];
    }

    private function createAppCatalog(string $pimUrl): string
    {
        $accessToken = $this->getAccessToken();
        $catalogEndpointUrl = $pimUrl.'/api/rest/v1/catalogs';
        $catalogPayload = [
            'name' => 'Demo App catalog',
        ];

        $response = $this->client->request('POST', $catalogEndpointUrl, [
            'headers' => [
                'Content-type' => 'application/json',
                'Authorization' => 'Bearer '.$accessToken,
            ],
            'json' => $catalogPayload,
        ])->toArray();

        return $response['id'];
    }

    private function getAccessToken(): string
    {
        $accessToken = $this->accessTokenStorage->getAccessToken();
        if (null === $accessToken) {
            throw new \LogicException('Can\'t retrieve access token, please restart the authorization process.');
        }

        return $accessToken;
    }
}
