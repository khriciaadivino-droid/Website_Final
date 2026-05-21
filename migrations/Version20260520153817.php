<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260520153817 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['orders', 'productss'])) {
            return;
        }

        if (!$this->hasColumn($schemaManager, 'orders', 'product_id')) {
            return;
        }

        $ordersForeignKeys = $schemaManager->listTableForeignKeys('orders');
        $hasOrdersProductForeignKey = false;
        $needsForeignKeyUpdate = false;

        foreach ($ordersForeignKeys as $foreignKey) {
            if (strcasecmp($foreignKey->getName(), 'FK_E52FFDEE4584665A') !== 0) {
                continue;
            }

            $hasOrdersProductForeignKey = true;
            $onDelete = strtoupper((string) $foreignKey->onDelete());
            $foreignTable = strtolower($foreignKey->getForeignTableName());

            if ($foreignTable !== 'productss' || $onDelete !== 'SET NULL') {
                $needsForeignKeyUpdate = true;
            }

            break;
        }

        if ($hasOrdersProductForeignKey && $needsForeignKeyUpdate) {
            $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE4584665A');
            $hasOrdersProductForeignKey = false;
        }

        if (!$hasOrdersProductForeignKey) {
            $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE4584665A FOREIGN KEY (product_id) REFERENCES productss (id) ON DELETE SET NULL');
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
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE4584665A');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE4584665A FOREIGN KEY (product_id) REFERENCES productss (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
