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
//    public function itDisplaysAProduct(): void
//    {
//        $client = $this->initializeClientWithSession([
//            'pim_url' => 'https://example.com',
//            'akeneo_pim_access_token' => 'random_access_token',
//        ]);
//
//        $crawler = $client->request('GET', '/catalogs/70313d30-8316-41c2-b298-8f9e7186fe9a/products/16467667-9a29-48c1-90b3-8a169b83e8e6');
//        $this->assertResponseIsSuccessful();
//
//        $this->assertSelectorTextContains('.locale-switcher__language', '🇺🇸 English (United States)');
//        $this->assertSelectorTextContains('h1.page-title', 'Sunglasses');
//
//        $foundAttributes = $crawler->filter('.attribute');
//        $this->assertEquals(3, $foundAttributes->count());
//
//        $eanLabel = $foundAttributes->eq(0)->filter('.attribute__label')->text();
//        $this->assertEquals('EAN', $eanLabel);
//
//        $eanLabel = $foundAttributes->eq(1)->filter('.attribute__label')->text();
//        $this->assertEquals('Name', $eanLabel);
//
//        $eanLabel = $foundAttributes->eq(2)->filter('.attribute__label')->text();
//        $this->assertEquals('Description', $eanLabel);
//    }

    /**
     * @test
     */
//    public function itReturnsNotFound(): void
//    {
//        $client = $this->initializeClientWithSession([
//            'pim_url' => 'https://example.com',
//            'akeneo_pim_access_token' => 'random_access_token',
//        ]);
//
//        $this->mockHttpResponse(
//            'GET',
//            'https://example.com/api/rest/v1/catalogs/70313d30-8316-41c2-b298-8f9e7186fe9a/products/wrong_identifier_1234',
//            [],
//            new MockResponse('', ['http_code' => 404])
//        );
//
//        $client->request('GET', '/catalogs/70313d30-8316-41c2-b298-8f9e7186fe9a/products/wrong_identifier_1234');
//        $this->assertResponseStatusCodeSame(404);
//    }

    /**
     * @test
     */
    public function itRedirectsToAuthorizationPageWhenAccessDenied(): void
    {
        $client = $this->initializeClientWithSession([
            'pim_url' => 'https://example.com',
            'akeneo_pim_access_token' => 'random_access_token',
        ]);

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

//        $this->assertResponseRedirects('/authorization/activate');
//        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * @test
     */
//    public function itRedirectsToCatalogsPageWhenCatalogIsDisabled(): void
//    {
//        $client = $this->initializeClientWithSession([
//            'pim_url' => 'https://example.com',
//            'akeneo_pim_access_token' => 'random_access_token',
//        ]);
//
//        $client->request('GET', '/catalogs/70313d30-8316-41c2-b298-8f9e7186fe9a/products/16467667-9a29-48c1-90b3-8a169b83e8e6');
//
//        $this->assertResponseRedirects('/catalogs/70313d30-8316-41c2-b298-8f9e7186fe9a');
//    }


    /**
     * @test
     */
//    public function itDisplaysAMappedProduct(): void
//    {
//        $client = $this->initializeClientWithSession([
//            'pim_url' => 'https://example.com',
//            'akeneo_pim_access_token' => 'random_access_token',
//        ]);
//
//        $crawler = $client->request('GET', '/catalogs/8a8494d2-05cc-4b8f-942e-f5ea7591e89c/products/a5eed606-4f98-4d8c-b926-5b59f8fb0ee7');
//        $this->assertResponseIsSuccessful();
//
//        $this->assertSelectorTextContains('.locale-switcher__language', '🇺🇸 English (United States)');
//        $this->assertSelectorTextContains('h1.page-title', 'Kodak i2600 for Govt');
//
//        $foundAttributes = $crawler->filter('.attribute');
//        $this->assertEquals(4, $foundAttributes->count());
//
//        $eanLabel = $foundAttributes->eq(0)->filter('.attribute__label')->text();
//        $this->assertEquals('Product UUID', $eanLabel);
//
//        $eanLabel = $foundAttributes->eq(1)->filter('.attribute__label')->text();
//        $this->assertEquals('Title', $eanLabel);
//
//        $eanLabel = $foundAttributes->eq(2)->filter('.attribute__label')->text();
//        $this->assertEquals('Description', $eanLabel);
//
//        $eanLabel = $foundAttributes->eq(3)->filter('.attribute__label')->text();
//        $this->assertEquals('Code', $eanLabel);
//    }


    /**
     * @test
     */
//    public function itReturnsNotFoundOnMappedProductBadUuid(): void
//    {
//        $client = $this->initializeClientWithSession([
//            'pim_url' => 'https://example.com',
//            'akeneo_pim_access_token' => 'random_access_token',
//        ]);
//
//        $this->mockHttpResponse(
//            'GET',
//            'https://example.com/api/rest/v1/catalogs/8a8494d2-05cc-4b8f-942e-f5ea7591e89c/mapped-products/wrong_uuid_1234',
//            [],
//            new MockResponse('', ['http_code' => 422])
//        );
//
//        $client->request('GET', '/catalogs/8a8494d2-05cc-4b8f-942e-f5ea7591e89c/products/wrong_uuid_1234');
//        $this->assertResponseStatusCodeSame(404);
//    }
}
