<?php

namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;

class OrderLiveRevisionService
{
    private const CACHE_KEY = 'orders.live_revision';

    public function __construct(
        private readonly CacheInterface $cache,
    ) {}

    public function bump(): int
    {
        $next = $this->current() + 1;
        $this->cache->delete(self::CACHE_KEY);
        $this->cache->get(self::CACHE_KEY, static fn () => $next);

        return $next;
    }

    public function current(): int
    {
        return (int) $this->cache->get(self::CACHE_KEY, static fn () => 0);
    }
}
