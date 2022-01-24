<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment as TwigEnvironment;

class ListProductsAction
{
    public function __construct(
        private TwigEnvironment $twig,
    ) {
    }

    #[Route('/products', name: 'products', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        // TODO: call PIM API

        return new Response($this->twig->render('products.html.twig', [

        ]));
    }
}
