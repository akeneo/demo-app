<?php

namespace App\Tests\Unit\PimApi\Client;

use App\PimApi\PimApiClientFactory;
use App\Storage\AccessTokenStorageInterface;
use App\Storage\PimURLStorageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;

class PimApiClientFactoryTest extends TestCase
{
    private AccessTokenStorageInterface|MockObject $accessTokenStorage;
    private ClientInterface|MockObject $httpClient;
    private PimURLStorageInterface|MockObject $pimURLStorage;
    private ?PimApiClientFactory $pimApiClientFactory;

    protected function setUp(): void
    {
        $this->accessTokenStorage = $this->getMockBuilder(AccessTokenStorageInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->httpClient = $this->getMockBuilder(ClientInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->pimURLStorage = $this->getMockBuilder(PimURLStorageInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->pimApiClientFactory = new PimApiClientFactory(
            $this->accessTokenStorage,
            $this->httpClient,
            $this->pimURLStorage,
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
        $this->pimURLStorage
            ->method('getPimURL')
            ->willReturn(null);

        $this->expectExceptionObject(
            new \LogicException('Could not retrieve PIM URL, please restart the authorization process.')
        );

        ($this->pimApiClientFactory)();
    }

    /**
     * @test
     */
    public function itThrowsAnAccessDeniedExceptionIfNoAccessTokenFound(): void
    {
        $this->pimURLStorage
            ->method('getPimURL')
            ->willReturn('https://example.com');

        $this->accessTokenStorage
            ->method('getAccessToken')
            ->willReturn(null);

        $this->expectExceptionObject(new \LogicException('Missing Pim API access token.'));

        ($this->pimApiClientFactory)();
    }

    /**
     * @test
     */
    public function itBuildsThePimApiClientWithAccessTokenFromStorage(): void
    {
        $this->pimURLStorage
            ->method('getPimURL')
            ->willReturn('https://example.com');

        $this->accessTokenStorage
            ->method('getAccessToken')
            ->willReturn('TEST_ACCESS_TOKEN');

        $pimApiClient = ($this->pimApiClientFactory)();

        $this->assertEquals('TEST_ACCESS_TOKEN', $pimApiClient->getToken());
        $this->assertEquals('', $pimApiClient->getRefreshToken());
    }
}
