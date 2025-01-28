<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Tests\Integration\AbstractIntegrationTest;
use App\Tests\Integration\MockPimApiTrait;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CallbackActionTest extends AbstractIntegrationTest
{
    use MockPimApiTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = $this->initializeClientWithSession([
            'pim_url' => 'https://example.com',
            'state' => 'random_state_123456789',
        ]);
    }

    /**
     * @test
     */
    public function itThrowsAnExceptionWhenThePimUrlIsMissingInSession(): void
    {
        $client = $this->initializeClientWithSession([]);
        $client->request('GET', '/callback');
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function itThrowsAnExceptionWhenTheStateIsInvalid(): void
    {
        $this->client->request('GET', '/callback?code=code&state=random_state_abcdefgh');

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function itThrowsAnExceptionWhenTheCodeIsMissingInUrl(): void
    {
        $httpClient = $this->client->getContainer()->get(HttpClientInterface::class);
        \assert($httpClient instanceof MockHttpClient);

        $httpClient->setResponseFactory([
            new MockResponse(\json_encode(['access_token' => 'access_token'])),
        ]);
        $this->client->request('GET', '/callback?state=random_state_123456789');

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function itThrowsAnExceptionWhenAccessTokenIsMissing(): void
    {
        $httpClient = $this->client->getContainer()->get(HttpClientInterface::class);
        \assert($httpClient instanceof MockHttpClient);

        $httpClient->setResponseFactory([
            new MockResponse(\json_encode([])),
        ]);

        $this->client->request('GET', '/callback?code=code&state=random_state_123456789');

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function itRedirectsToTheAuthorizeUrlWithQueryParameters(): void
    {
        $this->mockHttpResponse(
            'POST',
            'https://example.com/connect/apps/v1/oauth2/token',
            [],
            new MockResponse(\json_encode(['access_token' => 'random_access_token']))
        );
        $this->mockPimAPIResponse(
            'get-catalogs.json',
            'https://example.com/api/rest/v1/catalogs',
        );

        $this->client->request('GET', '/callback?code=code&state=random_state_123456789');

        $this->assertAccessTokenIsStored('random_access_token');
        $this->assertResponseRedirects('/catalogs', Response::HTTP_FOUND);
    }

    /**
     * @test
     */
    public function itFetchesAccessTokenWithAuthenticationScopesAndRedirectsToCatalogsPage(): void
    {
        ['private' => $privateKey, 'public' => $publicKey] = $this->getAsymmetricKeyPair();
        $idToken = $this->generateIdToken($privateKey, $publicKey);

        $this->mockHttpResponse(
            'POST',
            'https://example.com/connect/apps/v1/oauth2/token',
            [],
            new MockResponse(\json_encode([
                'access_token' => 'random_access_token',
                'id_token' => $idToken,
            ])),
        );
        $this->mockHttpResponse(
            'GET',
            'https://example.com/connect/apps/v1/openid/public-key',
            [],
            new MockResponse(\json_encode(['public_key' => $publicKey]))
        );
        $this->mockPimAPIResponse(
            'get-catalogs.json',
            'https://example.com/api/rest/v1/catalogs',
        );

        $this->client->request('GET', '/callback?code=code&state=random_state_123456789');

        $this->assertAccessTokenIsStored('random_access_token');
        $this->assertUserProfileIsStored('John Doe');
        $this->assertResponseRedirects('/catalogs', Response::HTTP_FOUND);
    }

    /**
     * @test
     */
    public function itCreatesCatalogWhenNoCatalogExists(): void
    {
        ['private' => $privateKey, 'public' => $publicKey] = $this->getAsymmetricKeyPair();
        $idToken = $this->generateIdToken($privateKey, $publicKey);

        $this->mockHttpResponse(
            'POST',
            'https://example.com/connect/apps/v1/oauth2/token',
            [],
            new MockResponse(\json_encode(['access_token' => 'random_access_token']))
        );
        $this->mockPimAPIResponse(
            'get-catalogs-empty-list.json',
            'https://example.com/api/rest/v1/catalogs',
        );

        $this->mockHttpResponse(
            'POST',
            'https://example.com/api/rest/v1/catalogs',
            [],
            new MockResponse(\json_encode([
                'id' => '7e018bfd-00e1-4642-951e-4d45684b51f4',
                'name' => 'Catalog with product value filters',
                'enabled' => true,
            ], JSON_THROW_ON_ERROR),
                ['http_code' => 201],
            )
        );

        $this->mockHttpResponse(
            'PUT',
            'https://example.com/api/rest/v1/catalogs/7e018bfd-00e1-4642-951e-4d45684b51f4/mapping-schemas/product',
            [],
            new MockResponse('', ['http_code' => 204])
        );

        $this->client->request('GET', '/callback?code=code&state=random_state_123456789');

        $this->assertResponseRedirects('/catalogs', Response::HTTP_FOUND);
    }

    /**
     * @test
     */
    public function itThrowsAnExceptionWhenPublicKeyIsMissing(): void
    {
        ['private' => $privateKey, 'public' => $publicKey] = $this->getAsymmetricKeyPair();
        $idToken = $this->generateIdToken($privateKey, $publicKey);

        $httpClient = $this->client->getContainer()->get(HttpClientInterface::class);
        assert($httpClient instanceof MockHttpClient);
        $httpClient->setResponseFactory([
            new MockResponse(\json_encode([
                'access_token' => 'random_access_token',
                'id_token' => $idToken,
            ])),
        ]);

        $this->client->request('GET', '/callback?code=code&state=random_state_123456789');

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function itThrowsAnExceptionOnBadIdTokenSignature(): void
    {
        ['public' => $publicKey] = $this->getAsymmetricKeyPair();

        $alteredPrivateKey = <<<EOD
        -----BEGIN RSA PRIVATE KEY-----
        MIIEowIBAAKCAQEA3AxQMVKxuar+MkcpiRph2s+ppdLCYhofJ1dO4mZLKtvC/XTw
        5wMd8RMFmawJi5MGVxDKXhGLykhX+KgjyYk3XcfRL7McOLaqki3qo1/RlGrRBngR
        NRVCDduzg7GKZsOC0b1Iep0WZNGIChbjBLm6EGo69vWFJzXr+ijm0sT76dg59shp
        /u9OgMmRqAJdavzI9O/CQI0uxziir3+b1h+I/P6VqvEvnIjIDmkiF579Ix2sKKhG
        mh96HQuognjTwnVLSUa6IxgAO12dt1FDIbKjMxqpE7GovhGg7TIZhW7Lat35KgWz
        K1XmRW2gmrbdOz5DcnDf0nnEA1nBppiHkV2NdwIDAQABAoIBAQCPPhy93tz+xkbv
        J8/sBhaJQAFphscu4V5CV91sF7b60VAfeg6P80F8eyt8G7ei+jR4XN+/WKCtL2bE
        4X9aZE58Z+TOrkPCz3Y97lH9xBREDzy+f06ERbBYIRq8scgsmT0Bl7wkxTmcq6Fp
        H/jsTJLIuL5loPHq0nkowZNwxPZQBFKzk0mp7ILd+VYN5W5DnP9YUesK7dpgSOYz
        HNN+UyB11rz8Iv4ZVLNGcajdvRQRDDLOJAZqMjGUT2Hz7jan+mbihWtTZYLOqOYZ
        dqG+LHbA5wY+U8ewNlEasz9FS7vPTD2UQjBzz4BeN1zB+TqmzcJP0j4d0LQ+a9r8
        WzF12fxhAoGBAPJna1368R5g1NtL01Lhi/LZt5GhSelM5mCtwP59fLHU75HFXLN1
        9RbGGkjlmQPWYQrkY+rwByypfQHs2CXnOa+LnRXQZXkr9q5fab7mPCdI1W4olfE4
        OJOuQF4tH++GMBC0gYVd2Nn9NKuQWBy4PYiLP7gsdbjSIxVCgeSHTkyvAoGBAOhj
        5Q/4AKfPni7GHEUoMtTyQF3cqwn2ZS5WKuw/ekV0anLCee/ICax8sCjBdp6LicHc
        Ne3c8+2LvwCfWQQJYi/5vUn45gYkGyqFCXLyD8Jd2z1bydgFvu3SAZkEl8W1avix
        nssm4vM2i9IGUFKaf7u4wW9eNP8pAQsINI6noM25AoGAd4VawToMTYg9K+vVRNaF
        JLcI9jtqsCgEp8LEmDbTlvOBNIT10l7k+9et+ieLLQM1UiOOyLaVMwZW5u4bHYVH
        QjE6wRjCD98HgK72+vOW0V/uLKTCWe/4pYiToFvrlTS62mHGQzYkyEc7AyTm7TJC
        1OKkBnGVkg260q66US4OA20CgYAtEorv8PL3mT4d5lC/XQ+W27F+QvltgjuXpCDJ
        F3q4k969iVirGApASSLFlNhT4c6DKhnPm6Y38X3HOiDCtqszkOWUvlCm6kPWmFz/
        zVEfKqMGIJJicqLYb33d1tU1BH3N5G1fC5jDAVZXEuBrVQnnN+tUlVoED0jjgeDn
        tj1rCQKBgBciMqqNS0nVHvLYUGe7ZL+fmMGK6wBUAzwZrtOpiGYaLtkCElL5NA8h
        jwURFmCsfiHnIa5Az1qkwtgM2aUqTJyQYQZOFOJSDtah1Xkr66lOnszJFzOyK9ol
        uWq4mwaTx1O4TdJL01RFOh8LoNux2+RrKlbIAbaqG/mZy2TZY1MM
        -----END RSA PRIVATE KEY-----
        EOD;

        $idToken = $this->generateIdToken($alteredPrivateKey, $publicKey);

        $httpClient = $this->client->getContainer()->get(HttpClientInterface::class);
        assert($httpClient instanceof MockHttpClient);
        $httpClient->setResponseFactory([
            new MockResponse(\json_encode([
                'access_token' => 'random_access_token',
                'id_token' => $idToken,
            ])),
            new MockResponse(\json_encode([
                'public_key' => $publicKey,
            ])),
        ]);

        $this->client->request('GET', '/callback?code=code&state=random_state_123456789');

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function itThrowsAnExceptionWhenUserProfileClaimsAreMissing(): void
    {
        ['private' => $privateKey, 'public' => $publicKey] = $this->getAsymmetricKeyPair();
        $idToken = $this->generateIdToken($privateKey, $publicKey, ['lastname' => 'Doe']);

        $httpClient = $this->client->getContainer()->get(HttpClientInterface::class);
        assert($httpClient instanceof MockHttpClient);
        $httpClient->setResponseFactory([
            new MockResponse(\json_encode([
                'access_token' => 'random_access_token',
                'id_token' => $idToken,
            ])),
            new MockResponse(\json_encode([
                'public_key' => $publicKey,
            ])),
        ]);

        $this->client->request('GET', '/callback?code=code&state=random_state_123456789');

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @return array{private: string, public: string}
     */
    private function getAsymmetricKeyPair(): array
    {
        return [
            'private' => <<<EOD
        -----BEGIN PRIVATE KEY-----
        MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQDTvwE87MtgREYL
        TL4aHhQo3ZzogmxxvMUsKnPzyxRs1YrXOSOpwN0npsXarBKKVIUMNLfFODp/vnQn
        2Zp06N8XG59WAOKwvC4MfxLDQkA+JXggzHlkbVoTN+dUkdYIFqSKuAPGwiWToRK2
        SxEhij3rE2FON8jQZvDxZkiP9a4vxJO3OTPQwKredXFiObsXD/c3RtLFhKctjCyH
        OIrP0bQEsee/m7JNtG4ry6BPusN6wb+vJo5ieBYPa3c19akNq6q/nYWhplhkkJSu
        aOrL5xXEFzI5TvcvnXR568GVcxK8YLfFkdxpsXGt5rAbeh0h/U5kILEAqv8P9PGT
        ZpicKbrnAgMBAAECggEAd3yTQEQHR91/ASVfKPHMQns77eCbPVtekFusbugsMHYY
        EPdHbqVMpvFvOMRc+f5Tzd15ziq6qBdbCJm8lThLm4iU0z1QrpaiDZ8vgUvDYM5Y
        CXoZDli+uZWUTp60/n94fmb0ipZIChScsI2PrzOJWTvobvD/uso8MJydWc8zafQm
        uqYzygOfjFZvU4lSfgzpefhpquy0JUy5TiKRmGUnwLb3TtcsVavjsn4QmNwLYgOF
        2OE+R12ex3pAKTiRE6FcnE1xFIo1GKhBa2Otgw3MDO6Gg+kn8Q4alKz6C6RRlgaH
        R7sYzEfJhsk/GGFTYOzXKQz2lSaStKt9wKCor04RcQKBgQDzPOu5jCTfayUo7xY2
        jHtiogHyKLLObt9l3qbwgXnaD6rnxYNvCrA0OMvT+iZXsFZKJkYzJr8ZOxOpPROk
        10WdOaefiwUyL5dypueSwlIDwVm+hI4Bs82MajHtzOozh+73wA+aw5rPs84Uix9w
        VbbwaVR6qP/BV09yJYS5kQ7fmwKBgQDe2xjywX2d2MC+qzRr+LfU+1+gq0jjhBCX
        WHqRN6IECB0xTnXUf9WL/VCoI1/55BhdbbEja+4btYgcXSPmlXBIRKQ4VtFfVmYB
        kPXeD8oZ7LyuNdCsbKNe+x1IHXDe6Wfs3L9ulCfXxeIE84wy3fd66mQahyXV9iD9
        CkuifMqUpQKBgQCiydHlY1LGJ/o9tA2Ewm5Na6mrvOs2V2Ox1NqbObwoYbX62eiF
        53xX5u8bVl5U75JAm+79it/4bd5RtKux9dUETbLOhwcaOFm+hM+VG/IxyzRZ2nMD
        1qcpY2U5BpxzknUvYF3RMTop6edxPk7zKpp9ubCtSu+oINvtxAhY/SkcIwKBgGP1
        upcImyO2GZ5shLL5eNubdSVILwV+M0LveOqyHYXZbd6z5r5OKKcGFKuWUnJwEU22
        6gGNY9wh7M9sJ7JBzX9c6pwqtPcidda2AtJ8GpbOTUOG9/afNBhiYpv6OKqD3w2r
        ZmJfKg/qvpqh83zNezgy8nvDqwDxyZI2j/5uIx/RAoGBAMWRmxtv6H2cKhibI/aI
        MTJM4QRjyPNxQqvAQsv+oHUbid06VK3JE+9iQyithjcfNOwnCaoO7I7qAj9QEfJS
        MZQc/W/4DHJebo2kd11yoXPVTXXOuEwLSKCejBXABBY0MPNuPUmiXeU0O3Tyi37J
        TUKzrgcd7NvlA41Y4xKcOqEA
        -----END PRIVATE KEY-----
        EOD,
            'public' => <<<EOD
        -----BEGIN PUBLIC KEY-----
        MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA078BPOzLYERGC0y+Gh4U
        KN2c6IJscbzFLCpz88sUbNWK1zkjqcDdJ6bF2qwSilSFDDS3xTg6f750J9madOjf
        FxufVgDisLwuDH8Sw0JAPiV4IMx5ZG1aEzfnVJHWCBakirgDxsIlk6EStksRIYo9
        6xNhTjfI0Gbw8WZIj/WuL8STtzkz0MCq3nVxYjm7Fw/3N0bSxYSnLYwshziKz9G0
        BLHnv5uyTbRuK8ugT7rDesG/ryaOYngWD2t3NfWpDauqv52FoaZYZJCUrmjqy+cV
        xBcyOU73L510eevBlXMSvGC3xZHcabFxreawG3odIf1OZCCxAKr/D/Txk2aYnCm6
        5wIDAQAB
        -----END PUBLIC KEY-----
        EOD,
        ];
    }

    /**
     * @param array<string, string> $withClaims
     */
    private function generateIdToken(
        string $privateKey,
        string $publicKey,
        array $withClaims = ['firstname' => 'John', 'lastname' => 'Doe'],
    ): string {
        $jwtConfig = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText($privateKey),
            InMemory::plainText($publicKey),
        );

        $now = new \DateTimeImmutable();

        $jwtTokenBuilder = $jwtConfig->builder()
            ->issuedBy('https://example.com')
            ->identifiedBy('uuid')
            ->relatedTo('ppid')
            ->permittedFor('clientId')
            ->issuedAt($now)
            ->expiresAt($now->modify('+1 hour'));

        foreach ($withClaims as $name => $value) {
            $jwtTokenBuilder->withClaim($name, $value);
        }

        $jwtToken = $jwtTokenBuilder->getToken(
            $jwtConfig->signer(),
            $jwtConfig->signingKey()
        );

        return $jwtToken->toString();
    }

    private function assertAccessTokenIsStored(string $expectedAccessToken): void
    {
        $savedAccessToken = $this->client?->getRequest()->getSession()->get('akeneo_pim_access_token');
        $this->assertEquals($expectedAccessToken, $savedAccessToken);
    }

    private function assertUserProfileIsStored(string $expectedUserProfile): void
    {
        $savedUserProfile = $this->client?->getRequest()->getSession()->get('akeneo_pim_user_profile');
        $this->assertEquals($expectedUserProfile, $savedUserProfile);
    }
}
