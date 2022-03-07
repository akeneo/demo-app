<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Exception\MissingPimUrlException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

class MissingPimUrlExceptionEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RouterInterface $router,
        private LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onException',
        ];
    }

    public function onException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if (!$exception instanceof MissingPimUrlException) {
            return;
        }

        $this->logger->warning('An exception was thrown because the PIM URL is missing', ['exception' => $exception]);

        $event->setResponse(new RedirectResponse($this->router->generate('welcome')));
    }
}
