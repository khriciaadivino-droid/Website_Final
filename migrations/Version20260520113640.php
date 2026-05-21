<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260520113640 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if ($schemaManager->tablesExist(['orders']) && !$this->hasColumn($schemaManager, 'orders', 'payment_method')) {
            $this->addSql('ALTER TABLE orders ADD payment_method VARCHAR(30) DEFAULT NULL');
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
        $this->addSql('ALTER TABLE orders DROP payment_method');
    }
}
