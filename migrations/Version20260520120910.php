<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260520120910 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if ($schemaManager->tablesExist(['orders'])) {
            $columnsToAdd = [];

            if (!$this->hasColumn($schemaManager, 'orders', 'payment_status')) {
                $columnsToAdd[] = 'ADD payment_status VARCHAR(20) DEFAULT NULL';
            }

            if (!$this->hasColumn($schemaManager, 'orders', 'payment_intent_id')) {
                $columnsToAdd[] = 'ADD payment_intent_id VARCHAR(100) DEFAULT NULL';
            }

            if ($columnsToAdd !== []) {
                $this->addSql('ALTER TABLE orders ' . implode(', ', $columnsToAdd));
            }
        }
    }

    private function hasColumn($schemaManager, string $table, string $column): bool
    {
        $columns = array_change_key_case($schemaManager->listTableColumns($table), CASE_LOWER);

        return array_key_exists(strtolower($column), $columns);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE orders DROP payment_status, DROP payment_intent_id');
    }
}
