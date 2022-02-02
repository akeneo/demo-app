<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Storage\AccessTokenStorageInterface;
use App\Storage\PimURLStorageInterface;
use App\Tests\Services\Storage\AccessTokenInMemoryStorage;
use App\Tests\Services\Storage\PimURLInMemoryStorage;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpFoundation\Session\SessionFactoryInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class AbstractIntegrationTest extends WebTestCase
{
    protected ?ContainerInterface $container;
    protected ?KernelBrowser $client;

    /**
     * @var array<array-key, array{method: string, url: string, options: array<string, mixed>, response: ResponseInterface}>
     */
    private array $mockedRequests = [];

    public function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->container = $this->client->getContainer();

        $httpClient = $this->container->get(HttpClientInterface::class);
        \assert($httpClient instanceof MockHttpClient);

        $httpClient->setResponseFactory(function (string $method, string $url, array $options) {
            return $this->findMockedHttpResponse($method, $url, $options);
        });

        $this->mockedRequests = [];
    }

    /**
     * @param array<string, string> $data
     */
    protected function initializeClientWithSession(array $data): KernelBrowser
    {
        /** @var SessionFactoryInterface $sessionFactory */
        $sessionFactory = $this->client->getContainer()->get('session.factory');
        $session = $sessionFactory->createSession();

        $session->replace($data);
        $session->save();
        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);

        return $this->client;
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function mockHttpResponse(string $method, string $url, array $options, ResponseInterface $response): void
    {
        array_unshift($this->mockedRequests, [
            'method' => $method,
            'url' => $url,
            'options' => $options,
            'response' => $response,
        ]);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function findMockedHttpResponse(string $method, string $url, array $options): ResponseInterface
    {
        foreach ($this->mockedRequests as $mock) {
            if ($mock['method'] !== $method || $mock['url'] !== $url) {
                continue;
            }

            // @todo support diff of $options

            return $mock['response'];
        }

        throw new \LogicException(sprintf('No mock matches the request %s %s', $method, $url));
    }

    protected function setUpFakeAccessTokenStorage(string $token = 'fake_access_token'): AccessTokenStorageInterface
    {
        $this->container->set(
            'test.App\Storage\AccessTokenSessionStorage',
            new AccessTokenInMemoryStorage($token)
        );

        return $this->container->get(AccessTokenStorageInterface::class);
    }

    protected function setUpFakePimUrlStorage(string $url = 'https://example.com'): PimURLStorageInterface
    {
        $this->container->set(
            'test.App\Storage\PimURLStorageInterface',
            new PimURLInMemoryStorage($url)
        );

        return $this->container->get(PimURLStorageInterface::class);
    }
}
