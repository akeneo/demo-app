<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Storage\AccessTokenStorageInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

class AccessDeniedExceptionEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AccessTokenStorageInterface $accessTokenStorage,
        private RouterInterface $router,
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
        if (!$exception instanceof AccessDeniedHttpException) {
            return;
        }

        $this->accessTokenStorage->clear();

        $event->setResponse(new RedirectResponse($this->router->generate('welcome')));
    }
}
