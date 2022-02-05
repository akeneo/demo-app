<?php

declare(strict_types=1);

namespace App\Controller;

use Akeneo\Pim\ApiClient\Exception\HttpException;
use App\Query\Locale\GuessCurrentLocaleQuery;
use App\Query\Product\FetchProductsQuery;
use App\Storage\AccessTokenStorageInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment as TwigEnvironment;

final class ListProductsAction
{
    public function __construct(
        private TwigEnvironment $twig,
        private GuessCurrentLocaleQuery $guessCurrentLocaleQuery,
        private FetchProductsQuery $fetchProductsQuery,
        private RouterInterface $router,
        private AccessTokenStorageInterface $accessTokenStorage,
    ) {
    }

    #[Route('/products', name: 'products', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        try {
            $locale = ($this->guessCurrentLocaleQuery)();
            $products = ($this->fetchProductsQuery)($locale);
        } catch (HttpException $e){
            $this->accessTokenStorage->clear();
            $session = $request->getSession();

            return new RedirectResponse($this->router->generate('welcome', [
                'pim_url' => $session->get('pim_url'),
            ]));
        }

        return new Response(
            $this->twig->render('products.html.twig', [
                'locale' => $locale,
                'products' => $products,
            ])
        );
    }
}
