<?php

declare(strict_types=1);

namespace App\Tests\Unit\Storage;

use App\Storage\UserProfileSessionStorage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class UserProfileSessionStorageTest extends TestCase
{
    private SessionInterface|MockObject $session;
    private ?UserProfileSessionStorage $sessionStorage;

    protected function setUp(): void
    {
        $this->session = $this->getMockBuilder(SessionInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestStack = $this->getMockBuilder(RequestStack::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestStack->method('getSession')->willReturn($this->session);

        $this->sessionStorage = new UserProfileSessionStorage($requestStack);
    }

    protected function tearDown(): void
    {
        $this->sessionStorage = null;
    }

    /**
     * @test
     */
    public function itGetsUserProfileFromTheSession(): void
    {
        $this->session
            ->expects($this->once())
            ->method('get')
            ->with('akeneo_pim_user_profile')
            ->willReturn('Bob');

        $userProfile = $this->sessionStorage->getUserProfile();

        self::assertEquals('Bob', $userProfile);
    }

    /**
     * @test
     */
    public function itGetsNullFromTheSession(): void
    {
        $this->session
            ->expects($this->once())
            ->method('get')
            ->with('akeneo_pim_user_profile')
            ->willReturn(null);

        $userProfile = $this->sessionStorage->getUserProfile();

        self::assertNull($userProfile);
    }

    /**
     * @test
     */
    public function itSetsUserProfileIntoTheSession(): void
    {
        $this->session
            ->expects($this->once())
            ->method('set')
            ->with('akeneo_pim_user_profile', 'NEW USER PROFILE');

        $this->sessionStorage->setUserProfile('NEW USER PROFILE');
    }
}
