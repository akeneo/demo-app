<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WelcomeAction
{
    public function __construct(private \Twig\Environment $twig)
    {
    }

    #[Route('/', name: 'welcome')]
    public function __invoke(): Response
    {
        return new Response($this->twig->render('welcome.html.twig'));
    }
}
