<?php

declare(strict_types=1);

namespace App\Controller;

use App\Storage\AccessTokenSessionStorage;
use App\Validator\ReachableUrl;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Twig\Environment as TwigEnvironment;

final class WelcomeAction
{
    public function __construct(
        private TwigEnvironment $twig,
        private RouterInterface $router,
        private AccessTokenSessionStorage $accessTokenSessionStorage,
        private ValidatorInterface $validator,
    ) {
    }

    #[Route('/', name: 'welcome')]
    public function __invoke(Request $request): Response
    {
        $session = $request->getSession();

        $pimUrl = $request->query->get('pim_url');
        if (empty($pimUrl)) {
            return new Response($this->twig->render('welcome.html.twig'));
        }

        $violations = $this->validator->validate($pimUrl, new ReachableUrl());
        if ($violations->count() > 0) {
            throw new BadRequestHttpException('PIM url is not valid.');
        }

        $session->set('pim_url', \rtrim((string) $pimUrl, '/'));

        $accessToken = $this->accessTokenSessionStorage->getAccessToken();
        if (null !== $accessToken) {
            return new RedirectResponse($this->router->generate('products'));
        }

        return new Response($this->twig->render('welcome.html.twig'));
    }
}
