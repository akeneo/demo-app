<?php

declare(strict_types=1);

namespace App\Controller;

use Akeneo\Pim\ApiClient\Exception\NotFoundHttpException as AkeneoNotFoundHttpException;
use App\Exception\CatalogDisabledException;
use App\Exception\CatalogProductNotFoundException;
use App\PimApi\Exception\PimApiException;
use App\PimApi\Model\Catalog;
use App\PimApi\PimCatalogApiClient;
use App\Query\FetchMappedProductQuery;
use App\Query\FetchProductQuery;
use App\Query\GuessCurrentLocaleQuery;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment as TwigEnvironment;

final class ShowProductAction
{
    public function __construct(
        private TwigEnvironment $twig,
        private GuessCurrentLocaleQuery $guessCurrentLocaleQuery,
        private FetchProductQuery $fetchProductQuery,
        private FetchMappedProductQuery $fetchMappedProductQuery,
        private readonly PimCatalogApiClient $catalogApiClient,
    ) {
    }

    #[Route('/catalogs/{catalogId}/products/{uuid}', name: 'product', methods: ['GET'])]
    public function __invoke(Request $request, string $catalogId, string $uuid): Response
    {
        try {
            $locale = $this->guessCurrentLocaleQuery->guess();

            try {
                $catalog = $this->catalogApiClient->getCatalog($catalogId);
            } catch (PimApiException) {
                throw new NotFoundHttpException();
            }

            if ($catalog->enabled) {
                if (Catalog::PRODUCT_VALUE_FILTERS_NAME === $catalog->name) {
                    $product = $this->fetchProductQuery->fetch($catalog->id, $uuid, $locale);
                } else {
                    $product = $this->fetchMappedProductQuery->fetch($catalog->id, $uuid);
                }
            } else {
                throw new CatalogDisabledException();
            }
        } catch (AkeneoNotFoundHttpException|CatalogProductNotFoundException) {
            throw new NotFoundHttpException();
        }

        return new Response(
            $this->twig->render('product.html.twig', [
                'locale' => $locale,
                'product' => $product,
                'catalog' => $catalog,
            ])
        );
    }
}
