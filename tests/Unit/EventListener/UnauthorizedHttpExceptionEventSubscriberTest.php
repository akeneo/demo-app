<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use Akeneo\Pim\ApiClient\Exception\UnauthorizedHttpException;
use App\EventListener\UnauthorizedHttpExceptionEventSubscriber;
use App\Storage\AccessTokenStorageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;

class UnauthorizedHttpExceptionEventSubscriberTest extends TestCase
{
    private KernelInterface $kernel;
    private ?UnauthorizedHttpExceptionEventSubscriber $subscriber;
    private AccessTokenStorageInterface|MockObject $accessTokenStorage;
    private RouterInterface|MockObject $router;
    private LoggerInterface|MockObject $logger;
    private RequestInterface|MockObject $request;
    private ResponseInterface|MockObject $response;

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

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->response = $this->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->kernel = $this->getMockBuilder(KernelInterface::class)->getMock();

        $this->subscriber = new UnauthorizedHttpExceptionEventSubscriber(
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
        $this->assertArrayHasKey(KernelEvents::EXCEPTION, UnauthorizedHttpExceptionEventSubscriber::getSubscribedEvents());
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

        $event = new ExceptionEvent(
            $this->kernel,
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            new UnauthorizedHttpException('message', $this->request, $this->response)
        );

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($this->subscriber);
        $dispatcher->dispatch($event, KernelEvents::EXCEPTION);

        $eventResponse = $event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $eventResponse);
        $this->assertEquals('welcome_url', $eventResponse->headers->get('location'));
    }
}
