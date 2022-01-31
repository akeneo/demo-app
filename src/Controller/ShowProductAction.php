<?php

declare(strict_types=1);

namespace App\Controller;

use App\Storage\AccessTokenSessionStorage;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment as TwigEnvironment;

class ShowProductAction
{
    public function __construct(
        private TwigEnvironment $twig,
        private RouterInterface $router,
    ) {
    }

    #[Route('/products/{id}', name: 'product')]
    public function __invoke(Request $request): Response
    {
        return new Response($this->twig->render('product.html.twig'));
    }
}
