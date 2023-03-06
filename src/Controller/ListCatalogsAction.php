<?php

declare(strict_types=1);

namespace App\Controller;

use App\PimApi\PimCatalogApiClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment as TwigEnvironment;

final class ListCatalogsAction
{
    public function __construct(
        private readonly TwigEnvironment $twig,
        private readonly PimCatalogApiClient $catalogApiClient,
        private readonly string $akeneoClientId,
    ) {
    }

    #[Route('/catalogs', name: 'catalogs', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        return new Response(
            $this->twig->render('catalogs.html.twig', [
                'catalogs' => $this->catalogApiClient->getCatalogs(),
                'connected_app_id' => $this->akeneoClientId,
            ])
        );
    }
}
