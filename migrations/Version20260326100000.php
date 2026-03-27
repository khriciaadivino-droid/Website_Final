<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260326100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Shipment table with auto-generated codes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS shipment (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(50) NOT NULL, tracking_number VARCHAR(100) DEFAULT NULL, status VARCHAR(50) NOT NULL, shipment_date DATETIME NOT NULL, delivery_date DATETIME DEFAULT NULL, origin VARCHAR(100) DEFAULT NULL, destination VARCHAR(100) DEFAULT NULL, weight DOUBLE PRECISION DEFAULT NULL, carrier VARCHAR(50) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_2D693F17F3FD585E9 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS shipment');
    }
}
