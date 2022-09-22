<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\CatalogNotFoundException;
use App\PimApi\Model\Catalog;
use App\PimApi\PimCatalogApiClient;
use App\Query\FetchProductsQuery;
use App\Query\GuessCurrentLocaleQuery;
use App\Storage\CatalogIdStorageInterface;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment as TwigEnvironment;

final class ListProductsAction
{
    public function __construct(
        private readonly TwigEnvironment $twig,
        private readonly CatalogIdStorageInterface $catalogIdStorage,
        private readonly PimCatalogApiClient $catalogApiClient,
        private readonly GuessCurrentLocaleQuery $guessCurrentLocaleQuery,
        private readonly FetchProductsQuery $fetchProductsQuery,
    ) {
    }

    #[Route('/products', name: 'products', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $catalog = $this->getDefaultCatalog();
        $locale = $this->guessCurrentLocaleQuery->guess();

        if ($catalog->enabled) {
            $products = $this->fetchProductsQuery->fetch($locale, $catalog->id);
        } else {
            $products = [];
        }

        return new Response(
            $this->twig->render('products.html.twig', [
                'locale' => $locale,
                'products' => $products,
                'catalog' => $catalog,
            ])
        );
    }

    private function getDefaultCatalog(): Catalog
    {
        $catalogId = $this->catalogIdStorage->getCatalogId();
        if (null === $catalogId) {
            throw new CatalogNotFoundException();
        }

        try {
            $catalog = $this->catalogApiClient->getCatalog($catalogId);
        } catch (ClientException) {
            throw new CatalogNotFoundException();
        }

        return $catalog;
    }
}
