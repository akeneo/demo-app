<?php

declare(strict_types=1);

namespace App\Session;

use App\Security\Decrypt;
use App\Security\Encrypt;
use Symfony\Component\HttpFoundation\Cookie;

class CookieSessionHandler implements \SessionHandlerInterface
{
    public const COOKIE_NAME = 'demo_app_session_cookie';
    private ?Cookie $cookie = null;

    public function __construct(
        private Encrypt $encrypt,
        private Decrypt $decrypt,
    ) {
    }

    public function initCookie(?string $value): void
    {
        $this->cookie = Cookie::create(
            self::COOKIE_NAME,
            null !== $value ? $value : ($this->encrypt)(\json_encode([], JSON_THROW_ON_ERROR)),
        );
    }

    public function getCookie(): ?Cookie
    {
        return $this->cookie;
    }

    public function close(): bool
    {
        return true;
    }

    /**
     * @param string $id
     */
    public function destroy($id): bool
    {
        $this->cookie = Cookie::create(
            self::COOKIE_NAME,
            ($this->encrypt)(\json_encode([], JSON_THROW_ON_ERROR)),
        );

        return true;
    }

    /**
     * @param int $max_lifetime
     */
    public function gc($max_lifetime): int|false
    {
        return 0;
    }

    /**
     * @param string $path
     * @param string $name
     */
    public function open($path, $name): bool
    {
        return true;
    }

    /**
     * @param string $id
     */
    public function read($id): string
    {
        if (null !== $this->cookie) {
            $cookieValue = ($this->decrypt)((string) $this->cookie->getValue());
            $session = \json_decode($cookieValue, true, 512, JSON_THROW_ON_ERROR);

            if (\array_key_exists($id, $session)) {
                return $session[$id];
            }
        }

        return '';
    }

    /**
     * @param string $id
     * @param string $data
     */
    public function write($id, $data): bool
    {
        $session = [];
        if (null !== $this->cookie) {
            $cookieValue = ($this->decrypt)((string) $this->cookie->getValue());
            $session = \json_decode($cookieValue, true, 512, JSON_THROW_ON_ERROR);
        }
        $session[$id] = $data;

        $this->cookie = Cookie::create(
            self::COOKIE_NAME,
            ($this->encrypt)(\json_encode($session, JSON_THROW_ON_ERROR)),
        );

        return true;
    }
}
