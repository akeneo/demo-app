<?php

declare(strict_types=1);

namespace App\PimApi;

use Akeneo\Pim\ApiClient\AkeneoPimClientBuilder;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use App\Storage\AccessTokenStorageInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PimApiClientFactory
{
    public function __construct(
        private RequestStack $requestStack,
        private AccessTokenStorageInterface $accessTokenStorage,
        private string $akeneoClientId,
        private string $akeneoClientSecret,
    ) {
    }

    public function __invoke(): AkeneoPimClientInterface
    {
        $pimUrl = $this->requestStack->getSession()->get('pim_url');
        if (empty($pimUrl)) {
            throw new \LogicException('Could not retrieve PIM url, please restart the authorization process.');
        }

        $accessToken = $this->accessTokenStorage->getAccessToken();
        if (null === $accessToken) {
            throw new AccessDeniedHttpException('Missing Pim API access token.');
        }

        $clientBuilder = new AkeneoPimClientBuilder($pimUrl);

        return $clientBuilder->buildAuthenticatedByToken($this->akeneoClientId, $this->akeneoClientSecret, $accessToken, '');
    }
}
