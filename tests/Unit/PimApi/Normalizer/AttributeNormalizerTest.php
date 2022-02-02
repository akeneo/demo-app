<?php

namespace App\Tests\Unit\PimApi\Normalizer;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\AttributeApiInterface;
use Akeneo\Pim\ApiClient\Api\AttributeOptionApiInterface;
use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use App\PimApi\Model\Attribute;
use App\PimApi\Normalizer\AttributeNormalizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AttributeNormalizerTest extends TestCase
{
    private AttributeApiInterface|MockObject $pimAttributeApi;
    private PageInterface|MockObject $pimAttributeOptionApiFirstPage;
    private ?AttributeNormalizer $attributeNormalizer;

    protected function setUp(): void
    {
        // mock PIM attribute API
        $this->pimAttributeApi = $this->getMockBuilder(AttributeApiInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        // mock PIM attribute option API
        $this->pimAttributeOptionApiFirstPage = $this->getMockBuilder(PageInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $pimAttributeOptionApi = $this->getMockBuilder(AttributeOptionApiInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pimAttributeOptionApi
            ->method('listPerPage')
            ->willReturn($this->pimAttributeOptionApiFirstPage);

        // mock PIM API Client
        $pimApiClient = $this->getMockBuilder(AkeneoPimClientInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pimApiClient
            ->method('getAttributeApi')
            ->willReturn($this->pimAttributeApi);
        $pimApiClient
            ->method('getAttributeOptionApi')
            ->willReturn($pimAttributeOptionApi);

        $this->attributeNormalizer = new AttributeNormalizer(
            $pimApiClient,
        );
    }

    protected function tearDown(): void
    {
        $this->attributeNormalizer = null;
    }

    /**
     * @test
     *
     * @dataProvider provideAttributeOfEachTypes
     */
    public function itDenormalizesAttribute(
        string $attributeType,
        mixed  $attributeRawValue,
        mixed  $attributeExpectedValue,
    ): void {
        $this->pimAttributeApi
            ->method('get')
            ->with('attribute001')
            ->willReturn([
                'type' => $attributeType,
                'labels' => [
                    'en_US' => 'Attribute name',
                    'fr_FR' => 'Nom de l\'attribut',
                ],
            ]);

        $attributeResult = $this->attributeNormalizer->denormalizeFromApi(
            'attribute001',
            [
                [
                    'locale' => null,
                    'scope' => null,
                    'data' => $attributeRawValue,
                ],
            ],
            'fr_FR',
            'scope1',
        );

        $expectedAttribute = new Attribute('Nom de l\'attribut', $attributeType, $attributeExpectedValue);

        $this->assertEqualsCanonicalizing($expectedAttribute, $attributeResult);
    }

    /**
     * @dataProvider
     */
    private function provideAttributeOfEachTypes(): array
    {
        return [
            'identifier' => [
                'pim_catalog_identifier',
                '123456-7891011',
                '123456-7891011',
            ],
            'text' => [
                'pim_catalog_text',
                'du texte',
                'du texte',
            ],
            'textarea' => [
                'pim_catalog_textarea',
                'beaucoup plus de texte',
                'beaucoup plus de texte',
            ],
            'number' => [
                'pim_catalog_number',
                42,
                '42',
            ],
            'date' => [
                'pim_catalog_date',
                '1985-09-05T00:00:00+00:00',
                '1985-09-05T00:00:00+00:00',
            ],
            'boolean' => [
                'pim_catalog_boolean',
                true,
                true,
            ],
            'metric' => [
                'pim_catalog_metric',
                [
                    'amount' => 2.25,
                    'unit' => 'kg',
                ],
                '2.25 kg',
            ],
            'price' => [
                'pim_catalog_price_collection',
                [
                    [
                        'amount' => 16,
                        'currency' => 'EUR',
                    ],
                    [
                        'amount' => 16.99,
                        'currency' => 'DOL',
                    ],
                ],
                '16 EUR; 16.99 DOL',
            ],
        ];
    }

    /**
     * @test
     */
    public function itDenormalizesSimpleSelectAttribute(): void
    {
        // TODO
    }

    /**
     * @test
     */
    public function itThrowsExceptionWhenDenormalizingUnsupportedAttribute(): void
    {
        // TODO
    }
}
