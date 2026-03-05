<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251211142017 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE courier (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, contact_person VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, phone VARCHAR(50) DEFAULT NULL, website VARCHAR(255) DEFAULT NULL, service_areas LONGTEXT DEFAULT NULL, is_active TINYINT(1) NOT NULL, notes LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE shipment (id INT AUTO_INCREMENT NOT NULL, shipping_zone_id INT NOT NULL, courier_id INT NOT NULL, tracking_number VARCHAR(100) NOT NULL, customer_name VARCHAR(255) NOT NULL, customer_email VARCHAR(255) DEFAULT NULL, customer_phone VARCHAR(50) DEFAULT NULL, delivery_address LONGTEXT NOT NULL, status VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, shipped_at DATETIME DEFAULT NULL, delivered_at DATETIME DEFAULT NULL, estimated_delivery DATETIME DEFAULT NULL, shipping_cost NUMERIC(10, 2) NOT NULL, weight NUMERIC(10, 2) DEFAULT NULL, items LONGTEXT DEFAULT NULL, notes LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_2CB20DC3E1C9C18 (tracking_number), INDEX IDX_2CB20DC7964396F (shipping_zone_id), INDEX IDX_2CB20DCE3D8151C (courier_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE shipment_tracking (id INT AUTO_INCREMENT NOT NULL, shipment_id INT NOT NULL, status VARCHAR(100) NOT NULL, location VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, customer_notified TINYINT(1) NOT NULL, INDEX IDX_E2B9D7D7BE036FC (shipment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE shipping_zone (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, region VARCHAR(255) NOT NULL, base_rate NUMERIC(10, 2) NOT NULL, per_kg_rate NUMERIC(10, 2) DEFAULT NULL, is_active TINYINT(1) NOT NULL, estimated_days INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE shipment ADD CONSTRAINT FK_2CB20DC7964396F FOREIGN KEY (shipping_zone_id) REFERENCES shipping_zone (id)');
        $this->addSql('ALTER TABLE shipment ADD CONSTRAINT FK_2CB20DCE3D8151C FOREIGN KEY (courier_id) REFERENCES courier (id)');
        $this->addSql('ALTER TABLE shipment_tracking ADD CONSTRAINT FK_E2B9D7D7BE036FC FOREIGN KEY (shipment_id) REFERENCES shipment (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE shipment DROP FOREIGN KEY FK_2CB20DC7964396F');
        $this->addSql('ALTER TABLE shipment DROP FOREIGN KEY FK_2CB20DCE3D8151C');
        $this->addSql('ALTER TABLE shipment_tracking DROP FOREIGN KEY FK_E2B9D7D7BE036FC');
        $this->addSql('DROP TABLE courier');
        $this->addSql('DROP TABLE shipment');
        $this->addSql('DROP TABLE shipment_tracking');
        $this->addSql('DROP TABLE shipping_zone');
    }
}
