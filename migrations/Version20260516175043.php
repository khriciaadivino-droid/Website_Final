<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260516175043 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contact_message (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, email VARCHAR(180) NOT NULL, phone VARCHAR(50) DEFAULT NULL, subject VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, email_sent TINYINT(1) NOT NULL, delivery_status VARCHAR(50) NOT NULL, delivery_error LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, INDEX idx_contact_message_created_at (created_at), INDEX idx_contact_message_email_sent (email_sent), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE shipment_tracking DROP FOREIGN KEY FK_E2B9D7D7BE036FC');
        $this->addSql('DROP TABLE courier');
        $this->addSql('DROP TABLE shipment');
        $this->addSql('DROP TABLE shipment_tracking');
        $this->addSql('DROP TABLE shipping_zone');
        $this->addSql('ALTER TABLE productss ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE productss ADD CONSTRAINT FK_9003CDBBB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_9003CDBBB03A8386 ON productss (created_by_id)');
        $this->addSql('ALTER TABLE user RENAME INDEX google_id TO UNIQ_8D93D64976F5C865');
        $this->addSql('ALTER TABLE verification_token DROP FOREIGN KEY verification_token_ibfk_1');
        $this->addSql('ALTER TABLE verification_token RENAME INDEX token TO UNIQ_C1CC006B5F37A13B');
        $this->addSql('ALTER TABLE verification_token RENAME INDEX fk_c1cc006ba76ed395 TO IDX_C1CC006BA76ED395');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE courier (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, contact_person VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, email VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, phone VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, website VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, service_areas LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, is_active TINYINT(1) NOT NULL, notes LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE shipment (id INT AUTO_INCREMENT NOT NULL, tracking_number VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, customer_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, customer_email VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, customer_phone VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, delivery_address LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, status VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, shipped_at DATETIME DEFAULT NULL, delivered_at DATETIME DEFAULT NULL, estimated_delivery DATETIME DEFAULT NULL, shipping_cost NUMERIC(10, 2) NOT NULL, weight NUMERIC(10, 2) DEFAULT NULL, items LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, notes LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, shipping_zone VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, courier VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, UNIQUE INDEX UNIQ_2CB20DC3E1C9C18 (tracking_number), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE shipment_tracking (id INT AUTO_INCREMENT NOT NULL, shipment_id INT NOT NULL, status VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, location VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, customer_notified TINYINT(1) NOT NULL, INDEX IDX_E2B9D7D7BE036FC (shipment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE shipping_zone (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, region VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, base_rate NUMERIC(10, 2) NOT NULL, per_kg_rate NUMERIC(10, 2) DEFAULT NULL, is_active TINYINT(1) NOT NULL, estimated_days INT DEFAULT NULL, zone_type VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, courier VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE shipment_tracking ADD CONSTRAINT FK_E2B9D7D7BE036FC FOREIGN KEY (shipment_id) REFERENCES shipment (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('DROP TABLE contact_message');
        $this->addSql('ALTER TABLE productss DROP FOREIGN KEY FK_9003CDBBB03A8386');
        $this->addSql('DROP INDEX IDX_9003CDBBB03A8386 ON productss');
        $this->addSql('ALTER TABLE productss DROP created_by_id');
        $this->addSql('ALTER TABLE `user` RENAME INDEX uniq_8d93d64976f5c865 TO google_id');
        $this->addSql('ALTER TABLE verification_token RENAME INDEX uniq_c1cc006b5f37a13b TO token');
        $this->addSql('ALTER TABLE verification_token RENAME INDEX idx_c1cc006ba76ed395 TO FK_C1CC006BA76ED395');
    }
}
