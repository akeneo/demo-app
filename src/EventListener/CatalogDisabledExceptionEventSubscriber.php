<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Exception\CatalogDisabledException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

class CatalogDisabledExceptionEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
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
        if (!($exception instanceof CatalogDisabledException)) {
            return;
        }

        $event->setResponse(new RedirectResponse($this->buildRedirectionUrl($event)));
    }

    private function buildRedirectionUrl(ExceptionEvent $event): string
    {
        return $this->router->generate('products');
    }
}
