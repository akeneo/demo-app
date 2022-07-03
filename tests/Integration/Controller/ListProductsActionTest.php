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
        $this->mockPimAPIResponse(
            'get-products-scanners.json',
            'https://example.com/api/rest/v1/products?search=%7B%22identifier%22%3A%5B%7B%22operator%22%3A%22IN%22%2C%22value%22%3A%5B%221004114%22%2C%2210649473%22%2C%2210655295%22%2C%2210655470%22%2C%2210661721%22%2C%221211614%22%2C%2212239052%22%2C%2212239064%22%2C%2212249736%22%2C%2212249740%22%5D%7D%5D%7D&locales=en_US&limit=10&with_count=false',
        );
    }

    /**
     * @test
     */
    public function itDisplaysTenProductsAndTheCurrentLocale(): void
    {
        $client = $this->initializeClientWithSession([
            'pim_url' => 'https://example.com',
            'akeneo_pim_access_token' => 'random_access_token',
            'akeneo_pim_catalog_id' => 'catalog_store_us_id',
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
            'akeneo_pim_catalog_id' => 'catalog_store_us_id',
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
            'akeneo_pim_catalog_id' => 'catalog_store_us_id',
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
        $catalogConfigurationUrl = 'https://example.com/connect/apps/v1/catalogs/catalog_store_fr_id';
        $client = $this->initializeClientWithSession([
            'pim_url' => 'https://example.com',
            'akeneo_pim_access_token' => 'random_access_token',
            'akeneo_pim_catalog_id' => 'catalog_store_fr_id',
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
            'https://example.com/api/rest/v1/catalogs/catalog_store_fr_id',
            [],
            new MockResponse('', ['http_code' => 404])
        );

        $client = $this->initializeClientWithSession([
            'pim_url' => 'https://example.com',
            'akeneo_pim_access_token' => 'random_access_token',
            'akeneo_pim_catalog_id' => 'catalog_store_fr_id',
        ]);

        $client->request('GET', '/products');

        $this->assertResponseRedirects('/authorization/activate', Response::HTTP_FOUND);

        $this->assertNull($client->getRequest()->getSession()->get('akeneo_pim_catalog_id'));
    }
}
