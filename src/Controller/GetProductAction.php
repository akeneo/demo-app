<?php

declare(strict_types=1);

namespace App\Controller;

use Akeneo\Pim\ApiClient\Exception\ClientErrorHttpException;
use Akeneo\Pim\ApiClient\Exception\NotFoundHttpException as AkeneoNotFoundHttpException;
use App\Query\FetchProductQuery;
use App\Query\GuessCurrentLocaleQuery;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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

    #[Route('/products/{identifier}', name: 'product', methods: ['GET'])]
    public function __invoke(Request $request, string $identifier): Response
    {
        try {
            $locale = $this->guessCurrentLocaleQuery->guess();
            $product = $this->fetchProductQuery->fetch($identifier, $locale);
        } catch (AkeneoNotFoundHttpException $e) {
            throw new NotFoundHttpException('', $e);
        } catch (ClientErrorHttpException $e) {
            throw new AccessDeniedHttpException('', $e);
        }

        return new Response(
            $this->twig->render('product.html.twig', [
                'locale' => $locale,
                'product' => $product,
            ])
        );
    }
}
