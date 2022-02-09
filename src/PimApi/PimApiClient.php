<?php

namespace App\PimApi;

use Akeneo\Pim\ApiClient\AkeneoPimClientBuilder;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\AssociationTypeApiInterface;
use Akeneo\Pim\ApiClient\Api\AttributeApiInterface;
use Akeneo\Pim\ApiClient\Api\AttributeGroupApiInterface;
use Akeneo\Pim\ApiClient\Api\AttributeOptionApiInterface;
use Akeneo\Pim\ApiClient\Api\CategoryApiInterface;
use Akeneo\Pim\ApiClient\Api\ChannelApiInterface;
use Akeneo\Pim\ApiClient\Api\CurrencyApiInterface;
use Akeneo\Pim\ApiClient\Api\FamilyApiInterface;
use Akeneo\Pim\ApiClient\Api\FamilyVariantApiInterface;
use Akeneo\Pim\ApiClient\Api\LocaleApiInterface;
use Akeneo\Pim\ApiClient\Api\MeasureFamilyApiInterface;
use Akeneo\Pim\ApiClient\Api\MeasurementFamilyApiInterface;
use Akeneo\Pim\ApiClient\Api\MediaFileApiInterface;
use Akeneo\Pim\ApiClient\Api\ProductApiInterface;
use Akeneo\Pim\ApiClient\Api\ProductModelApiInterface;
use App\Storage\AccessTokenStorageInterface;
use App\Storage\PimURLStorageInterface;
use Psr\Http\Client\ClientInterface;

class PimApiClient implements AkeneoPimClientInterface
{
    private ?AkeneoPimClientInterface $authenticatedPimApiClient = null;

    public function __construct(
        private AccessTokenStorageInterface $accessTokenStorage,
        private ClientInterface $httpClient,
        private PimURLStorageInterface $pimURLStorage,
    ) {
    }

    private function getAuthenticatedPimApiClient(): AkeneoPimClientInterface
    {
        if (null === $this->authenticatedPimApiClient) {
            $pimURL = $this->pimURLStorage->getPimURL();
            if (empty($pimURL)) {
                throw new \LogicException('Could not retrieve PIM URL, please restart the authorization process.');
            }

            $accessToken = $this->accessTokenStorage->getAccessToken();
            if (empty($accessToken)) {
                throw new \LogicException('Missing Pim API access token.');
            }

            $clientBuilder = new AkeneoPimClientBuilder($pimURL);
            $clientBuilder->setHttpClient($this->httpClient);

            $this->authenticatedPimApiClient = $clientBuilder->buildAuthenticatedByToken(
                '',
                '',
                $accessToken,
                ''
            );
        }

        return $this->authenticatedPimApiClient;
    }

    public function getToken(): ?string
    {
        return $this->getAuthenticatedPimApiClient()->getToken();
    }

    public function getRefreshToken(): ?string
    {
        return $this->getAuthenticatedPimApiClient()->getRefreshToken();
    }

    public function getProductApi(): ProductApiInterface
    {
        return $this->getAuthenticatedPimApiClient()->getProductApi();
    }

    public function getCategoryApi(): CategoryApiInterface
    {
        return $this->getAuthenticatedPimApiClient()->getCategoryApi();
    }

    public function getAttributeApi(): AttributeApiInterface
    {
        return $this->getAuthenticatedPimApiClient()->getAttributeApi();
    }

    public function getAttributeOptionApi(): AttributeOptionApiInterface
    {
        return $this->getAuthenticatedPimApiClient()->getAttributeOptionApi();
    }

    public function getAttributeGroupApi(): AttributeGroupApiInterface
    {
        return $this->getAuthenticatedPimApiClient()->getAttributeGroupApi();
    }

    public function getFamilyApi(): FamilyApiInterface
    {
        return $this->getAuthenticatedPimApiClient()->getFamilyApi();
    }

    public function getProductMediaFileApi(): MediaFileApiInterface
    {
        return $this->getAuthenticatedPimApiClient()->getProductMediaFileApi();
    }

    public function getLocaleApi(): LocaleApiInterface
    {
        return $this->getAuthenticatedPimApiClient()->getLocaleApi();
    }

    public function getChannelApi(): ChannelApiInterface
    {
        return $this->getAuthenticatedPimApiClient()->getChannelApi();
    }

    public function getCurrencyApi(): CurrencyApiInterface
    {
        return $this->getAuthenticatedPimApiClient()->getCurrencyApi();
    }

    public function getMeasureFamilyApi(): MeasureFamilyApiInterface
    {
        return $this->getAuthenticatedPimApiClient()->getMeasureFamilyApi();
    }

    public function getMeasurementFamilyApi(): MeasurementFamilyApiInterface
    {
        return $this->getAuthenticatedPimApiClient()->getMeasurementFamilyApi();
    }

    public function getAssociationTypeApi(): AssociationTypeApiInterface
    {
        return $this->getAuthenticatedPimApiClient()->getAssociationTypeApi();
    }

    public function getFamilyVariantApi(): FamilyVariantApiInterface
    {
        return $this->getAuthenticatedPimApiClient()->getFamilyVariantApi();
    }

    public function getProductModelApi(): ProductModelApiInterface
    {
        return $this->getAuthenticatedPimApiClient()->getProductModelApi();
    }
}
