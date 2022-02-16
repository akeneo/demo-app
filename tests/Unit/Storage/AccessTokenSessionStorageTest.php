<?php

namespace App\Tests\Unit\Storage;

use App\Storage\AccessTokenSessionStorage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AccessTokenSessionStorageTest extends TestCase
{
    private SessionInterface|MockObject $session;
    private RequestStack|MockObject $requestStack;
    private ?AccessTokenSessionStorage $sessionStorage;

    protected function setUp(): void
    {
        $this->session = $this->getMockBuilder(SessionInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestStack = $this->getMockBuilder(RequestStack::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestStack->method('getSession')->willReturn($this->session);

        $this->sessionStorage = new AccessTokenSessionStorage($this->requestStack);
    }

    protected function tearDown(): void
    {
        $this->sessionStorage = null;
    }

    /**
     * @test
     */
    public function itGetsTheAccessTokenFromTheSession(): void
    {
        $this->session
            ->expects($this->once())
            ->method('get')
            ->with('akeneo_pim_access_token');

        $this->sessionStorage->getAccessToken();
    }

    /**
     * @test
     */
    public function itSetsTheAccessTokenIntoTheSession(): void
    {
        $this->session
            ->expects($this->once())
            ->method('set')
            ->with('akeneo_pim_access_token', 'MY_ACCESS_TOKEN');

        $this->sessionStorage->setAccessToken('MY_ACCESS_TOKEN');
    }

    /**
     * @test
     */
    public function itClearsTheAccessTokenFromTheSession(): void
    {
        $this->session
            ->expects($this->once())
            ->method('remove')
            ->with('akeneo_pim_access_token');

        $this->sessionStorage->clear();
    }
}
