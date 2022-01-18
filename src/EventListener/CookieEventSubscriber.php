<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Storage\CookieStorage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CookieEventSubscriber implements EventSubscriberInterface
{
    private const COOKIE_NAME = 'demo_app_cookie';

    public function __construct(
        private CookieStorage $cookieStorage,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'initCookieStorage',
            KernelEvents::RESPONSE => 'hydrateCookieResponse',
        ];
    }

    public function initCookieStorage(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            // don't do anything if it's not the main request
            return;
        }

        $data = (string) $event->getRequest()->cookies->get(self::COOKIE_NAME, '[]');

        $this->cookieStorage->hydrateWith(\json_decode($data, true, 512, JSON_THROW_ON_ERROR));
    }

    public function hydrateCookieResponse(ResponseEvent $event): void
    {
        $data = \json_encode($this->cookieStorage->all(), JSON_THROW_ON_ERROR);

        $event->getResponse()->headers->setCookie(Cookie::create(self::COOKIE_NAME, $data));
    }
}
