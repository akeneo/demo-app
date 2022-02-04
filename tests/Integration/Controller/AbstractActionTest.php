<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\Strategy\MockClientStrategy;
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
}
