<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260516174354 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        $hasVerificationToken = $schemaManager->tablesExist(['verification_token']);
        $hasVerificationTokenFk = $hasVerificationToken && $this->hasForeignKey($schemaManager, 'verification_token', 'FK_C1CC006BA76ED395');

        $hasPetProfileManagement = $schemaManager->tablesExist(['pet_profile_management']);
        $hasOwnerId = $hasPetProfileManagement && $this->hasColumn($schemaManager, 'pet_profile_management', 'owner_id');
        $hasPetOwnerFk = $hasPetProfileManagement && $this->hasForeignKey($schemaManager, 'pet_profile_management', 'FK_9AC1397B7E3C61F9');

        $hasProductss = $schemaManager->tablesExist(['productss']);
        $hasCreatedById = $hasProductss && $this->hasColumn($schemaManager, 'productss', 'created_by_id');
        $hasProductssFk = $hasProductss && $this->hasForeignKey($schemaManager, 'productss', 'FK_9003CDBBB03A8386');

        $hasUser = $schemaManager->tablesExist(['user']);
        $hasVerifiedAt = $hasUser && $this->hasColumn($schemaManager, 'user', 'verified_at');
        $hasGoogleId = $hasUser && $this->hasColumn($schemaManager, 'user', 'google_id');
        $hasGoogleIdIndex = $hasUser && $this->hasIndex($schemaManager, 'user', 'UNIQ_8D93D64976F5C865');
        $hasLegacyGoogleIdIndex = $hasUser && $this->hasIndex($schemaManager, 'user', 'google_id');

        $this->addSql('CREATE TABLE IF NOT EXISTS contact_message (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, email VARCHAR(180) NOT NULL, phone VARCHAR(50) DEFAULT NULL, subject VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, email_sent TINYINT(1) NOT NULL, delivery_status VARCHAR(50) NOT NULL, delivery_error LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, INDEX idx_contact_message_created_at (created_at), INDEX idx_contact_message_email_sent (email_sent), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS customer (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(100) NOT NULL, phone_number VARCHAR(20) DEFAULT NULL, address LONGTEXT DEFAULT NULL, city VARCHAR(100) DEFAULT NULL, postal_code VARCHAR(20) DEFAULT NULL, country VARCHAR(100) DEFAULT NULL, registration_date DATETIME NOT NULL, last_purchase_date DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS verification_token (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, token VARCHAR(255) NOT NULL, expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL, used_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_C1CC006B5F37A13B (token), INDEX IDX_C1CC006BA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        if (!$hasVerificationTokenFk && $hasUser) {
            $this->addSql('ALTER TABLE verification_token ADD CONSTRAINT FK_C1CC006BA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        }

        $this->addSql('DROP TABLE IF EXISTS shipment_tracking');
        $this->addSql('DROP TABLE IF EXISTS shipment');
        $this->addSql('DROP TABLE IF EXISTS courier');
        $this->addSql('DROP TABLE IF EXISTS shipping_zone');

        if ($hasPetProfileManagement && $hasOwnerId && !$hasPetOwnerFk) {
            $this->addSql('ALTER TABLE pet_profile_management ADD CONSTRAINT FK_9AC1397B7E3C61F9 FOREIGN KEY (owner_id) REFERENCES pet_owners (id)');
        }

        if ($hasProductss) {
            if (!$hasCreatedById) {
                $this->addSql('ALTER TABLE productss ADD created_by_id INT DEFAULT NULL');
            }
            if (!$hasProductssFk) {
                $this->addSql('ALTER TABLE productss ADD CONSTRAINT FK_9003CDBBB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
            }
        }

        if ($hasUser) {
            $columnsToAdd = [];
            if (!$hasVerifiedAt) {
                $columnsToAdd[] = 'ADD verified_at DATETIME DEFAULT NULL';
            }
            if (!$hasGoogleId) {
                $columnsToAdd[] = 'ADD google_id VARCHAR(255) DEFAULT NULL';
            }
            if ($columnsToAdd !== []) {
                $this->addSql('ALTER TABLE `user` ' . implode(', ', $columnsToAdd));
            }
            if (!$hasGoogleIdIndex && !$hasLegacyGoogleIdIndex) {
                $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D64976F5C865 ON `user` (google_id)');
            }
        }
    }

    private function hasColumn($schemaManager, string $table, string $column): bool
    {
        $columns = array_change_key_case($schemaManager->listTableColumns($table), CASE_LOWER);

        return array_key_exists(strtolower($column), $columns);
    }

    private function hasForeignKey($schemaManager, string $table, string $foreignKeyName): bool
    {
        foreach ($schemaManager->listTableForeignKeys($table) as $foreignKey) {
            if (strcasecmp($foreignKey->getName(), $foreignKeyName) === 0) {
                return true;
            }
        }

        return false;
    }

    private function hasIndex($schemaManager, string $table, string $indexName): bool
    {
        foreach ($schemaManager->listTableIndexes($table) as $index) {
            if (strcasecmp($index->getName(), $indexName) === 0) {
                return true;
            }
        }

        return false;
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE courier (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, contact_person VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, email VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, phone VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, website VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, service_areas LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, is_active TINYINT(1) NOT NULL, notes LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE shipment (id INT AUTO_INCREMENT NOT NULL, tracking_number VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, customer_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, customer_email VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, customer_phone VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, delivery_address LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, status VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, shipped_at DATETIME DEFAULT NULL, delivered_at DATETIME DEFAULT NULL, estimated_delivery DATETIME DEFAULT NULL, shipping_cost NUMERIC(10, 2) NOT NULL, weight NUMERIC(10, 2) DEFAULT NULL, items LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, notes LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, shipping_zone VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, courier VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, UNIQUE INDEX UNIQ_2CB20DC3E1C9C18 (tracking_number), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE shipment_tracking (id INT AUTO_INCREMENT NOT NULL, shipment_id INT NOT NULL, status VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, location VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, customer_notified TINYINT(1) NOT NULL, INDEX IDX_E2B9D7D7BE036FC (shipment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE shipping_zone (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, region VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, base_rate NUMERIC(10, 2) NOT NULL, per_kg_rate NUMERIC(10, 2) DEFAULT NULL, is_active TINYINT(1) NOT NULL, estimated_days INT DEFAULT NULL, zone_type VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, courier VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE shipment_tracking ADD CONSTRAINT FK_E2B9D7D7BE036FC FOREIGN KEY (shipment_id) REFERENCES shipment (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE verification_token DROP FOREIGN KEY FK_C1CC006BA76ED395');
        $this->addSql('DROP TABLE contact_message');
        $this->addSql('DROP TABLE customer');
        $this->addSql('DROP TABLE verification_token');
        $this->addSql('ALTER TABLE pet_profile_management DROP FOREIGN KEY FK_9AC1397B7E3C61F9');
        $this->addSql('ALTER TABLE productss DROP FOREIGN KEY FK_9003CDBBB03A8386');
        $this->addSql('DROP INDEX IDX_9003CDBBB03A8386 ON productss');
        $this->addSql('ALTER TABLE productss DROP created_by_id');
        $this->addSql('DROP INDEX UNIQ_8D93D64976F5C865 ON `user`');
        $this->addSql('ALTER TABLE `user` DROP verified_at, DROP google_id');
    }
}
