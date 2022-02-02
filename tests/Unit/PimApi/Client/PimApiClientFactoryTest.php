<?php

namespace App\Tests\Unit\PimApi\Client;

use App\PimApi\Client\PimApiClientFactory;
use App\Storage\AccessTokenStorageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PimApiClientFactoryTest extends TestCase
{
    private SessionInterface|MockObject $session;
    private RequestStack|MockObject $requestStack;
    private AccessTokenStorageInterface|MockObject $accessTokenStorage;
    private ?PimApiClientFactory $pimApiClientFactory;

    protected function setUp(): void
    {
        $this->session = $this->getMockBuilder(SessionInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestStack = $this->getMockBuilder(RequestStack::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestStack->method('getSession')->willReturn($this->session);

        $this->accessTokenStorage = $this->getMockBuilder(AccessTokenStorageInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->pimApiClientFactory = new PimApiClientFactory(
            $this->requestStack,
            $this->accessTokenStorage,
            'TEST_CLIENT_ID',
            'TEST_CLIENT_SECRET',
        );
    }

    protected function tearDown(): void
    {
        $this->pimApiClientFactory = null;
    }

    /**
     * @test
     */
    public function itThrowsALogicExceptionWhenPimUrlCanNotBeRetrieved(): void
    {
        $this->session
            ->method('get')
            ->with('pim_url')
            ->willReturn(null);

        $this->expectExceptionObject(new \LogicException('Could not retrieve PIM url, please restart the authorization process.'));

        ($this->pimApiClientFactory)();
    }

    /**
     * @test
     */
    public function itThrowsAnAccessDeniedExceptionIfNoAccessTokenFound(): void
    {
        $this->session
            ->method('get')
            ->with('pim_url')
            ->willReturn('https://example.com');

        $this->accessTokenStorage
            ->method('getAccessToken')
            ->willReturn(null);

        $this->expectExceptionObject(new AccessDeniedHttpException('Missing Pim API access token.'));

        ($this->pimApiClientFactory)();
    }

    /**
     * @test
     */
    public function itBuildsThePimApiClientWithAccessTokenFromStorage(): void
    {
        $this->session
            ->method('get')
            ->with('pim_url')
            ->willReturn('https://example.com');

        $this->accessTokenStorage
            ->method('getAccessToken')
            ->willReturn('TEST_ACCESS_TOKEN');

        $pimApiClient = ($this->pimApiClientFactory)();

        $this->assertEquals('TEST_ACCESS_TOKEN', $pimApiClient->getToken());
        $this->assertEquals('', $pimApiClient->getRefreshToken());
    }
}
