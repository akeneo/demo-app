<?php

declare(strict_types=1);

namespace App\Controller;

use App\Storage\CookieStorage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ListProductsAction
{
    public function __construct(
        private \Twig\Environment $twig,
        private CookieStorage $cookieStorage,
    ) {
    }

    #[Route('/products', name: 'products', methods: ['GET'])]
    public function __invoke(): Response
    {
        $this->cookieStorage->get('akeneo_pim_access_token');

        return new Response($this->twig->render('products.html.twig'));
    }
}
