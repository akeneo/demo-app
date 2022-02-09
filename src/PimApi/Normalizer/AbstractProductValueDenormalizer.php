<?php

declare(strict_types=1);

namespace App\PimApi\Normalizer;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;

abstract class AbstractProductValueDenormalizer implements ContextAwareDenormalizerInterface
{
    public const SUPPORTED_ATTRIBUTE_TYPES = [
        'pim_catalog_identifier',
        'pim_catalog_text',
        'pim_catalog_textarea',
        'pim_catalog_number',
        'pim_catalog_boolean',
//        'pim_catalog_date',
//        'pim_catalog_currency',
//        'pim_catalog_price_collection',
//        'pim_catalog_simple_select',
    ];

    public function __construct(
        protected AkeneoPimClientInterface $pimApiClient,
    ) {
    }
}
