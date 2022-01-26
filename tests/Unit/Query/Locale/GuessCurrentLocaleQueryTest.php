<?php

namespace App\Tests\Unit\Locale;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\LocaleApiInterface;
use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use App\Query\Locale\GuessCurrentLocaleQuery;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class GuessCurrentLocaleQueryTest extends TestCase
{
    private RequestStack|MockObject $requestStack;
    private PageInterface|MockObject $pimLocaleApiFirstPage;
    private ?GuessCurrentLocaleQuery $guessCurrentLocaleQuery;

    protected function setUp(): void
    {
        $this->requestStack = $this->getMockBuilder(RequestStack::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->pimLocaleApiFirstPage = $this->getMockBuilder(PageInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $pimLocaleApi = $this->getMockBuilder(LocaleApiInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $pimLocaleApi
            ->method('listPerPage')
            ->willReturn($this->pimLocaleApiFirstPage)
        ;

        $pimApiClient = $this->getMockBuilder(AkeneoPimClientInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $pimApiClient
            ->method('getLocaleApi')
            ->willReturn($pimLocaleApi)
        ;

        $this->guessCurrentLocaleQuery = new GuessCurrentLocaleQuery(
            $this->requestStack,
            $pimApiClient,
        );
    }

    protected function tearDown(): void
    {
        $this->guessCurrentLocaleQuery = null;
    }

    /**
     * @test
     */
    public function itThrowsALogicExceptionWhenThereIsNoPimLocales(): void
    {
        $this->pimLocaleApiFirstPage
            ->method('getItems')
            ->willReturn([])
        ;

        $this->expectExceptionObject(new \LogicException('No PIM locale available.'));

        ($this->guessCurrentLocaleQuery)();
    }

    /**
     * @test
     */
    public function itThrowsALogicExceptionWhenThereIsNoMainRequest(): void
    {
        $items = [
            [
                'code' => 'locale_1',
            ],
        ];

        $this->pimLocaleApiFirstPage
            ->method('getItems')
            ->willReturn($items)
        ;

        $this->requestStack
            ->method('getMainRequest')
            ->willReturn(null)
        ;

        $this->expectExceptionObject(new \LogicException('No main request.'));

        ($this->guessCurrentLocaleQuery)();
    }

    /**
     * @test
     */
    public function itReturnsFirstUserLanguagesMatchedByAPimLocale(): void
    {
        $items = [
            [
                'code' => 'locale_2',
            ],
            [
                'code' => 'locale_4',
            ],
            [
                'code' => 'locale_1',
            ],
        ];

        $this->pimLocaleApiFirstPage
            ->method('getItems')
            ->willReturn($items)
        ;

        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $request
            ->method('getLanguages')
            ->willReturn(['locale_3', 'locale_1', 'locale_2'])
        ;

        $this->requestStack
            ->method('getMainRequest')
            ->willReturn($request)
        ;

        $currentLocale = ($this->guessCurrentLocaleQuery)();

        $this->assertEquals('locale_1', $currentLocale);
    }

    /**
     * @test
     */
    public function itReturnsFirstPimLocaleWhenThereIsNoMatchingUserLanguages(): void
    {
        $items = [
            [
                'code' => 'locale_2',
            ],
            [
                'code' => 'locale_1',
            ],
        ];

        $this->pimLocaleApiFirstPage
            ->method('getItems')
            ->willReturn($items)
        ;

        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $request
            ->method('getLanguages')
            ->willReturn(['locale_3'])
        ;

        $this->requestStack
            ->method('getMainRequest')
            ->willReturn($request)
        ;

        $currentLocale = ($this->guessCurrentLocaleQuery)();

        $this->assertEquals('locale_2', $currentLocale);
    }

    /**
     * @test
     */
    public function itCanMatchAndReturnsTheDefaultUserLanguage(): void
    {
        $items = [
            [
                'code' => 'en_US',
            ],
            [
                'code' => 'locale_1',
            ],
        ];

        $this->pimLocaleApiFirstPage
            ->method('getItems')
            ->willReturn($items)
        ;

        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $request
            ->method('getLanguages')
            ->willReturn([])
        ;

        $this->requestStack
            ->method('getMainRequest')
            ->willReturn($request)
        ;

        $currentLocale = ($this->guessCurrentLocaleQuery)();

        $this->assertEquals('en_US', $currentLocale);
    }
}
