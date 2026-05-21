<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260520190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Track whether an order has already applied its stock deduction';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['orders'])) {
            return;
        }

        if (!$this->hasColumn($schemaManager, 'orders', 'stock_deducted')) {
            $this->addSql('ALTER TABLE orders ADD stock_deducted TINYINT(1) DEFAULT 0 NOT NULL');
        }

        $this->addSql('UPDATE orders SET stock_deducted = 1');
    }

    private function hasColumn($schemaManager, string $table, string $column): bool
    {
        $columns = array_change_key_case($schemaManager->listTableColumns($table), CASE_LOWER);

        return array_key_exists(strtolower($column), $columns);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders DROP stock_deducted');
    }
}
