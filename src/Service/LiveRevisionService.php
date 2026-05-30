<?php

namespace App\Service;

use Doctrine\DBAL\Connection;

class LiveRevisionService
{
    public const ORDERS = 'orders';
    public const ACTIVITY = 'activity';
    public const PRODUCTS = 'products';
    public const STOCKS = 'stocks';
    public const CATEGORIES = 'categories';
    public const USERS = 'users';
    public const PETS = 'pets';
    public const CONTACT = 'contact';
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
        self::CONTACT,
        self::DASHBOARD,
    ];

    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function bump(string $domain): int
    {
        if (!in_array($domain, self::DOMAINS, true)) {
            throw new \InvalidArgumentException(sprintf('Unknown live revision domain "%s".', $domain));
        }

        $next = $this->increment($domain);

        if ($domain !== self::DASHBOARD) {
            $this->increment(self::DASHBOARD);
        }

        return $next;
    }

    public function current(string $domain): int
    {
        if (!in_array($domain, self::DOMAINS, true)) {
            return 0;
        }

        $revision = $this->connection->fetchOne(
            'SELECT revision FROM live_revision WHERE domain = ?',
            [$domain]
        );

        return $revision !== false ? (int) $revision : 0;
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

    private function increment(string $domain): int
    {
        $this->connection->executeStatement(
            'INSERT INTO live_revision (domain, revision) VALUES (?, 1)
             ON DUPLICATE KEY UPDATE revision = revision + 1',
            [$domain]
        );

        return $this->current($domain);
    }
}
