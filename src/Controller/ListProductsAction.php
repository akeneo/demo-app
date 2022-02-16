<?php

declare(strict_types=1);

namespace App\Controller;

use Akeneo\Pim\ApiClient\Exception\ClientErrorHttpException;
use App\Query\FetchProductsQuery;
use App\Query\GuessCurrentLocaleQuery;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment as TwigEnvironment;

final class ListProductsAction
{
    public function __construct(
        private TwigEnvironment $twig,
        private GuessCurrentLocaleQuery $guessCurrentLocaleQuery,
        private FetchProductsQuery $fetchProductsQuery,
    ) {
    }

    #[Route('/products', name: 'products', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        try {
            $locale = $this->guessCurrentLocaleQuery->guess();
            $products = $this->fetchProductsQuery->fetch($locale);
        } catch (ClientErrorHttpException $e) {
            throw new AccessDeniedHttpException('', $e);
        }

        $locale = $this->guessCurrentLocaleQuery->guess();

        return new Response(
            $this->twig->render('products.html.twig', [
                'locale' => $locale,
                'products' => $products,
            ])
        );
    }
}
