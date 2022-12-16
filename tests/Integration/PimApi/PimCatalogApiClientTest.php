<?php

declare(strict_types=1);

namespace App\Tests\Integration\PimApi;

use App\Exception\CatalogDisabledException;
use App\PimApi\Exception\PimApiException;
use App\PimApi\Model\Catalog;
use App\PimApi\PimCatalogApiClient;
use App\Tests\Integration\AbstractIntegrationTest;
use App\Tests\Integration\MockPimApiTrait;
use Symfony\Component\HttpClient\Response\MockResponse;

class PimCatalogApiClientTest extends AbstractIntegrationTest
{
    use MockPimApiTrait;

    private ?PimCatalogApiClient $pimCatalogApiClient;

    public function setUp(): void
    {
        parent::setUp();

        $this->setUpFakeAccessTokenStorage();
        $this->setUpFakePimUrlStorage();
        $this->mockDefaultPimAPIResponses();

        $this->pimCatalogApiClient = self::getContainer()->get(PimCatalogApiClient::class);
    }

    /**
     * @test
     */
    public function itRetrievesACatalog(): void
    {
        $catalogId = '8a8494d2-05cc-4b8f-942e-f5ea7591e89c';

        $this->mockHttpResponse(
            'GET',
            'https://example.com/api/rest/v1/catalogs/'.$catalogId,
            [],
            new MockResponse(\json_encode([
                'id' => $catalogId,
                'name' => 'Catalog with attribute mapping',
                'enabled' => true,
            ], JSON_THROW_ON_ERROR)),
        );

        $result = $this->pimCatalogApiClient->getCatalog($catalogId);

        $this->assertEquals(new Catalog(
            $catalogId,
            'Catalog with attribute mapping',
            true,
        ), $result);
    }

    /**
     * @test
     */
    public function itThrowsWhenAttemptToRetrieveAnUnknownCatalog(): void
    {
        $this->mockHttpResponse(
            'GET',
            'https://example.com/api/rest/v1/catalogs/unknown_id',
            [],
            new MockResponse('', ['http_code' => 404]),
        );

        $this->expectException(PimApiException::class);
        $this->pimCatalogApiClient->getCatalog('unknown_id');
    }

    /**
     * @test
     */
    public function itRetrievesAllCatalogs(): void
    {
        $this->mockPimAPIResponse(
            'get-catalogs.json',
            'https://example.com/api/rest/v1/catalogs',
        );

        $result = $this->pimCatalogApiClient->getCatalogs();

        $this->assertEquals([
            new Catalog(
                '70313d30-8316-41c2-b298-8f9e7186fe9a',
                'Catalog with product value filters',
                true,
            ),
            new Catalog(
                '8a8494d2-05cc-4b8f-942e-f5ea7591e89c',
                'Catalog with attribute mapping',
                false,
            ),
        ], $result);
    }

    /**
     * @test
     */
    public function itReturnsAnEmptyListWhenNoCatalogsAreReturned(): void
    {
        $this->mockPimAPIResponse(
            'get-catalogs-empty-list.json',
            'https://example.com/api/rest/v1/catalogs',
        );

        $result = $this->pimCatalogApiClient->getCatalogs();

        $this->assertEmpty($result);
    }

    /**
     * @test
     */
    public function itThrowsWhenAnErrorOccurOnCatalogsRetrieval(): void
    {
        $this->mockHttpResponse(
            'GET',
            'https://example.com/api/rest/v1/catalogs',
            [],
            new MockResponse('', ['http_code' => 400]),
        );

        $this->expectException(PimApiException::class);
        $this->pimCatalogApiClient->getCatalogs();
    }

    /**
     * @test
     */
    public function itCreatesACatalogWithAName(): void
    {
        $this->mockHttpResponse(
            'POST',
            'https://example.com/api/rest/v1/catalogs',
            [],
            new MockResponse(\json_encode([
                'id' => '7e018bfd-00e1-4642-951e-4d45684b51f4',
                'name' => 'Demo App catalog',
                'enabled' => false,
            ], JSON_THROW_ON_ERROR),
            ['http_code' => 201]
            )
        );

        $result = $this->pimCatalogApiClient->createCatalog('Demo App catalog');

        $this->assertEquals(new Catalog(
            '7e018bfd-00e1-4642-951e-4d45684b51f4',
            'Demo App catalog',
            false,
        ), $result);
    }

    /**
     * @test
     */
    public function itThrowsWhenItFailsToCreateACatalog(): void
    {
        $this->mockHttpResponse(
            'POST',
            'https://example.com/api/rest/v1/catalogs',
            [],
            new MockResponse('', ['http_code' => 400]),
        );

        $this->expectException(PimApiException::class);
        $this->pimCatalogApiClient->createCatalog('Demo App catalog');
    }

    /**
     * @test
     */
    public function itSetsProductMappingSchemaForACatalog(): void
    {
        $catalogId = '7e018bfd-00e1-4642-951e-4d45684b51f4';
        $this->mockHttpResponse(
            'PUT',
            "https://example.com/api/rest/v1/catalogs/$catalogId/mapping-schemas/product",
            [],
            new MockResponse('', ['http_code' => 204])
        );

        $this->pimCatalogApiClient->setProductMappingSchema($catalogId, 'mapping_json_content');

        // expect no exception to occur
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function itThrowsWhenItFailsToSetProductMappingSchema(): void
    {
        $catalogId = '7e018bfd-00e1-4642-951e-4d45684b51f4';
        $this->mockHttpResponse(
            'PUT',
            "https://example.com/api/rest/v1/catalogs/$catalogId/mapping-schemas/product",
            [],
            new MockResponse('', ['http_code' => 422]),
        );

        $this->expectException(PimApiException::class);
        $this->pimCatalogApiClient->setProductMappingSchema($catalogId, 'mapping_json_content');
    }

    /**
     * @test
     */
    public function itRetrievesMappedProducts(): void
    {
        $this->mockPimAPIResponse(
            'get-catalogs-mapped-products.json',
            'https://example.com/api/rest/v1/catalogs/8a8494d2-05cc-4b8f-942e-f5ea7591e89c/mapped-products?limit=10',
        );

        $result = $this->pimCatalogApiClient->getMappedProducts('8a8494d2-05cc-4b8f-942e-f5ea7591e89c');

        $this->assertEquals([
            [
                'uuid' => 'a5eed606-4f98-4d8c-b926-5b59f8fb0ee7',
                'title' => 'Kodak i2600 for Govt',
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
                'code' => '',
            ],
            [
                'uuid' => '16467667-9a29-48c1-90b3-8a169b83e8e6',
                'title' => 'Sunglasses',
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer aliquam',
                'code' => '1234567890316',
            ],
            [
                'uuid' => '5b8381e2-a97a-4120-87da-1ef8b9c53988',
                'title' => '',
                'description' => '',
                'code' => '',
            ],
        ], $result);
    }

    /**
     * @test
     */
    public function itReturnsAnEmptyListWhenNoMappedProductsAreReturned(): void
    {
        $this->mockPimAPIResponse(
            'get-catalogs-mapped-products-empty-list.json',
            'https://example.com/api/rest/v1/catalogs/70313d30-8316-41c2-b298-8f9e7186fe9a/mapped-products?limit=10',
        );

        $result = $this->pimCatalogApiClient->getMappedProducts('70313d30-8316-41c2-b298-8f9e7186fe9a');

        $this->assertEmpty($result);
    }

    /**
     * @test
     */
    public function itThrowsWhenAnErrorOccurOnMappedProductsRetrieval(): void
    {
        $this->mockHttpResponse(
            'GET',
            'https://example.com/api/rest/v1/catalogs/8a8494d2-05cc-4b8f-942e-f5ea7591e89c/mapped-products?limit=10',
            [],
            new MockResponse('', ['http_code' => 400]),
        );

        $this->expectException(PimApiException::class);
        $this->pimCatalogApiClient->getMappedProducts('8a8494d2-05cc-4b8f-942e-f5ea7591e89c');
    }

    /**
     * @test
     */
    public function itThrowsWhenCatalogDisablingErrorOccurOnMappedProductsRetrieval(): void
    {
        $this->mockPimAPIResponse(
            'get-catalogs-disabled-catalog-error-message.json',
            'https://example.com/api/rest/v1/catalogs/8a8494d2-05cc-4b8f-942e-f5ea7591e89c/mapped-products?limit=10',
        );

        $this->expectException(CatalogDisabledException::class);
        $this->pimCatalogApiClient->getMappedProducts('8a8494d2-05cc-4b8f-942e-f5ea7591e89c');
    }
}
