<?php

namespace App\Tests\Unit\Session;

use App\Security\Decrypt;
use App\Security\Encrypt;
use App\Session\CookieSessionHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;

class CookieSessionHandlerTest extends TestCase
{
    private ?CookieSessionHandler $cookieSessionHandler;
    private Encrypt $encrypt;
    private Decrypt $decrypt;

    protected function setUp(): void
    {
        $this->encrypt = new Encrypt('AES-256-CBC', 'password');
        $this->decrypt = new Decrypt('AES-256-CBC', 'password');

        $this->cookieSessionHandler = new CookieSessionHandler($this->encrypt, $this->decrypt);
    }

    protected function tearDown(): void
    {
        $this->cookieSessionHandler = null;
    }

    /**
     * @test
     */
    public function itInitsCookie(): void
    {
        $cookieValue = '{"foo": "bar"}';
        $expectedCookie = Cookie::create(CookieSessionHandler::COOKIE_NAME, ($this->encrypt)($cookieValue));

        $this->cookieSessionHandler->initCookie($cookieValue);

        $this->assertSameCookies($expectedCookie, $this->cookieSessionHandler->getCookie());
    }

    /**
     * @test
     */
    public function itGetsCookie(): void
    {
        $this->assertEquals(null, $this->cookieSessionHandler->getCookie());

        $cookieValue = '{"foo": "bar"}';
        $expectedCookie = Cookie::create(CookieSessionHandler::COOKIE_NAME, ($this->encrypt)($cookieValue));

        $this->cookieSessionHandler->initCookie($cookieValue);

        $this->assertSameCookies($expectedCookie, $this->cookieSessionHandler->getCookie());
    }

    /**
     * @test
     */
    public function itClosesAndReturnsTrue(): void
    {
        $this->assertEquals(true, $this->cookieSessionHandler->close());
    }

    /**
     * @test
     */
    public function itDestroysCookieAndReturnsTrue(): void
    {
        $cookieValue = '{"foo": "bar"}';
        $this->cookieSessionHandler->initCookie($cookieValue);

        $expectedCookie = Cookie::create(CookieSessionHandler::COOKIE_NAME, ($this->encrypt)('[]'));
        $result = $this->cookieSessionHandler->destroy('whatever');

        $this->assertEquals(true, $result);

        $this->assertSameCookies($expectedCookie, $this->cookieSessionHandler->getCookie());
    }

    /**
     * @test
     */
    public function itReturnsZeroOnGarbageCollector(): void
    {
        $this->assertEquals(0, $this->cookieSessionHandler->gc(1234));
    }

    /**
     * @test
     */
    public function itOpensAndReturnsTrue(): void
    {
        $this->assertEquals(true, $this->cookieSessionHandler->open('randomPath', 'randomName'));
    }

    /**
     * @test
     */
    public function itReads(): void
    {
        $cookieValue = '{"foo": "bar"}';
        $this->cookieSessionHandler->initCookie($cookieValue);

        $this->assertEquals('bar', $this->cookieSessionHandler->read('foo'));
        $this->assertEquals('', $this->cookieSessionHandler->read('randomKey'));
    }

    /**
     * @test
     */
    public function itWritesAndReturnsTrue(): void
    {
        $this->cookieSessionHandler->destroy('');
        $this->cookieSessionHandler->write('bar', 'baz');
        $expectedCookie = Cookie::create(CookieSessionHandler::COOKIE_NAME, ($this->encrypt)('{"bar":"baz"}'));
        $this->assertSameCookies($expectedCookie, $this->cookieSessionHandler->getCookie());

        $this->cookieSessionHandler->write('qux', 'quux');
        $expectedCookie = Cookie::create(CookieSessionHandler::COOKIE_NAME, ($this->encrypt)('{"bar":"baz","qux":"quux"}'));
        $this->assertSameCookies($expectedCookie, $this->cookieSessionHandler->getCookie());
    }

    private function assertSameCookies(Cookie $expectedCookie, Cookie $cookie): void
    {
        $this->assertEquals(($this->decrypt)($expectedCookie->getValue()), ($this->decrypt)($cookie->getValue()));

        $this->assertEquals($expectedCookie->getName(), $cookie->getName());
        $this->assertEquals($expectedCookie->getDomain(), $cookie->getDomain());
        $this->assertEquals($expectedCookie->getExpiresTime(), $cookie->getExpiresTime());
        $this->assertEquals($expectedCookie->getPath(), $cookie->getPath());
    }
}
