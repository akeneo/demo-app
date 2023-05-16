<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Tests\Integration\AbstractIntegrationTest;
use App\Tests\Integration\MockPimApiTrait;
use Symfony\Component\HttpClient\Response\MockResponse;

class ShowProductActionTest extends AbstractIntegrationTest
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
    public function itDisplaysAProduct(): void
    {
        $client = $this->initializeClientWithSession([
            'pim_url' => 'https://example.com',
            'akeneo_pim_access_token' => 'random_access_token',
        ]);

        $this->mockPimAPIResponse(
            'get-catalog-product-value-filters.json',
            'https://example.com/api/rest/v1/catalogs/70313d30-8316-41c2-b298-8f9e7186fe9a',
        );
        $this->mockPimAPIResponse(
            'get-catalogs-store-us-product-sunglasses.json',
            'https://example.com/api/rest/v1/catalogs/70313d30-8316-41c2-b298-8f9e7186fe9a/products/16467667-9a29-48c1-90b3-8a169b83e8e6',
        );

        $crawler = $client->request('GET', '/catalogs/70313d30-8316-41c2-b298-8f9e7186fe9a/products/16467667-9a29-48c1-90b3-8a169b83e8e6');
        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains('.locale-switcher__language', '🇺🇸 English (United States)');
        $this->assertSelectorTextContains('h1.page-title', 'Sunglasses');

        $foundAttributes = $crawler->filter('.attribute');
        $this->assertEquals(3, $foundAttributes->count());

        $eanLabel = $foundAttributes->eq(0)->filter('.attribute__label')->text();
        $this->assertEquals('EAN', $eanLabel);

        $eanLabel = $foundAttributes->eq(1)->filter('.attribute__label')->text();
        $this->assertEquals('Name', $eanLabel);

        $eanLabel = $foundAttributes->eq(2)->filter('.attribute__label')->text();
        $this->assertEquals('Description', $eanLabel);
    }

    /**
     * @test
     */
    public function itReturnsNotFound(): void
    {
        $client = $this->initializeClientWithSession([
            'pim_url' => 'https://example.com',
            'akeneo_pim_access_token' => 'random_access_token',
        ]);

        $this->mockPimAPIResponse(
            'get-catalog-product-value-filters.json',
            'https://example.com/api/rest/v1/catalogs/70313d30-8316-41c2-b298-8f9e7186fe9a',
        );

        $this->mockHttpResponse(
            'GET',
            'https://example.com/api/rest/v1/catalogs/70313d30-8316-41c2-b298-8f9e7186fe9a/products/wrong_identifier_1234',
            [],
            new MockResponse('', ['http_code' => 404])
        );

        $client->request('GET', '/catalogs/70313d30-8316-41c2-b298-8f9e7186fe9a/products/wrong_identifier_1234');
        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * @test
     */
    public function itRedirectsToAuthorizationPageWhenAccessDenied(): void
    {
        $client = $this->initializeClientWithSession([
            'pim_url' => 'https://example.com',
            'akeneo_pim_access_token' => 'random_access_token',
        ]);

        $this->mockPimAPIResponse(
            'get-catalog-product-value-filters.json',
            'https://example.com/api/rest/v1/catalogs/70313d30-8316-41c2-b298-8f9e7186fe9a',
        );

        $this->mockHttpResponse(
            'GET',
            'https://example.com/api/rest/v1/catalogs/70313d30-8316-41c2-b298-8f9e7186fe9a/products/wrong_identifier_1234',
            [],
            new MockResponse('', ['http_code' => 401])
        );

        $this->mockHttpResponse(
            'POST',
            'https://example.com/api/oauth/v1/token',
            [],
            new MockResponse('', ['http_code' => 401])
        );

        $client->request('GET', '/catalogs/70313d30-8316-41c2-b298-8f9e7186fe9a/products/wrong_identifier_1234');

        $this->assertResponseRedirects('/authorization/activate');
    }

    /**
     * @test
     */
    public function itRedirectsToCatalogsPageWhenCatalogIsDisabledOnCatalogEndpoint(): void
    {
        $client = $this->initializeClientWithSession([
            'pim_url' => 'https://example.com',
            'akeneo_pim_access_token' => 'random_access_token',
        ]);

        $this->mockPimAPIResponse(
            'get-catalog-disabled.json',
            'https://example.com/api/rest/v1/catalogs/ad1f6e7a-e6d9-495f-b568-f4f473803679',
        );

        $client->request('GET', '/catalogs/ad1f6e7a-e6d9-495f-b568-f4f473803679/products/16467667-9a29-48c1-90b3-8a169b83e8e6');

        $this->assertResponseRedirects('/catalogs');
    }

    /**
     * @test
     */
    public function itRedirectsToCatalogsPageWhenCatalogIsDisabledOnProductEndpoint(): void
    {
        $client = $this->initializeClientWithSession([
            'pim_url' => 'https://example.com',
            'akeneo_pim_access_token' => 'random_access_token',
        ]);

        $this->mockPimAPIResponse(
            'get-catalog-product-value-filters.json',
            'https://example.com/api/rest/v1/catalogs/70313d30-8316-41c2-b298-8f9e7186fe9a',
        );
        $this->mockPimAPIResponse(
            'get-catalogs-disabled-catalog-error-message.json',
            'https://example.com/api/rest/v1/catalogs/70313d30-8316-41c2-b298-8f9e7186fe9a/products/disabled',
        );

        $client->request('GET', '/catalogs/70313d30-8316-41c2-b298-8f9e7186fe9a/products/disabled');

        $this->assertResponseRedirects('/catalogs');
    }

    /**
     * @test
     */
    public function itDisplaysAMappedProduct(): void
    {
        $client = $this->initializeClientWithSession([
            'pim_url' => 'https://example.com',
            'akeneo_pim_access_token' => 'random_access_token',
        ]);

        $this->mockPimAPIResponse(
            'get-catalog-attribute-mapping.json',
            'https://example.com/api/rest/v1/catalogs/8a8494d2-05cc-4b8f-942e-f5ea7591e89c',
        );
        $this->mockPimAPIResponse(
            'get-catalogs-mapped-product-scanner.json',
            'https://example.com/api/rest/v1/catalogs/8a8494d2-05cc-4b8f-942e-f5ea7591e89c/mapped-products/a5eed606-4f98-4d8c-b926-5b59f8fb0ee7',
        );

        $crawler = $client->request('GET', '/catalogs/8a8494d2-05cc-4b8f-942e-f5ea7591e89c/products/a5eed606-4f98-4d8c-b926-5b59f8fb0ee7');
        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains('.locale-switcher__language', '🇺🇸 English (United States)');
        $this->assertSelectorTextContains('h1.page-title', 'Kodak i2600 for Govt');

        $foundAttributes = $crawler->filter('.attribute');
        $this->assertEquals(15, $foundAttributes->count());

        $label0 = $foundAttributes->eq(0)->filter('.attribute__label')->text();
        $this->assertEquals('Product UUID', $label0);

        $label1 = $foundAttributes->eq(1)->filter('.attribute__label')->text();
        $this->assertEquals('SKU (Stock Keeping Unit)', $label1);

        $label2 = $foundAttributes->eq(2)->filter('.attribute__label')->text();
        $this->assertEquals('Product name', $label2);

        $label3 = $foundAttributes->eq(3)->filter('.attribute__label')->text();
        $this->assertEquals('Product type', $label3);

        $label4 = $foundAttributes->eq(4)->filter('.attribute__label')->text();
        $this->assertEquals('Description', $label4);

        $label5 = $foundAttributes->eq(5)->filter('.attribute__label')->text();
        $this->assertEquals('Main image', $label5);

        $label6 = $foundAttributes->eq(6)->filter('.attribute__label')->text();
        $this->assertEquals('Main color', $label6);

        $label7 = $foundAttributes->eq(7)->filter('.attribute__label')->text();
        $this->assertEquals('Colors', $label7);

        $label8 = $foundAttributes->eq(8)->filter('.attribute__label')->text();
        $this->assertEquals('Is available', $label8);

        $label9 = $foundAttributes->eq(9)->filter('.attribute__label')->text();
        $this->assertEquals('Price (€)', $label9);

        $label10 = $foundAttributes->eq(10)->filter('.attribute__label')->text();
        $this->assertEquals('Publication date', $label10);

        $label11 = $foundAttributes->eq(11)->filter('.attribute__label')->text();
        $this->assertEquals('Certification number', $label11);

        $label12 = $foundAttributes->eq(12)->filter('.attribute__label')->text();
        $this->assertEquals('Size (letter)', $label12);

        $label13 = $foundAttributes->eq(13)->filter('.attribute__label')->text();
        $this->assertEquals('Size', $label13);

        $label14 = $foundAttributes->eq(14)->filter('.attribute__label')->text();
        $this->assertEquals('Weight (grams)', $label14);
    }

    /**
     * @test
     */
    public function itReturnsNotFoundOnMappedProductBadUuid(): void
    {
        $client = $this->initializeClientWithSession([
            'pim_url' => 'https://example.com',
            'akeneo_pim_access_token' => 'random_access_token',
        ]);

        $this->mockPimAPIResponse(
            'get-catalog-attribute-mapping.json',
            'https://example.com/api/rest/v1/catalogs/8a8494d2-05cc-4b8f-942e-f5ea7591e89c',
        );

        $this->mockHttpResponse(
            'GET',
            'https://example.com/api/rest/v1/catalogs/8a8494d2-05cc-4b8f-942e-f5ea7591e89c/mapped-products/wrong_uuid_1234',
            [],
            new MockResponse('', ['http_code' => 422])
        );

        $client->request('GET', '/catalogs/8a8494d2-05cc-4b8f-942e-f5ea7591e89c/products/wrong_uuid_1234');
        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * @test
     */
    public function itRedirectsToAuthorizationPageWhenAccessDeniedOnMappedProduct(): void
    {
        $client = $this->initializeClientWithSession([
            'pim_url' => 'https://example.com',
            'akeneo_pim_access_token' => 'random_access_token',
        ]);

        $this->mockPimAPIResponse(
            'get-catalog-attribute-mapping.json',
            'https://example.com/api/rest/v1/catalogs/8a8494d2-05cc-4b8f-942e-f5ea7591e89c',
        );

        $this->mockHttpResponse(
            'GET',
            'https://example.com/api/rest/v1/catalogs/8a8494d2-05cc-4b8f-942e-f5ea7591e89c/mapped-products/wrong_identifier_1234',
            [],
            new MockResponse('', ['http_code' => 401])
        );

        $this->mockHttpResponse(
            'POST',
            'https://example.com/api/oauth/v1/token',
            [],
            new MockResponse('', ['http_code' => 401])
        );

        $client->request('GET', '/catalogs/8a8494d2-05cc-4b8f-942e-f5ea7591e89c/products/wrong_identifier_1234');

        $this->assertResponseRedirects('/authorization/activate');
    }

    /**
     * @test
     */
    public function itRedirectsToCatalogsPageWhenCatalogIsDisabledOnMappedProductEndpoint(): void
    {
        $client = $this->initializeClientWithSession([
            'pim_url' => 'https://example.com',
            'akeneo_pim_access_token' => 'random_access_token',
        ]);

        $this->mockPimAPIResponse(
            'get-catalog-attribute-mapping.json',
            'https://example.com/api/rest/v1/catalogs/8a8494d2-05cc-4b8f-942e-f5ea7591e89c',
        );
        $this->mockPimAPIResponse(
            'get-catalogs-disabled-catalog-error-message.json',
            'https://example.com/api/rest/v1/catalogs/8a8494d2-05cc-4b8f-942e-f5ea7591e89c/mapped-products/disabled',
        );

        $client->request('GET', '/catalogs/8a8494d2-05cc-4b8f-942e-f5ea7591e89c/products/disabled');

        $this->assertResponseRedirects('/catalogs');
    }
}
