<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Storage\AccessTokenStorageInterface;
use Psr\Log\LoggerInterface;
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
        if (!$exception instanceof AccessDeniedHttpException) {
            return;
        }

        $this->logger->warning('An access denied error was detected, destroy the session.');

        $this->accessTokenStorage->clear();

        $event->setResponse(new RedirectResponse($this->buildRedirectionUrl($event)));
    }

    private function buildRedirectionUrl(ExceptionEvent $event): string
    {
        $request = $event->getRequest();

        if ($request->hasSession() && !empty($request->getSession()->get('pim_url'))) {
            return $this->router->generate('authorization_activate');
        }

        return $this->router->generate('welcome');
    }
}
