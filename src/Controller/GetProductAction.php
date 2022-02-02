<?php

declare(strict_types=1);

namespace App\Controller;

use App\Query\Locale\GuessCurrentLocaleQuery;
use App\Query\Product\FetchProductQuery;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment as TwigEnvironment;

final class GetProductAction
{
    public function __construct(
        private TwigEnvironment $twig,
        private GuessCurrentLocaleQuery $guessCurrentLocaleQuery,
        private FetchProductQuery $fetchProductQuery,
    ) {
    }

    #[Route('/product/{identifier}', name: 'product', methods: ['GET'])]
    public function __invoke(Request $request, string $identifier): Response
    {
        $locale = $this->guessCurrentLocaleQuery->guess();

        return new Response(
            $this->twig->render('product.html.twig', [
                'locale' => $locale,
                'product' => $this->fetchProductQuery->fetch($identifier, $locale),
            ])
        );
    }
}
