<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\Strategy\MockClientStrategy;
use Http\Message\RequestMatcher\RequestMatcher;
use Psr\Http\Message\ResponseInterface as HttplugResponseInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Session\SessionFactoryInterface;

abstract class AbstractActionTest extends WebTestCase
{
    public function setUp(): void
    {
        HttpClientDiscovery::prependStrategy(MockClientStrategy::class);
    }

    /**
     * @param array<string, string> $data
     */
    protected static function createClientWithSession(array $data): KernelBrowser
    {
        $client = static::createClient();

        /** @var SessionFactoryInterface $sessionFactory */
        $sessionFactory = $client->getContainer()->get('session.factory');
        $session = $sessionFactory->createSession();

        $session->replace($data);
        $session->save();
        $cookie = new Cookie($session->getName(), $session->getId());
        $client->getCookieJar()->set($cookie);

        return $client;
    }

    protected function getPimApiMockResponse(string $filename): string
    {
        return \file_get_contents(__DIR__.'/../PimApiMockResponses/'.$filename);
    }

    /**
     * @param array<string, string> $responseHeaders
     */
    protected function mockPimApiClientResponse(
        KernelBrowser $client,
        RequestMatcher $requestMatcher,
        ?string $rawResponseBody = null,
        int $responseStatusCode = 200,
        array $responseHeaders = [],
    ): void {
        $response = $this->createMock(HttplugResponseInterface::class);
        $response->method('getStatusCode')->willReturn($responseStatusCode);
        $response->method('getHeaders')->willReturn($responseHeaders);

        if (null !== $rawResponseBody) {
            $responseBody = $this->createMock(StreamInterface::class);
            $responseBody->method('getContents')->willReturn($rawResponseBody);
            $response->method('getBody')->willReturn($responseBody);
        }

        $mockClient = $client->getContainer()->get('httplug.client.mock');
        $mockClient->on($requestMatcher, $response);
    }
}
