<?php

declare(strict_types=1);

namespace App\Controller;

use App\PimApi\Exception\PimApiException;
use App\PimApi\Model\Catalog;
use App\PimApi\Model\Product;
use App\PimApi\PimCatalogApiClient;
use App\Query\FetchMappedProductsQuery;
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
        private readonly FetchMappedProductsQuery $fetchMappedProductsQuery,
        private readonly string $akeneoClientId,
    ) {
    }

    #[Route('/catalogs/{catalogId}', name: 'catalog', methods: ['GET'])]
    public function __invoke(Request $request, string $catalogId): Response
    {
        $catalog = $this->getCatalogBy($catalogId);
        $products = $this->getProductsFrom($catalog);

        return new Response(
            $this->twig->render('catalog.html.twig', [
                'products' => $products,
                'catalog' => $catalog,
                'connected_app_id' => $this->akeneoClientId,
            ])
        );
    }

    private function getCatalogBy(string $catalogId): Catalog
    {
        try {
            $catalog = $this->catalogApiClient->getCatalog($catalogId);
        } catch (PimApiException) {
            throw new NotFoundHttpException();
        }

        return $catalog;
    }

    /**
     * @return array<Product>
     */
    private function getProductsFrom(Catalog $catalog): array
    {
        if (!$catalog->enabled) {
            return [];
        }

        if (Catalog::ATTRIBUTE_MAPPING_NAME === $catalog->name) {
            return $this->fetchMappedProductsQuery->fetch($catalog->id);
        }

        $locale = $this->guessCurrentLocaleQuery->guess();

        return $this->fetchProductsQuery->fetch($locale, $catalog->id);
    }
}
