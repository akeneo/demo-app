<?php

declare(strict_types=1);

namespace App\PimApi;

use Akeneo\Pim\ApiClient\AkeneoPimClientBuilder;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use App\Exception\MissingPimApiAccessTokenException;
use App\Storage\AccessTokenStorageInterface;
use App\Storage\PimURLStorageInterface;
use Psr\Http\Client\ClientInterface;

class PimApiClientFactory
{
    public function __construct(
        private AccessTokenStorageInterface $accessTokenStorage,
        private ClientInterface $httpClient,
        private PimURLStorageInterface $pimURLStorage,
    ) {
    }

    public function __invoke(): AkeneoPimClientInterface
    {
        $pimURL = $this->pimURLStorage->getPimURL();
        if (empty($pimURL)) {
            throw new \LogicException('Could not retrieve PIM URL, please restart the authorization process.');
        }

        $accessToken = $this->accessTokenStorage->getAccessToken();
        if (empty($accessToken)) {
            throw new MissingPimApiAccessTokenException();
        }

        $clientBuilder = new AkeneoPimClientBuilder($pimURL);
        $clientBuilder->setHttpClient($this->httpClient);

        return $clientBuilder->buildAuthenticatedByToken(
            '',
            '',
            $accessToken,
            ''
        );
    }
}
