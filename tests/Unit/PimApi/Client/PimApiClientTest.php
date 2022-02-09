<?php

namespace App\Tests\Unit\PimApi\Client;

use App\PimApi\PimApiClient;
use App\Storage\AccessTokenStorageInterface;
use App\Storage\PimURLStorageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;

class PimApiClientTest extends TestCase
{
    private AccessTokenStorageInterface|MockObject $accessTokenStorage;
    private ClientInterface|MockObject $httpClient;
    private PimURLStorageInterface|MockObject $pimURLStorage;
    private ?PimApiClient $pimApiClient;

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

        $this->pimApiClient = new PimApiClient(
            $this->accessTokenStorage,
            $this->httpClient,
            $this->pimURLStorage,
        );
    }

    protected function tearDown(): void
    {
        $this->pimApiClient = null;
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

        $this->pimApiClient->getToken();
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

        $this->pimApiClient->getToken();
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

        $this->assertEquals('TEST_ACCESS_TOKEN', $this->pimApiClient->getToken());
        $this->assertEquals('', $this->pimApiClient->getRefreshToken());
    }
}
