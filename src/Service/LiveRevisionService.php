<?php

namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;

class LiveRevisionService
{
    public const ORDERS = 'orders';
    public const ACTIVITY = 'activity';
    public const PRODUCTS = 'products';
    public const STOCKS = 'stocks';
    public const CATEGORIES = 'categories';
    public const USERS = 'users';
    public const PETS = 'pets';
    public const DASHBOARD = 'dashboard';

    /** @var list<string> */
    public const DOMAINS = [
        self::ORDERS,
        self::ACTIVITY,
        self::PRODUCTS,
        self::STOCKS,
        self::CATEGORIES,
        self::USERS,
        self::PETS,
        self::DASHBOARD,
    ];

    public function __construct(
        private readonly CacheInterface $cache,
    ) {}

    public function bump(string $domain): int
    {
        if (!in_array($domain, self::DOMAINS, true)) {
            throw new \InvalidArgumentException(sprintf('Unknown live revision domain "%s".', $domain));
        }

        $next = $this->current($domain) + 1;
        $cacheKey = $this->cacheKey($domain);
        $this->cache->delete($cacheKey);
        $this->cache->get($cacheKey, static fn () => $next);

        if ($domain !== self::DASHBOARD) {
            $this->bump(self::DASHBOARD);
        }

        return $next;
    }

    public function current(string $domain): int
    {
        if (!in_array($domain, self::DOMAINS, true)) {
            return 0;
        }

        return (int) $this->cache->get($this->cacheKey($domain), static fn () => 0);
    }

    /**
     * @return array<string, int>
     */
    public function snapshot(): array
    {
        $snapshot = [];

        foreach (self::DOMAINS as $domain) {
            $snapshot[$domain] = $this->current($domain);
        }

        return $snapshot;
    }

    private function cacheKey(string $domain): string
    {
        return sprintf('live_revision.%s', $domain);
    }
}
