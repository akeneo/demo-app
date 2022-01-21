<?php

namespace App\Tests\Unit\Session;

use App\Session\CookieSessionHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;

class CookieSessionHandlerTest extends TestCase
{
    private ?CookieSessionHandler $cookieSessionHandler;

    protected function setUp(): void
    {
        $this->cookieSessionHandler = new CookieSessionHandler();
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
        $this->cookieSessionHandler->initCookie($cookieValue);

        $expectedCookie = Cookie::create(CookieSessionHandler::COOKIE_NAME, $cookieValue);
        $this->assertEquals($expectedCookie, $this->cookieSessionHandler->getCookie());
    }

    /**
     * @test
     */
    public function itGetsCookie(): void
    {
        $this->assertEquals(null, $this->cookieSessionHandler->getCookie());

        $cookieValue = '{"foo": "bar"}';
        $this->cookieSessionHandler->initCookie($cookieValue);

        $expectedCookie = Cookie::create(CookieSessionHandler::COOKIE_NAME, $cookieValue);
        $this->assertEquals($expectedCookie, $this->cookieSessionHandler->getCookie());
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

        $expectedCookie = Cookie::create(CookieSessionHandler::COOKIE_NAME, '[]');
        $result = $this->cookieSessionHandler->destroy('whatever');

        $this->assertEquals(true, $result);
        $this->assertEquals($expectedCookie, $this->cookieSessionHandler->getCookie());
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
        $this->cookieSessionHandler->write('bar', 'baz');
        $expectedCookie = Cookie::create(CookieSessionHandler::COOKIE_NAME, '{"bar":"baz"}');
        $this->assertEquals($expectedCookie, $this->cookieSessionHandler->getCookie());

        $this->cookieSessionHandler->write('qux', 'quux');
        $expectedCookie = Cookie::create(CookieSessionHandler::COOKIE_NAME, '{"bar":"baz","qux":"quux"}');
        $this->assertEquals($expectedCookie, $this->cookieSessionHandler->getCookie());
    }
}
