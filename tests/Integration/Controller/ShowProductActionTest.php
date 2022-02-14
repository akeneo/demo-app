<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Tests\Integration\AbstractIntegrationTest;
use App\Tests\Integration\MockPimApiTrait;

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
}
