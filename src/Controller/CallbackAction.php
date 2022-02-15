<?php

declare(strict_types=1);

namespace App\Controller;

use App\Storage\AccessTokenStorageInterface;
use App\Storage\UserProfileStorageInterface;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class CallbackAction
{
    public function __construct(
        private string $akeneoClientId,
        private string $akeneoClientSecret,
        private HttpClientInterface $client,
        private AccessTokenStorageInterface $accessTokenStorage,
        private UserProfileStorageInterface $userProfileStorage,
        private RouterInterface $router,
    ) {
    }

    #[Route('/callback', name: 'callback', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $session = $request->getSession();

        $pimUrl = $session->get('pim_url');
        if (empty($pimUrl)) {
            throw new \LogicException('Can\'t retrieve PIM url, please restart the authorization process.');
        }

        $state = $request->query->get('state');
        if (empty($state) || $state !== $session->get('state')) {
            throw new \LogicException('Invalid state');
        }

        $authorizationCode = $request->query->get('code');
        if (empty($authorizationCode)) {
            throw new \LogicException('Missing authorization code');
        }

        ['access_token' => $accessToken, 'user_data' => $userData ] = $this->fetchAccessTokenPayload($pimUrl, $authorizationCode);

        $this->accessTokenStorage->setAccessToken($accessToken);

        if (!empty($userData)) {
            $this->userProfileStorage->setUserProfile($userData['firstname'].' '.$userData['lastname']);
        }

        return new RedirectResponse($this->router->generate('products'));
    }

    /**
     * @return array{'access_token': string, "user_data": array{'firstname': string, "lastname": string}}
     */
    private function fetchAccessTokenPayload(mixed $pimUrl, float|bool|int|string $authorizationCode): array
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

        $payload = \json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        if (!\array_key_exists('access_token', $payload)) {
            throw new \LogicException('Missing access token in response');
        }

        $idToken = $payload['id_token'] ?? null;
        if (null !== $idToken) {
            $openIdPublicKey = $this->fetchOpenIdPublicKey($pimUrl);
            $userData = $this->extractUserDataFromToken($idToken, $openIdPublicKey, $pimUrl);
        }

        return [
            'access_token' => $payload['access_token'],
            'user_data' => $userData ?? [],
        ];
    }

    /**
     * @return array{'firstname': string, "lastname": string}
     */
    private function extractUserDataFromToken(string $idToken, string $publicKey, string $issuer): array
    {
        $jwtConfig = Configuration::forUnsecuredSigner();
        $token = $jwtConfig->parser()->parse($idToken);
        \assert($token instanceof UnencryptedToken);

        $jwtConfig->setValidationConstraints(new IssuedBy($issuer), new SignedWith(new Sha256(), InMemory::plainText($publicKey)));
        $constraints = $jwtConfig->validationConstraints();
        $jwtConfig->validator()->assert($token, ...$constraints);
        $claims = $token->claims();

        if (!$claims->has('firstname') || !$claims->has('lastname')) {
            throw new \LogicException('One or several user profile claims are missing');
        }

        return [
            'firstname' => $claims->get('firstname'),
            'lastname' => $claims->get('lastname'),
        ];
    }

    private function fetchOpenIdPublicKey(string $pimUrl): string
    {
        $openIDPublicKeyUrl = $pimUrl.'/connect/apps/v1/openid/public-key';

        $response = $this->client->request('GET', $openIDPublicKeyUrl)->toArray();
        if (!\array_key_exists('public_key', $response)) {
            throw new \LogicException('Failed to retrieve openid public key');
        }
        if (!\is_string($response['public_key'])) {
            throw new \LogicException('OpenID public key is not a string');
        }

        return $response['public_key'];
    }
}
