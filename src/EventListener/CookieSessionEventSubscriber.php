<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Session\CookieSessionHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CookieSessionEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CookieSessionHandler $cookieSessionHandler,
    ) {
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onRequest',
            // low priority to come right after session has been saved by
            /** @see \Symfony\Component\HttpKernel\EventListener\AbstractSessionListener */
            KernelEvents::RESPONSE => ['onResponse', -1001],
        ];
    }

    public function onRequest(RequestEvent $requestEvent): void
    {
        if (!$requestEvent->isMainRequest()) {
            return;
        }

        $session = $requestEvent->getRequest()->cookies->get(CookieSessionHandler::COOKIE_NAME);

        $this->cookieSessionHandler->initCookie($session);
    }

    public function onResponse(ResponseEvent $responseEvent): void
    {
        if (!$responseEvent->isMainRequest()) {
            return;
        }

        if (null !== $this->cookieSessionHandler->getCookie()) {
            $responseEvent->getResponse()->headers->setCookie($this->cookieSessionHandler->getCookie());
        }
    }
}
