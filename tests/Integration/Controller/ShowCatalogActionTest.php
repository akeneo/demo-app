<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Tests\Integration\AbstractIntegrationTest;
use App\Tests\Integration\MockPimApiTrait;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;

class ShowCatalogActionTest extends AbstractIntegrationTest
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
    public function itDisplaysTenProductsOfValueFilterCatalog(): void
    {
        $client = $this->initializeClientWithSession([
            'pim_url' => 'https://example.com',
            'akeneo_pim_access_token' => 'random_access_token',
        ]);

        $client->request('GET', '/catalogs/70313d30-8316-41c2-b298-8f9e7186fe9a');
        $this->assertResponseIsSuccessful();

        $this->assertCount(10, $client->getCrawler()->filter('.product-card'));
    }

    /**
     * @test
     */
    public function itDisplaysTenProductsOfAttributeMappingCatalog(): void
    {
        $client = $this->initializeClientWithSession([
            'pim_url' => 'https://example.com',
            'akeneo_pim_access_token' => 'random_access_token',
        ]);

        $client->request('GET', '/catalogs/8a8494d2-05cc-4b8f-942e-f5ea7591e89c');
        $this->assertResponseIsSuccessful();

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
        ]);

        $client->request('GET', '/catalogs/70313d30-8316-41c2-b298-8f9e7186fe9a');

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
        ]);

        $client->request('GET', '/catalogs/70313d30-8316-41c2-b298-8f9e7186fe9a');

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
        $catalogConfigurationUrl = 'https://example.com/connect/apps/v1/catalogs/ad1f6e7a-e6d9-495f-b568-f4f473803679';
        $client = $this->initializeClientWithSession([
            'pim_url' => 'https://example.com',
            'akeneo_pim_access_token' => 'random_access_token',
        ]);

        $client->request('GET', '/catalogs/ad1f6e7a-e6d9-495f-b568-f4f473803679');
        $this->assertResponseIsSuccessful();

        $this->assertCount(0, $client->getCrawler()->filter('.product-card'));
        $this->assertEquals($catalogConfigurationUrl, $client->getCrawler()->selectLink('Configure catalog')->attr('href'));
    }

    /**
     * @test
     */
    public function itDisplaySpecificMessageWhenCatalogIsEmpty(): void
    {
        $this->mockPimAPIResponse(
            'get-catalogs-store-us-products-empty-list.json',
            'https://example.com/api/rest/v1/catalogs/70313d30-8316-41c2-b298-8f9e7186fe9a/products?limit=10',
        );
        $client = $this->initializeClientWithSession([
            'pim_url' => 'https://example.com',
            'akeneo_pim_access_token' => 'random_access_token',
        ]);

        $client->request('GET', '/catalogs/70313d30-8316-41c2-b298-8f9e7186fe9a');
        $this->assertResponseIsSuccessful();

        $translator = $this->container->get('translator');
        $this->assertEquals($translator->trans('page.catalog.no-products.title'), $client->getCrawler()->filter('.no-products__title')->first()->text());
    }

    /**
     * @test
     */
    public function itDisplaySpecificMessageWhenCatalogIsDisabled(): void
    {
        $client = $this->initializeClientWithSession([
            'pim_url' => 'https://example.com',
            'akeneo_pim_access_token' => 'random_access_token',
        ]);

        $client->request('GET', '/catalogs/ad1f6e7a-e6d9-495f-b568-f4f473803679');
        $this->assertResponseIsSuccessful();

        $translator = $this->container->get('translator');
        $this->assertEquals($translator->trans('page.catalog.catalog-disabled.title'), $client->getCrawler()->filter('.no-products__title')->first()->text());
    }

    /**
     * @test
     */
    public function itRedirectsToCatalogListPageWhenCatalogIsNotFound(): void
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
        ]);

        $client->request('GET', '/catalogs/8a8494d2-05cc-4b8f-942e-f5ea7591e89c');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
