<?php

declare(strict_types=1);

namespace App\Controller;

use App\Storage\CookieStorage;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ActivateAction
{
    private const OAUTH_SCOPES = [
        'read_products',
    ];

    public function __construct(
        private string $akeneoClientId,
        private CookieStorage $cookieStorage,
    ) {
    }

    #[Route('/authorization/activate', name: 'authorization_activate', methods: ['GET'])]
    public function __invoke(Request $request): RedirectResponse
    {
        $pimUrl = $this->cookieStorage->get('pim_url');

        if (empty($pimUrl)) {
            throw new \LogicException('Could not retrieve PIM URL, please restart the authorization process');
        }

        $state = \bin2hex(\random_bytes(10));

        $this->cookieStorage->set('state', $state);

        $authorizeUrlParams = \http_build_query([
            'response_type' => 'code',
            'client_id' => $this->akeneoClientId,
            'scope' => \implode(' ', self::OAUTH_SCOPES),
            'state' => $state,
        ]);

        $authorizeUrl = \rtrim($pimUrl, '/').'/connect/apps/v1/authorize?'.$authorizeUrlParams;

        return new RedirectResponse($authorizeUrl);
    }
}
