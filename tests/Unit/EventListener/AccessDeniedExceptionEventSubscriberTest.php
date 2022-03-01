<?php

declare(strict_types=1);

namespace Unit\EventListener;

use App\EventListener\AccessDeniedExceptionEventSubscriber;
use App\Storage\AccessTokenStorageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;

class AccessDeniedExceptionEventSubscriberTest extends TestCase
{
    private KernelInterface $kernel;
    private ?AccessDeniedExceptionEventSubscriber $subscriber;
    private AccessTokenStorageInterface|MockObject $accessTokenStorage;
    private RouterInterface|MockObject $router;
    private LoggerInterface|MockObject $logger;

    protected function setUp(): void
    {
        $this->accessTokenStorage = $this->getMockBuilder(AccessTokenStorageInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->router = $this->getMockBuilder(RouterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->kernel = $this->getMockBuilder(KernelInterface::class)->getMock();

        $this->subscriber = new AccessDeniedExceptionEventSubscriber(
            $this->accessTokenStorage,
            $this->router,
            $this->logger,
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
        $this->assertArrayHasKey(KernelEvents::EXCEPTION, AccessDeniedExceptionEventSubscriber::getSubscribedEvents());
    }

    /**
     * @test
     */
    public function itListensOnlyToAccessDeniedHttpExceptions(): void
    {
        $this->accessTokenStorage->expects($this->never())->method('clear');

        $event = new ExceptionEvent($this->kernel, new Request(), HttpKernelInterface::MAIN_REQUEST, new \Exception());

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($this->subscriber);
        $dispatcher->dispatch($event, KernelEvents::EXCEPTION);
    }

    /**
     * @test
     */
    public function itRemovesAccessTokenAndRedirectsToWelcomePage(): void
    {
        $this->accessTokenStorage->expects($this->once())->method('clear');
        $this->router->method('generate')->willReturn('welcome_url');

        $event = new ExceptionEvent($this->kernel, new Request(), HttpKernelInterface::MAIN_REQUEST, new AccessDeniedHttpException());

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($this->subscriber);
        $dispatcher->dispatch($event, KernelEvents::EXCEPTION);

        $eventResponse = $event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $eventResponse);
        $this->assertEquals('welcome_url', $eventResponse->headers->get('location'));
    }
}
