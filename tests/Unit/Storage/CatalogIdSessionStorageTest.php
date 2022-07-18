<?php

declare(strict_types=1);

namespace App\Tests\Unit\Storage;

use App\Storage\CatalogIdSessionStorage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CatalogIdSessionStorageTest extends TestCase
{
    private SessionInterface|MockObject $session;
    private ?CatalogIdSessionStorage $catalogIdSessionStorage;

    protected function setUp(): void
    {
        $this->session = $this->getMockBuilder(SessionInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestStack = $this->getMockBuilder(RequestStack::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestStack->method('getSession')->willReturn($this->session);

        $this->catalogIdSessionStorage = new CatalogIdSessionStorage($requestStack);
    }

    protected function tearDown(): void
    {
        $this->catalogIdSessionStorage = null;
    }

    /**
     * @test
     */
    public function itGetsCatalogIdFromTheSession(): void
    {
        $this->session
            ->expects($this->once())
            ->method('get')
            ->with('akeneo_pim_catalog_id')
            ->willReturn('test_catalog_id');

        $catalogId = $this->catalogIdSessionStorage?->getCatalogId();

        self::assertEquals('test_catalog_id', $catalogId);
    }

    /**
     * @test
     */
    public function itGetsNullFromTheSession(): void
    {
        $this->session
            ->expects($this->once())
            ->method('get')
            ->with('akeneo_pim_catalog_id')
            ->willReturn(null);

        $catalogId = $this->catalogIdSessionStorage?->getCatalogId();

        self::assertNull($catalogId);
    }

    /**
     * @test
     */
    public function itSetsCatalogIdIntoTheSession(): void
    {
        $this->session
            ->expects($this->once())
            ->method('set')
            ->with('akeneo_pim_catalog_id', 'NEW_CATALOG_ID');

        $this->catalogIdSessionStorage?->setCatalogId('NEW_CATALOG_ID');
    }

    /**
     * @test
     */
    public function itClearsCatalogIdFromTheSession(): void
    {
        $this->session
            ->expects($this->once())
            ->method('remove')
            ->with('akeneo_pim_catalog_id');

        $this->catalogIdSessionStorage?->clear();
    }
}
