<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Tests\Integration\AbstractIntegrationTest;
use App\Tests\Integration\MockPimApiTrait;

class ListCatalogsActionTest extends AbstractIntegrationTest
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
    public function itDisplaysAvailableCatalogs(): void
    {
        $catalogConfigurationUrl = 'https://example.com/connect/apps/v1/catalogs/';

        $client = $this->initializeClientWithSession([
            'pim_url' => 'https://example.com',
            'akeneo_pim_access_token' => 'random_access_token',
            'akeneo_pim_catalog_id' => '70313d30-8316-41c2-b298-8f9e7186fe9a',
        ]);

        $client->request('GET', '/catalogs');
        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains('.catalogs__list', 'Catalog with product value filters');
        $this->assertSelectorTextContains('.catalogs__list', 'Catalog with attribute mapping');

        $catalogList = $client->getCrawler()->filter('.catalogs__list tbody tr');
        $configureLinkForProductValueFilters = $catalogList->first()->selectLink('Configure catalog')->attr('href');
        $configureLinkForAttributeMapping = $catalogList->last()->selectLink('Configure catalog')->attr('href');

        $this->assertEquals(
            $catalogConfigurationUrl.'70313d30-8316-41c2-b298-8f9e7186fe9a',
            $configureLinkForProductValueFilters
        );

        $this->assertEquals(
            $catalogConfigurationUrl.'8a8494d2-05cc-4b8f-942e-f5ea7591e89c',
            $configureLinkForAttributeMapping
        );
    }
}
