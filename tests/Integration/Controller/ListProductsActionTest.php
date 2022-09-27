<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Tests\Integration\AbstractIntegrationTest;
use App\Tests\Integration\MockPimApiTrait;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;

class ListProductsActionTest extends AbstractIntegrationTest
{
    use MockPimApiTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->mockDefaultPimAPIResponses();
    }

    /**
     * @test
     */
    public function itDisplaysTenProductsAndTheCurrentLocale(): void
    {
        $client = $this->initializeClientWithSession([
            'pim_url' => 'https://example.com',
            'akeneo_pim_access_token' => 'random_access_token',
            'akeneo_pim_catalog_id' => '70313d30-8316-41c2-b298-8f9e7186fe9a',
        ]);

        $client->request('GET', '/products');
        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains('.current-locale', '🇺🇸 English (United States)');
        $this->assertCount(10, $client->getCrawler()->filter('.product-card'));
    }

    /**
     * @test
     */
    public function itRendersALinkThatTargetThePimUrl(): void
    {
        $client = $this->initializeClientWithSession([
            'pim_url' => 'https://example.com',
            'akeneo_pim_access_token' => 'random_access_token_123456',
            'akeneo_pim_catalog_id' => '70313d30-8316-41c2-b298-8f9e7186fe9a',
        ]);

        $client->request('GET', '/products');

        $this->assertEquals('https://example.com', $client->getCrawler()->selectLink('Go to Akeneo PIM')->attr('href'));
    }

    /**
     * @test
     */
    public function itRendersGoodLinksInHeader(): void
    {
        $client = $this->initializeClientWithSession([
            'pim_url' => 'https://example.com',
            'akeneo_pim_access_token' => 'random_access_token_123456',
            'akeneo_pim_catalog_id' => '70313d30-8316-41c2-b298-8f9e7186fe9a',
        ]);

        $client->request('GET', '/products');

        $this->assertEquals(
            'https://marketplace.akeneo.com/extension/akeneo-demo-app',
            $client->getCrawler()->selectLink('Help')->attr('href')
        );
    }

    /**
     * @test
     */
    public function itDisplaysAnEmptyListWithALinkForCatalogConfiguration(): void
    {
        $catalogConfigurationUrl = 'https://example.com/connect/apps/v1/catalogs/8a8494d2-05cc-4b8f-942e-f5ea7591e89c';
        $client = $this->initializeClientWithSession([
            'pim_url' => 'https://example.com',
            'akeneo_pim_access_token' => 'random_access_token',
            'akeneo_pim_catalog_id' => '8a8494d2-05cc-4b8f-942e-f5ea7591e89c',
        ]);

        $client->request('GET', '/products');
        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains('.current-locale', '🇺🇸 English (United States)');
        $this->assertCount(0, $client->getCrawler()->filter('.product-card'));
        $this->assertEquals($catalogConfigurationUrl, $client->getCrawler()->selectLink('Configure catalog')->attr('href'));
    }

    /**
     * @test
     */
    public function itRedirectsToActivateWhenCatalogIsNotInSession(): void
    {
        $client = $this->initializeClientWithSession([
            'pim_url' => 'https://example.com',
            'akeneo_pim_access_token' => 'random_access_token',
        ]);

        $client->request('GET', '/products');

        $this->assertResponseRedirects('/authorization/activate', Response::HTTP_FOUND);

        $this->assertNull($client->getRequest()->getSession()->get('akeneo_pim_catalog_id'));
    }

    /**
     * @test
     */
    public function itRedirectsToActivateWhenCatalogIsNotFound(): void
    {
        $this->mockHttpResponse(
            'GET',
            'https://example.com/api/rest/v1/catalogs/8a8494d2-05cc-4b8f-942e-f5ea7591e89c',
            [],
            new MockResponse('', ['http_code' => 404])
        );

        $client = $this->initializeClientWithSession([
            'pim_url' => 'https://example.com',
            'akeneo_pim_access_token' => 'random_access_token',
            'akeneo_pim_catalog_id' => '8a8494d2-05cc-4b8f-942e-f5ea7591e89c',
        ]);

        $client->request('GET', '/products');

        $this->assertResponseRedirects('/authorization/activate', Response::HTTP_FOUND);

        $this->assertNull($client->getRequest()->getSession()->get('akeneo_pim_catalog_id'));
    }
}
