<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Exception\MissingPimApiAccessTokenException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

class MissingPimApiAccessTokenExceptionEventSubscriber implements EventSubscriberInterface
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
        if (!$exception instanceof MissingPimApiAccessTokenException) {
            return;
        }

        $this->logger->warning('An exception was thrown because the Access Token is missing', ['exception' => $exception]);

        $event->setResponse(new RedirectResponse($this->router->generate('welcome')));
    }
}
