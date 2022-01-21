<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use Symfony\Component\HttpFoundation\Response;

class ActivateActionTest extends AbstractActionTest
{
    /**
     * @test
     */
    public function itThrowsAExceptionWhenThePimUrlIsMissingInSession(): void
    {
        $client = $this->getClientWithSession([]);
        $client->request('GET', '/authorization/activate');
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function itRedirectsToTheAuthorizeUrlWithQueryParameters(): void
    {
        $pimUrl = 'https://httpd/';
        $client = $this->getClientWithSession(['pim_url' => $pimUrl]);

        $client->request('GET', '/authorization/activate');

        $expectedAuthorizeUrlParams = \http_build_query([
            'response_type' => 'code',
            'client_id' => $client->getKernel()->getContainer()->getParameter('akeneoClientId'),
            'scope' => 'read_products',
            'state' => $client->getRequest()->getSession()->get('state'),
        ]);

        $expectedAuthorizeUrl = \rtrim($pimUrl, '/').'/connect/apps/v1/authorize?'.$expectedAuthorizeUrlParams;

        $this->assertResponseRedirects($expectedAuthorizeUrl, Response::HTTP_FOUND);
    }
}
