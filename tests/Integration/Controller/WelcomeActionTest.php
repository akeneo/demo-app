<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Tests\Integration\AbstractIntegrationTest;
use Symfony\Component\HttpFoundation\Response;

class WelcomeActionTest extends AbstractIntegrationTest
{
    /**
     * @test
     */
    public function itRedirectsToTheProductsPageWhenTheAccessTokenIsSet(): void
    {
        $client = $this->initializeClientWithSession(['akeneo_pim_access_token' => 'random_token']);
        $client->request('GET', '/?pim_url=https://httpd');
        $this->assertResponseRedirects('/products', Response::HTTP_FOUND);
    }

    /**
     * @test
     */
    public function itThrowsAnExceptionWhenThePimUrlIsMissing(): void
    {
        $client = $this->initializeClientWithSession([]);
        $client->request('GET', '/');
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function itThrowsAnExceptionWhenThePimUrlIsInvalid(): void
    {
        $client = $this->initializeClientWithSession([]);
        $client->request('GET', '/?pim_url=INVALID_URL');
        $client->request('GET', '/');
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function itSavesThePimUrlInSessionAndRenderTheWelcomePage(): void
    {
        $client = $this->initializeClientWithSession([]);
        $client->request('GET', '/?pim_url=https://httpd');
        $this->assertEquals('https://httpd', $client->getRequest()->getSession()->get('pim_url'));
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.connect-container__connect-button', 'Connect');
    }
}
