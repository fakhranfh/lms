<?php

namespace App\Support;

use Illuminate\Support\Facades\Request;

class RootDomains
{
    /**
     * All configured root domains: the primary app.domain plus any
     * APP_EXTRA_DOMAINS entries. Index 0 is always the primary domain.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        return array_values(array_unique(array_merge(
            [config('app.domain')],
            config('app.extra_domains')
        )));
    }

    /**
     * Resolve which configured root domain the given host belongs to
     * (matching the host itself, or any subdomain of it).
     */
    public static function match(string $host): string
    {
        $rootDomain = config('app.domain');

        foreach (config('app.extra_domains') as $extraDomain) {
            if ($host === $extraDomain || str_ends_with($host, ".{$extraDomain}")) {
                return $extraDomain;
            }
        }

        return $rootDomain;
    }

    /**
     * The route-name suffix (e.g. ".alt1") used to register/resolve routes
     * for the given root domain's group. Empty string for the primary domain.
     */
    public static function suffixFor(string $rootDomain): string
    {
        $index = array_search($rootDomain, self::all(), true);

        return $index ? ".alt{$index}" : '';
    }

    /**
     * The route-name suffix for the current request's host, so
     * route("home".RootDomains::currentSuffix()) stays on the domain the
     * visitor is currently browsing.
     */
    public static function currentSuffix(): string
    {
        return self::suffixFor(self::match(Request::getHost()));
    }

    /**
     * Whether the current request's host is a subdomain of a configured
     * root domain (e.g. a school's tenant subdomain) rather than the
     * root domain itself.
     */
    public static function isSubdomain(): bool
    {
        return Request::getHost() !== self::match(Request::getHost());
    }
}
