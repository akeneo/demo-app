<?php

declare(strict_types=1);

namespace App\Controller;

use App\PimApi\Exception\PimApiException;
use App\PimApi\PimCatalogApiClient;
use App\Query\FetchProductsQuery;
use App\Query\GuessCurrentLocaleQuery;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment as TwigEnvironment;

final class ShowCatalogAction
{
    public function __construct(
        private readonly TwigEnvironment $twig,
        private readonly PimCatalogApiClient $catalogApiClient,
        private readonly GuessCurrentLocaleQuery $guessCurrentLocaleQuery,
        private readonly FetchProductsQuery $fetchProductsQuery,
    ) {
    }

    #[Route('/catalogs/{catalogId}', name: 'catalog', methods: ['GET'])]
    public function __invoke(Request $request, string $catalogId): Response
    {
        try {
            $catalog = $this->catalogApiClient->getCatalog($catalogId);
        } catch (PimApiException) {
            throw new NotFoundHttpException();
        }

        $locale = $this->guessCurrentLocaleQuery->guess();

        $products = $catalog->enabled ? $this->fetchProductsQuery->fetch($locale, $catalog->id) : [];

        return new Response(
            $this->twig->render('products.html.twig', [
                'locale' => $locale,
                'products' => $products,
                'catalog' => $catalog,
            ])
        );
    }
}
