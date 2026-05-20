<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251212045447 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $schemaManager = $this->connection->createSchemaManager();
        if ($schemaManager->tablesExist(['pet_profile_management'])) {
            // First add the column as nullable.
            $columns = $schemaManager->listTableColumns('pet_profile_management');
            if (!array_key_exists('owner_id', $columns)) {
                $this->addSql('ALTER TABLE pet_profile_management ADD owner_id INT DEFAULT NULL');
            }

            if ($schemaManager->tablesExist(['pet_owners'])) {
                $foreignKeys = $schemaManager->listTableForeignKeys('pet_profile_management');
                $hasOwnerFk = false;
                foreach ($foreignKeys as $foreignKey) {
                    if ($foreignKey->getName() === 'FK_9AC1397B7E3C61F9') {
                        $hasOwnerFk = true;
                        break;
                    }
                }
                if (!$hasOwnerFk) {
                    $this->addSql('ALTER TABLE pet_profile_management ADD CONSTRAINT FK_9AC1397B7E3C61F9 FOREIGN KEY (owner_id) REFERENCES pet_owners (id)');
                }
            }

            $indexes = $schemaManager->listTableIndexes('pet_profile_management');
            if (!array_key_exists('idx_9ac1397b7e3c61f9', $indexes)) {
                $this->addSql('CREATE INDEX IDX_9AC1397B7E3C61F9 ON pet_profile_management (owner_id)');
            }
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pet_profile_management DROP FOREIGN KEY FK_9AC1397B7E3C61F9');
        $this->addSql('DROP INDEX IDX_9AC1397B7E3C61F9 ON pet_profile_management');
        $this->addSql('ALTER TABLE pet_profile_management DROP owner_id');
    }
}
