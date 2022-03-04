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

        $crawler = $client->request('GET', '/products/1111111304');
        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains('.locale-switcher__language', '🇺🇸 English (United States)');
        $this->assertSelectorTextContains('h1.product__title', 'Sunglasses');

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

        $this->mockHttpResponse(
            'GET',
            'https://example.com/api/rest/v1/products/wrong_identifier_1234',
            [],
            new MockResponse('', ['http_code' => 404])
        );

        $client->request('GET', '/products/wrong_identifier_1234');
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

        $this->mockHttpResponse(
            'GET',
            'https://example.com/api/rest/v1/products/wrong_identifier_1234',
            [],
            new MockResponse('', ['http_code' => 401])
        );

        $this->mockHttpResponse(
            'POST',
            'https://example.com/api/oauth/v1/token',
            [],
            new MockResponse('', ['http_code' => 400])
        );

        $client->request('GET', '/products/wrong_identifier_1234');

        $this->assertResponseRedirects('/authorization/activate');
    }
}
