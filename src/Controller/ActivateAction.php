<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ActivateAction
{
    private const OAUTH_SCOPES = [
        'read_products',
        'read_channel_localization',
    ];

    public function __construct(
        private string $akeneoClientId,
    ) {
    }

    #[Route('/authorization/activate', name: 'authorization_activate', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $session = $request->getSession();

        $pimUrl = $session->get('pim_url');
        if (empty($pimUrl)) {
            throw new \LogicException('Can\'t retrieve PIM url, please restart the authorization process.');
        }

        $state = \bin2hex(\random_bytes(10));
        $session->set('state', $state);

        $authorizeUrlParams = \http_build_query([
            'response_type' => 'code',
            'client_id' => $this->akeneoClientId,
            'scope' => \implode(' ', self::OAUTH_SCOPES),
            'state' => $state,
        ]);

        $authorizeUrl = $pimUrl.'/connect/apps/v1/authorize?'.$authorizeUrlParams;

        return new RedirectResponse($authorizeUrl);
    }
}
