<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use Symfony\Component\HttpFoundation\Response;

class WelcomeActionTest extends AbstractActionTest
{
    /**
     * @test
     */
    public function itRedirectsToTheProductsPageWhenTheAccessTokenIsSet(): void
    {
        $client = self::createClientWithSession(['akeneo_pim_access_token' => 'random_token']);
        $client->request('GET', '/?pim_url=https://httpd');
        $this->assertResponseRedirects('/products', Response::HTTP_FOUND);
    }

    /**
     * @test
     */
    public function itThrowsAnExceptionWhenThePimUrlIsMissing(): void
    {
        $client = self::createClientWithSession([]);
        $client->request('GET', '/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.connect-container__message', 'Go to your PIM and click on Connect in the Marketplace page.');
    }

    /**
     * @test
     */
    public function itThrowsAnExceptionWhenThePimUrlIsInvalid(): void
    {
        $client = self::createClientWithSession([]);
        $client->request('GET', '/?pim_url=INVALID_URL');
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function itSavesThePimUrlInSessionAndRenderTheWelcomePage(): void
    {
        $client = self::createClientWithSession([]);
        $client->request('GET', '/?pim_url=https://httpd');
        $this->assertEquals('https://httpd', $client->getRequest()->getSession()->get('pim_url'));
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.connect-container__connect-button', 'Connect');
    }
}
