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
        MIICWgIBAAKBgFlzel2DFgpM7Ra5xwKbWYw29JK+KqrsTkjv2OPmKcz/AkxDOyy8
        MJnJIHe+CQXdPFb1bIJc9Q9Ees/2iDlEmi8iWCOe8xuw+GyJXSBa5oIOKAvxiH3W
        9bhyMfe8gZlvKHEvrLF+01FJBZFBKiEEDEshyuCgbXSz5psP1t4cOrEtAgMBAAEC
        gYAYeEaZHisBVlnlRZzzUZwFh2MQYYU6jLo9qZ8jeOsmcPwn8JxXeIOzDhobp5jA
        Se0fvLOaVeOT8Z/HFCHfyKyEtVUHXPnKTTFTGINYDG89QI4UeOfkwJhkYLMhlMJO
        Fg4sPVBGj8yYAfYHOvdHRFKnjV29zZWFZDEqXRiLEAesAQJBAKLF0oRNxk0giiAl
        dcKsE4uGmsSDqhrZlkOlIWkeJ3xQw7ZxH5NXuHH7tFRICo5UXHw+eNeQviWE6XQ2
        aF6KMyECQQCMrwlzRodweLkII+VQf7+BcNIPtmGbesNZ92K4xf0yEVVZS/bm+4rs
        iu5jmCVfh447doqUJwmUkiZhcJfjk4iNAkA5vtiKW1Uoc4zNDr0STR25+Azb/qHQ
        WLT4VpLdyfbUIYrtJIDBMvOabGNzKwOjrsYIxdj1EMKEaPyxX8PzFjBBAkBrEC5d
        9xfNxWHzSvYSDBZe2NBUOtUPcR7IEdekjLCC8OQGICSXZmk0WQrQ6pHOoKfiovUV
        iJvm4E6rKve8rqNlAkAnhBpyfoDGyISY73lBqqMcVtIl8t4515du6ynYCiX2Y/kv
        QDZcQmAgJU/c0jSVUVLIcK+deBUfQ9CuI72fdfBK
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
        -----BEGIN RSA PRIVATE KEY-----
        MIICXAIBAAKBgQC8kGa1pSjbSYZVebtTRBLxBz5H4i2p/llLCrEeQhta5kaQu/Rn
        vuER4W8oDH3+3iuIYW4VQAzyqFpwuzjkDI+17t5t0tyazyZ8JXw+KgXTxldMPEL9
        5+qVhgXvwtihXC1c5oGbRlEDvDF6Sa53rcFVsYJ4ehde/zUxo6UvS7UrBQIDAQAB
        AoGAb/MXV46XxCFRxNuB8LyAtmLDgi/xRnTAlMHjSACddwkyKem8//8eZtw9fzxz
        bWZ/1/doQOuHBGYZU8aDzzj59FZ78dyzNFoF91hbvZKkg+6wGyd/LrGVEB+Xre0J
        Nil0GReM2AHDNZUYRv+HYJPIOrB0CRczLQsgFJ8K6aAD6F0CQQDzbpjYdx10qgK1
        cP59UHiHjPZYC0loEsk7s+hUmT3QHerAQJMZWC11Qrn2N+ybwwNblDKv+s5qgMQ5
        5tNoQ9IfAkEAxkyffU6ythpg/H0Ixe1I2rd0GbF05biIzO/i77Det3n4YsJVlDck
        ZkcvY3SK2iRIL4c9yY6hlIhs+K9wXTtGWwJBAO9Dskl48mO7woPR9uD22jDpNSwe
        k90OMepTjzSvlhjbfuPN1IdhqvSJTDychRwn1kIJ7LQZgQ8fVz9OCFZ/6qMCQGOb
        qaGwHmUK6xzpUbbacnYrIM6nLSkXgOAwv7XXCojvY614ILTK3iXiLBOxPu5Eu13k
        eUz9sHyD6vkgZzjtxXECQAkp4Xerf5TGfQXGXhxIX52yH+N2LtujCdkQZjXAsGdm
        B2zNzvrlgRmgBrklMTrMYgm1NPcW+bRLGcwgW2PTvNM=
        -----END RSA PRIVATE KEY-----
        EOD,
            'public' => <<<EOD
        -----BEGIN PUBLIC KEY-----
        MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC8kGa1pSjbSYZVebtTRBLxBz5H
        4i2p/llLCrEeQhta5kaQu/RnvuER4W8oDH3+3iuIYW4VQAzyqFpwuzjkDI+17t5t
        0tyazyZ8JXw+KgXTxldMPEL95+qVhgXvwtihXC1c5oGbRlEDvDF6Sa53rcFVsYJ4
        ehde/zUxo6UvS7UrBQIDAQAB
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
