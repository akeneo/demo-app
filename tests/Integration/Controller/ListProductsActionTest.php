<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Tests\Integration\AbstractIntegrationTest;
use App\Tests\Integration\MockPimApiTrait;

class ListProductsActionTest extends AbstractIntegrationTest
{
    use MockPimApiTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->mockDefaultPimAPIResponses();
        $this->mockPimAPIResponse(
            'get-products-scanners.json',
            'https://example.com/api/rest/v1/products?search=%7B%22enabled%22%3A%5B%7B%22operator%22%3A%22%3D%22%2C%22value%22%3Atrue%7D%5D%7D&locales=en_US&limit=10&with_count=false',
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
        $pimUrl = 'https://httpd';
        $client = self::createClientWithSession([
            'pim_url' => $pimUrl,
            'akeneo_pim_access_token' => 'random_access_token_123456',
        ]);

        $this->mockPimApiClientResponse(
            $client,
            new RequestMatcher('/api/rest/v1/locales', 'httpd', ['GET'], ['https']),
            $this->getPimApiMockResponse('getLocalesListWithOnlyEnUs.json'),
        );

        $this->mockPimApiClientResponse(
            $client,
            new RequestMatcher('/api/rest/v1/products', 'httpd', ['GET'], ['https']),
            $this->getPimApiMockResponse('getProductsListWith7TshirtsAnd4Caps.json'),
        );

        $this->mockPimApiClientResponse(
            $client,
            new RequestMatcher('/api/rest/v1/families/tshirt', 'httpd', ['GET'], ['https']),
            $this->getPimApiMockResponse('getFamilyTshirt.json'),
        );

        $this->mockPimApiClientResponse(
            $client,
            new RequestMatcher('/api/rest/v1/families/cap', 'httpd', ['GET'], ['https']),
            $this->getPimApiMockResponse('getFamilyCap.json'),
        );

        $client->request('GET', '/products');

        $this->assertEquals($pimUrl, $client->getCrawler()->selectLink('Go to Akeneo PIM')->attr('href'));
    }

    /**
     * @test
     */
    public function itRendersGoodLinksInHeader(): void
    {
        $pimUrl = 'https://httpd';
        $client = self::createClientWithSession([
            'pim_url' => $pimUrl,
            'akeneo_pim_access_token' => 'random_access_token_123456',
        ]);

        $this->mockPimApiClientResponse(
            $client,
            new RequestMatcher('/api/rest/v1/locales', 'httpd', ['GET'], ['https']),
            $this->getPimApiMockResponse('getLocalesListWithOnlyEnUs.json'),
        );

        $this->mockPimApiClientResponse(
            $client,
            new RequestMatcher('/api/rest/v1/products', 'httpd', ['GET'], ['https']),
            $this->getPimApiMockResponse('getProductsListWith7TshirtsAnd4Caps.json'),
        );

        $this->mockPimApiClientResponse(
            $client,
            new RequestMatcher('/api/rest/v1/families/tshirt', 'httpd', ['GET'], ['https']),
            $this->getPimApiMockResponse('getFamilyTshirt.json'),
        );

        $this->mockPimApiClientResponse(
            $client,
            new RequestMatcher('/api/rest/v1/families/cap', 'httpd', ['GET'], ['https']),
            $this->getPimApiMockResponse('getFamilyCap.json'),
        );

        $client->request('GET', '/products');

        $this->assertEquals(
            'https://marketplace.akeneo.com/extension/app-demo',
            $client->getCrawler()->selectLink('Help')->attr('href')
        );
    }
}
