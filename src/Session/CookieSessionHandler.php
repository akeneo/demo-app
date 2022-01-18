<?php

declare(strict_types=1);

namespace App\Session;

use Symfony\Component\HttpFoundation\Cookie;

class CookieSessionHandler implements \SessionHandlerInterface
{
    public const COOKIE_NAME = 'demo_app_session_cookie';
    private ?Cookie $cookie = null;

    public function initCookie(?string $value): void
    {
        $this->cookie = Cookie::create(
            self::COOKIE_NAME,
            null !== $value ? $value : \json_encode([], JSON_THROW_ON_ERROR),
        );
    }

    public function getCookie(): ?Cookie
    {
        return $this->cookie;
    }

    public function close()
    {
        return true;
    }

    public function destroy($id)
    {
        $this->cookie = Cookie::create(
            self::COOKIE_NAME,
            \json_encode([]),
            $this->cookie->getExpiresTime(),
            $this->cookie->getPath(),
            $this->cookie->getDomain(),
            $this->cookie->isSecure(),
            $this->cookie->isHttpOnly(),
            $this->cookie->isRaw(),
            $this->cookie->getSameSite(),
        );

        return true;
    }

    public function gc($max_lifetime)
    {
        return true;
    }

    public function open($path, $name)
    {
        return true;
    }

    public function read($id)
    {
        $session = \json_decode($this->cookie->getValue(), true);

        if (\array_key_exists($id, $session)) {
            return $session[$id];
        }

        return '';
    }

    public function write($id, $data)
    {
        $session = \json_decode($this->cookie->getValue(), true, 512, JSON_THROW_ON_ERROR);
        $session[$id] = $data;

        $this->cookie = Cookie::create(
            self::COOKIE_NAME,
            \json_encode($session, JSON_THROW_ON_ERROR),
            $this->cookie->getExpiresTime(),
            $this->cookie->getPath(),
            $this->cookie->getDomain(),
            $this->cookie->isSecure(),
            $this->cookie->isHttpOnly(),
            $this->cookie->isRaw(),
            $this->cookie->getSameSite(),
        );

        return true;
    }
}
