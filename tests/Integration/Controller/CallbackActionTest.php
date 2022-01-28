<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CallbackActionTest extends AbstractActionTest
{
    /**
     * @test
     */
    public function itThrowsAnExceptionWhenThePimUrlIsMissingInSession(): void
    {
        $client = self::createClientWithSession([]);
        $client->request('GET', '/callback');
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function itThrowsAnExceptionWhenTheStateIsInvalid(): void
    {
        $client = self::createClientWithSession([
            'pim_url' => 'https://httpd',
            'state' => 'random_state_123456789',
        ]);

        $client->request('GET', '/callback?code=code&state=random_state_abcdefgh');

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function itThrowsAnExceptionWhenTheCodeIsMissingInUrl(): void
    {
        $client = self::createClientWithSession([
            'pim_url' => 'https://example.com',
            'state' => 'random_state_123456789',
        ]);

        $httpClient = $client->getContainer()->get(HttpClientInterface::class);
        \assert($httpClient instanceof MockHttpClient);

        $httpClient->setResponseFactory([
            new MockResponse(\json_encode(['access_token' => 'access_token'])),
        ]);
        $client->request('GET', '/callback?state=random_state_123456789');

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function itThrowsAnExceptionWhenAccessTokenIsMissing(): void
    {
        $client = self::createClientWithSession([
            'pim_url' => 'https://httpd',
            'state' => 'random_state_123456789',
        ]);

        $httpClient = $client->getContainer()->get(HttpClientInterface::class);
        \assert($httpClient instanceof MockHttpClient);

        $httpClient->setResponseFactory([
            new MockResponse(\json_encode([])),
        ]);

        $client->request('GET', '/callback?code=code&state=random_state_123456789');

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function itRedirectsToTheAuthorizeUrlWithQueryParameters(): void
    {
        $client = self::createClientWithSession([
            'pim_url' => 'https://httpd',
            'state' => 'random_state_123456789',
        ]);

        $httpClient = $client->getContainer()->get(HttpClientInterface::class);
        assert($httpClient instanceof MockHttpClient);

        $httpClient->setResponseFactory([
            new MockResponse(\json_encode(['access_token' => 'random_access_token'])),
        ]);

        $client->request('GET', '/callback?code=code&state=random_state_123456789');
        $accessToken = $client->getRequest()->getSession()->get('akeneo_pim_access_token');
        $this->assertEquals('random_access_token', $accessToken);
        $this->assertResponseRedirects('/products', Response::HTTP_FOUND);
    }
}
