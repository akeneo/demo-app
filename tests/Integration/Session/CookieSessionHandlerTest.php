<?php

declare(strict_types=1);

namespace App\Tests\Integration\Session;

use App\Security\Decrypt;
use App\Session\CookieSessionHandler;
use App\Tests\Integration\AbstractIntegrationTest;
use Symfony\Component\HttpFoundation\Cookie;

class CookieSessionHandlerTest extends AbstractIntegrationTest
{
    /**
     * @test
     */
    public function itStartsANewSessionWhenTheCookieIsInitializedWithAnInvalidValue(): void
    {
        $decrypt = $this->container->get(Decrypt::class);
        $handler = $this->container->get(CookieSessionHandler::class);

        $handler->initCookie('invalid_encrypted_cookie');

        $actual = ($decrypt)($handler->getCookie()->getValue());
        $expected = '[]';

        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function itReturnsAnEmptyValueWhenTheCookieIsInvalid(): void
    {
        $handler = $this->container->get(CookieSessionHandler::class);

        $handler->setCookie(
            Cookie::create(
                CookieSessionHandler::COOKIE_NAME,
                'invalid_encrypted_cookie',
            )
        );

        $actual = $handler->read('pim_url');
        $expected = '';

        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function itWritesInANewSessionWhenTheCookieIsInvalid(): void
    {
        $handler = $this->container->get(CookieSessionHandler::class);

        $handler->setCookie(
            Cookie::create(
                CookieSessionHandler::COOKIE_NAME,
                'invalid_encrypted_cookie',
            )
        );

        $this->assertTrue($handler->write('url', 'https://example.com'));
        $this->assertSame('https://example.com', $handler->read('url'));
    }
}
