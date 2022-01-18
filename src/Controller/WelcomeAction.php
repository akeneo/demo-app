<?php

declare(strict_types=1);

namespace App\Controller;

use App\Storage\CookieStorage;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

class WelcomeAction
{
    public function __construct(
        private \Twig\Environment $twig,
        private CookieStorage $cookieStorage,
        private RouterInterface $router,
    ) {
    }

    #[Route('/', name: 'welcome', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $accessToken = $this->cookieStorage->get('akeneo_pim_access_token');

        if (null !== $accessToken) {
            return new RedirectResponse($this->router->generate('products'));
        }

        $pimUrl = $request->query->get('pim_url');
        if (empty($pimUrl)) {
            throw new \LogicException('Missing PIM URL in the query');
        }

        $this->cookieStorage->set('pim_url', $pimUrl);

        return new Response($this->twig->render('welcome.html.twig'));
    }
}
