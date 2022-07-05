<?php

declare(strict_types=1);

namespace App\PimApi;

use App\Storage\CatalogIdStorageInterface;
use App\Storage\PimURLStorageInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FetchOrCreateAppCatalogHandler
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly PimURLStorageInterface $pimURLStorage,
        private readonly CatalogIdStorageInterface $catalogIdStorage,
    ) {
    }

    public function execute(string $accessToken): void
    {
        $pimUrl = $this->getPimUrl();

        $catalogId = $this->catalogIdStorage->getCatalogId();
        if (null !== $catalogId) {
            return;
        }

        $catalogId = $this->fetchAnyExistingCatalogId($pimUrl, $accessToken);
        if (null === $catalogId) {
            $catalogId = $this->createAppCatalog($pimUrl, $accessToken);
        }

        $this->catalogIdStorage->setCatalogId($catalogId);
    }

    private function getPimUrl(): string
    {
        $pimUrl = $this->pimURLStorage->getPimURL();
        if (null === $pimUrl) {
            throw new \LogicException('Can\'t retrieve PIM url, please restart the authorization process.');
        }

        return $pimUrl;
    }

    private function fetchAnyExistingCatalogId(string $pimUrl, string $accessToken): ?string
    {
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

    private function createAppCatalog(string $pimUrl, string $accessToken): string
    {
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
}
