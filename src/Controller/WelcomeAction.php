<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment as TwigEnvironment;

class WelcomeAction
{
    public function __construct(
        private TwigEnvironment $twig,
        private RouterInterface $router,
    ) {
    }

    #[Route('/', name: 'welcome')]
    public function __invoke(Request $request): Response
    {
        $session = $request->getSession();

        $accessToken = $session->get('akeneo_pim_access_token');
        if (null !== $accessToken) {
            return new RedirectResponse($this->router->generate('products'));
        }

        $pimUrl = $request->query->get('pim_url');
        if (empty($pimUrl)) {
            throw new \LogicException('Missing PIM url in the query.');
        }
        if (false === \filter_var($pimUrl, FILTER_VALIDATE_URL)) {
            throw new \LogicException('PIM url is not valid.');
        }

        $session->set('pim_url', \rtrim((string) $pimUrl, '/'));

        return new Response($this->twig->render('welcome.html.twig'));
    }
}
