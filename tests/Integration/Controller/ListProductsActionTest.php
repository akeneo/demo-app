<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use Http\Message\RequestMatcher\RequestMatcher;

class ListProductsActionTest extends AbstractActionTest
{
    /**
     * @test
     */
    public function itDisplaysTenProductsAndTheCurrentLocale(): void
    {
        $client = self::createClientWithSession([
            'pim_url' => 'https://httpd',
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
        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains('.current-locale', '🇺🇸 English (United States)');
        $this->assertCount(10, $client->getCrawler()->filter('.product-card'));
    }

    // TODO: uncomment this test after the merge of CXP-1082 : https://github.com/akeneo/demo-app/pull/12
//    /**
//     * @test
//     */
//    public function itRendersALinkThatTargetThePimUrl(): void
//    {
//        $pimUrl = 'https://httpd';
//        $client = self::createClientWithSession([
//            'pim_url' => $pimUrl,
//            'akeneo_pim_access_token' => 'random_access_token_123456',
//        ]);
//
//        $crawler = $client->request('GET', '/products');
//
//        $this->assertEquals($pimUrl, $crawler->selectLink('Go to Akeneo PIM')->attr('href'));
//    }
}
