<?php

declare(strict_types=1);

namespace App\Controller;

use Akeneo\Pim\ApiClient\Exception\NotFoundHttpException as AkeneoNotFoundHttpException;
use App\Exception\CatalogNotFoundException;
use App\PimApi\Model\Catalog;
use App\PimApi\PimCatalogApiClient;
use App\Query\FetchProductQuery;
use App\Query\GuessCurrentLocaleQuery;
use App\Storage\CatalogIdStorageInterface;
use Symfony\Component\HttpClient\Exception\ClientException;
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
        private readonly CatalogIdStorageInterface $catalogIdStorage,
        private readonly PimCatalogApiClient $catalogApiClient,
    ) {
    }

    #[Route('/products/{identifier}', name: 'product', methods: ['GET'])]
    public function __invoke(Request $request, string $identifier): Response
    {
        try {
            $locale = $this->guessCurrentLocaleQuery->guess();
            $catalog = $this->getDefaultCatalog();
            $product = $this->fetchProductQuery->fetch($catalog->id, $identifier, $locale);
        } catch (AkeneoNotFoundHttpException $e) {
            throw new NotFoundHttpException('PIM API replied with a 404', $e);
        }

        return new Response(
            $this->twig->render('product.html.twig', [
                'locale' => $locale,
                'product' => $product,
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
