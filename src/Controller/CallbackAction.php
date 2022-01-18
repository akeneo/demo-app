<?php

declare(strict_types=1);

namespace App\Controller;

use App\Storage\CookieStorage;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CallbackAction
{
    public function __construct(
        private string $akeneoClientId,
        private string $akeneoClientSecret,
        private HttpClientInterface $client,
        private CookieStorage $cookieStorage,
        private RouterInterface $router,
    ) {
    }

    #[Route('/callback', name: 'callback', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $pimUrl = $this->cookieStorage->get('pim_url');
        if (empty($pimUrl)) {
            throw new \LogicException('Could not retrieve PIM URL, please restart the authorization process');
        }

        $state = $request->query->get('state');
        if (empty($state) || $state !== $this->cookieStorage->get('state')) {
            throw new \LogicException('Invalid state');
        }

        $authorizationCode = $request->query->get('code');
        if (empty($authorizationCode)) {
            throw new \LogicException('Missing authorization code');
        }

        $codeIdentifier = \bin2hex(\random_bytes(30));
        $codeChallenge = \hash('sha256', $codeIdentifier.$this->akeneoClientSecret);

        $accessTokenRequestPayload = [
            'grant_type' => 'authorization_code',
            'code' => $authorizationCode,
            'client_id' => $this->akeneoClientId,
            'code_identifier' => $codeIdentifier,
            'code_challenge' => $codeChallenge,
        ];

        $accessTokenUrl = $pimUrl.'/connect/apps/v1/oauth2/token';

        $response = $this->client->request('POST', $accessTokenUrl, [
            'headers' => [
                'Content-type' => 'application/x-www-form-urlencoded',
            ],
            'body' => $accessTokenRequestPayload,
        ]);

        $content = $response->getContent();

        $accessToken = \json_decode($content, true)['access_token'];

        $this->cookieStorage->set('akeneo_pim_access_token', $accessToken);

        return new RedirectResponse($this->router->generate('products'));
    }
}
