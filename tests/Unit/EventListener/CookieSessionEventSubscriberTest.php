<?php

namespace App\Tests\Unit\EventListener;

use App\EventListener\CookieSessionEventSubscriber;
use App\Session\CookieSessionHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;

class CookieSessionEventSubscriberTest extends TestCase
{
    private CookieSessionHandler|MockObject $cookieSessionHandler;
    private ?CookieSessionEventSubscriber $subscriber;
    private KernelInterface $kernel;

    protected function setUp(): void
    {
        $this->cookieSessionHandler = $this->getMockBuilder(CookieSessionHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->subscriber = new CookieSessionEventSubscriber($this->cookieSessionHandler);

        $this->kernel = $this->getMockBuilder(KernelInterface::class)->getMock();
    }

    protected function tearDown(): void
    {
        $this->subscriber = null;
    }

    /**
     * @test
     */
    public function itProvidesSubscribedEvents(): void
    {
        $this->assertArrayHasKey(KernelEvents::REQUEST, CookieSessionEventSubscriber::getSubscribedEvents());
        $this->assertArrayHasKey(KernelEvents::RESPONSE, CookieSessionEventSubscriber::getSubscribedEvents());
    }

    /**
     * @test
     */
    public function kernelEventResponseHasTheGoodPriority(): void
    {
        $kernelEventsResponse = CookieSessionEventSubscriber::getSubscribedEvents()[KernelEvents::RESPONSE];
        $this->assertEquals(['onResponse', -1001], $kernelEventsResponse);
    }

    /**
     * @test
     */
    public function itCallsInitCookieMethodWhenTheOnRequestEventIsDispatched(): void
    {
        $event = new RequestEvent($this->kernel, new Request(), HttpKernelInterface::MAIN_REQUEST);

        $this->cookieSessionHandler->expects($this->once())->method('initCookie');

        $this->dispatch($event, KernelEvents::REQUEST);
    }

    /**
     * @test
     */
    public function itDoesNotCallsInitCookieMethodWhenTheOnRequestEventIsDispatchedWithASubRequest(): void
    {
        $event = new RequestEvent($this->kernel, new Request(), HttpKernelInterface::SUB_REQUEST);

        $this->cookieSessionHandler->expects($this->never())->method('initCookie');

        $this->dispatch($event, KernelEvents::REQUEST);
    }

    /**
     * @test
     */
    public function itCallsGetCookieMethodWhenTheOnResponseEventIsDispatched(): void
    {
        $response = new Response();
        $event = new ResponseEvent($this->kernel, new Request(), HttpKernelInterface::MAIN_REQUEST, $response);

        $cookie = new Cookie('a_cookie');
        $this->cookieSessionHandler
            ->method('getCookie')
            ->willReturn($cookie);

        $this->cookieSessionHandler->expects($this->once())->method('getCookie');

        $this->dispatch($event, KernelEvents::RESPONSE);

        $this->assertContains($cookie, $response->headers->getCookies());
    }

    /**
     * @test
     */
    public function itDoesNotCallsGetCookieMethodWhenTheOnResponseEventIsDispatchedWithASubRequest(): void
    {
        $event = new ResponseEvent($this->kernel, new Request(), HttpKernelInterface::SUB_REQUEST, new Response());

        $this->cookieSessionHandler->expects($this->never())->method('getCookie');

        $this->dispatch($event, KernelEvents::RESPONSE);
    }

    private function dispatch(RequestEvent|ResponseEvent $event, string $eventName): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($this->subscriber);
        $dispatcher->dispatch($event, $eventName);
    }
}
