<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Security\Decrypt;
use App\Session\CookieSessionHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CookieSessionEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CookieSessionHandler $cookieSessionHandler,
        private Decrypt $decrypt,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onRequest',
            // low priority to come right after session has been saved by
            /* @see \Symfony\Component\HttpKernel\EventListener\AbstractSessionListener */
            KernelEvents::RESPONSE => ['onResponse', -1001],
        ];
    }

    public function onRequest(RequestEvent $requestEvent): void
    {
        if (!$requestEvent->isMainRequest()) {
            return;
        }

        $session = $requestEvent->getRequest()->cookies->get(CookieSessionHandler::COOKIE_NAME);
        $session = null !== $session ? ($this->decrypt)((string) $session) : null;

        $this->cookieSessionHandler->initCookie($session);
    }

    public function onResponse(ResponseEvent $responseEvent): void
    {
        if (!$responseEvent->isMainRequest()) {
            return;
        }

        $cookie = $this->cookieSessionHandler->getCookie();
        if (null !== $cookie) {
            $responseEvent->getResponse()->headers->setCookie($cookie);
        }
    }
}
