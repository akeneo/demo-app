<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Tests\Integration\AbstractIntegrationTest;
use Symfony\Component\HttpFoundation\Response;

class ActivateActionTest extends AbstractIntegrationTest
{
    /**
     * @test
     */
    public function itThrowsAnExceptionWhenThePimUrlIsMissingInSession(): void
    {
        $client = $this->initializeClientWithSession([]);
        $client->request('GET', '/authorization/activate');
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function itRedirectsToTheAuthorizeUrlWithQueryParameters(): void
    {
        $pimUrl = 'https://httpd';
        $client = $this->initializeClientWithSession(['pim_url' => $pimUrl]);

        $client->request('GET', '/authorization/activate');

        $expectedAuthorizeUrlParams = \http_build_query([
            'response_type' => 'code',
            'client_id' => $client->getKernel()->getContainer()->getParameter('akeneoClientId'),
            'scope' => 'read_products read_catalog_structure read_channel_localization read_attribute_options openid profile',
            'state' => $client->getRequest()->getSession()->get('state'),
        ]);

        $expectedAuthorizeUrl = $pimUrl.'/connect/apps/v1/authorize?'.$expectedAuthorizeUrlParams;

        $this->assertResponseRedirects($expectedAuthorizeUrl, Response::HTTP_FOUND);
    }
}
