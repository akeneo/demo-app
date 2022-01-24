<?php

declare(strict_types=1);

namespace App\Controller;

use App\Storage\AccessTokenStorageInterface;
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
        private AccessTokenStorageInterface $accessTokenStorage,
        private RouterInterface $router,
    ) {
    }

    #[Route('/callback', name: 'callback', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $session = $request->getSession();

        $pimUrl = $session->get('pim_url');
        if (empty($pimUrl)) {
            throw new \LogicException('Could not retrieve PIM URL, please restart the authorization process.');
        }

        $state = $request->query->get('state');
        if (empty($state) || $state !== $session->get('state')) {
            throw new \LogicException('Invalid state');
        }

        $authorizationCode = $request->query->get('code');
        if (empty($authorizationCode)) {
            throw new \LogicException('Missing authorization code');
        }

        $accessToken = $this->fetchAccessToken($pimUrl, $authorizationCode);

        $this->accessTokenStorage->setAccessToken($accessToken);

        return new RedirectResponse($this->router->generate('products'));
    }

    private function fetchAccessToken(mixed $pimUrl, float|bool|int|string $authorizationCode): string
    {
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

        return \json_decode($content, true, 512, JSON_THROW_ON_ERROR)['access_token'];
    }
}
