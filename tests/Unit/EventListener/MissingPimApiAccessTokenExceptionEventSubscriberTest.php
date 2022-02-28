<?php

declare(strict_types=1);

namespace Unit\EventListener;

use App\EventListener\MissingPimApiAccessTokenExceptionEventSubscriber;
use App\Exception\MissingPimApiAccessTokenException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;

class MissingPimApiAccessTokenExceptionEventSubscriberTest extends TestCase
{
    private KernelInterface $kernel;
    private ?MissingPimApiAccessTokenExceptionEventSubscriber $subscriber;
    private RouterInterface|MockObject $router;

    protected function setUp(): void
    {
        $this->router = $this->getMockBuilder(RouterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->kernel = $this->getMockBuilder(KernelInterface::class)->getMock();

        $this->subscriber = new MissingPimApiAccessTokenExceptionEventSubscriber(
            $this->router,
        );
    }

    protected function tearDown(): void
    {
        $this->subscriber = null;
    }

    /**
     * @test
     */
    public function itIsSubscribedToKernelExceptionEvent(): void
    {
        $this->assertArrayHasKey(KernelEvents::EXCEPTION, MissingPimApiAccessTokenExceptionEventSubscriber::getSubscribedEvents());
    }

    /**
     * @test
     */
    public function itListensOnlyToMissingPimApiAccessTokenExceptions(): void
    {
        $event = new ExceptionEvent($this->kernel, new Request(), HttpKernelInterface::MAIN_REQUEST, new \Exception());

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($this->subscriber);
        $dispatcher->dispatch($event, KernelEvents::EXCEPTION);

        $eventResponse = $event->getResponse();
        $this->assertNotInstanceOf(RedirectResponse::class, $eventResponse);
    }

    /**
     * @test
     */
    public function itRedirectsToWelcomePage(): void
    {
        $this->router->method('generate')->willReturn('welcome_url');

        $event = new ExceptionEvent($this->kernel, new Request(), HttpKernelInterface::MAIN_REQUEST, new MissingPimApiAccessTokenException());

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($this->subscriber);
        $dispatcher->dispatch($event, KernelEvents::EXCEPTION);

        $eventResponse = $event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $eventResponse);
        $this->assertEquals('welcome_url', $eventResponse->headers->get('location'));
    }
}
