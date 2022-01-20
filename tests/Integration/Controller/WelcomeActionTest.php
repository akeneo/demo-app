<?php

namespace Integration\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionFactoryInterface;

class WelcomeActionTest extends WebTestCase
{
    /**
     * @test
     */
    public function itRedirectsToTheProductsPageWhenTheAccessTokenIsSet(): void
    {
        $client = $this->getClientWithSession(['akeneo_pim_access_token' => 'random_token']);
        $client->request('GET', '/?pim_url=https://httpd');
        $this->assertResponseRedirects('/products', Response::HTTP_FOUND);
    }

    /**
     * @test
     */
    public function itThrowsALogicExceptionWhenThePimUrlIsEmpty(): void
    {
        $client = $this->getClientWithSession([]);
        $client->request('GET', '/');
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function itSavesThePimUrlInSessionAndRenderTheWelcomePage(): void
    {
        $client = $this->getClientWithSession([]);
        $client->request('GET', '/?pim_url=https://httpd');
        $this->assertEquals('https://httpd', $client->getRequest()->getSession()->get('pim_url'));
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Welcome');
    }

    /**
     * @param array<string, string> $data
     */
    private function getClientWithSession(array $data): KernelBrowser
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
