<?php

declare(strict_types=1);

namespace App\Query\Locale;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use Symfony\Component\HttpFoundation\RequestStack;

final class GuessCurrentLocaleQuery
{
    private const DEFAULT_USER_LANGUAGE = 'en_US';

    public function __construct(
        private RequestStack $requestStack,
        private AkeneoPimClientInterface $pimApiClient,
    ) {
    }

    public function guess(): string
    {
        $pimAvailableLocales = $this->fetchPimAvailableLocales();

        if (empty($pimAvailableLocales)) {
            throw new \LogicException('No PIM locale available.');
        }

        $userLanguages = $this->getUserLanguages();

        foreach ($userLanguages as $userLanguage) {
            foreach ($pimAvailableLocales as $pimAvailableLocale) {
                if ($pimAvailableLocale === $userLanguage) {
                    return $pimAvailableLocale;
                }
            }
        }

        return $pimAvailableLocales[0];
    }

    /**
     * @return string[]
     */
    private function fetchPimAvailableLocales(): array
    {
        $searchBuilder = new SearchBuilder();
        $searchBuilder->addFilter('enabled', '=', true);
        $searchFilters = $searchBuilder->getFilters();

        $firstPage = $this->pimApiClient->getLocaleApi()->listPerPage(100, false, ['search' => $searchFilters]);

        $pimAvailableLocales = [];
        foreach ($firstPage->getItems() as $locale) {
            $pimAvailableLocales[] = $locale['code'];
        }

        return $pimAvailableLocales;
    }

    /**
     * @return string[]
     */
    private function getUserLanguages(): array
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request) {
            throw new \LogicException('No main request.');
        }

        $userLanguages = $request->getLanguages();
        $userLanguages = empty($userLanguages) ? [self::DEFAULT_USER_LANGUAGE] : $userLanguages;

        return $userLanguages;
    }
}
