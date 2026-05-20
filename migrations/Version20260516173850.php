<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260516173850 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contact_message (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, email VARCHAR(180) NOT NULL, phone VARCHAR(50) DEFAULT NULL, subject VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, email_sent TINYINT(1) NOT NULL, delivery_status VARCHAR(50) NOT NULL, delivery_error LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, INDEX idx_contact_message_created_at (created_at), INDEX idx_contact_message_email_sent (email_sent), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE customer (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(100) NOT NULL, phone_number VARCHAR(20) DEFAULT NULL, address LONGTEXT DEFAULT NULL, city VARCHAR(100) DEFAULT NULL, postal_code VARCHAR(20) DEFAULT NULL, country VARCHAR(100) DEFAULT NULL, registration_date DATETIME NOT NULL, last_purchase_date DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE pet_owners (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(100) NOT NULL, phone_number VARCHAR(20) DEFAULT NULL, address LONGTEXT DEFAULT NULL, registration_date DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, full_name VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL, last_login_at DATETIME DEFAULT NULL, status VARCHAR(20) NOT NULL, created_by VARCHAR(100) DEFAULT NULL, verified_at DATETIME DEFAULT NULL, google_id VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), UNIQUE INDEX UNIQ_8D93D64976F5C865 (google_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE verification_token (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, token VARCHAR(255) NOT NULL, expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL, used_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_C1CC006B5F37A13B (token), INDEX IDX_C1CC006BA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE verification_token ADD CONSTRAINT FK_C1CC006BA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE orders ADD quantity INT NOT NULL');
        $this->addSql('ALTER TABLE pet_profile_management ADD owner_id INT DEFAULT NULL, ADD is_pet_of_the_month TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE pet_profile_management ADD CONSTRAINT FK_9AC1397B7E3C61F9 FOREIGN KEY (owner_id) REFERENCES pet_owners (id)');
        $this->addSql('CREATE INDEX IDX_9AC1397B7E3C61F9 ON pet_profile_management (owner_id)');
        $this->addSql('ALTER TABLE productss ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE productss ADD CONSTRAINT FK_9003CDBBB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_9003CDBBB03A8386 ON productss (created_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pet_profile_management DROP FOREIGN KEY FK_9AC1397B7E3C61F9');
        $this->addSql('ALTER TABLE productss DROP FOREIGN KEY FK_9003CDBBB03A8386');
        $this->addSql('ALTER TABLE verification_token DROP FOREIGN KEY FK_C1CC006BA76ED395');
        $this->addSql('DROP TABLE contact_message');
        $this->addSql('DROP TABLE customer');
        $this->addSql('DROP TABLE pet_owners');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE verification_token');
        $this->addSql('DROP INDEX IDX_9003CDBBB03A8386 ON productss');
        $this->addSql('ALTER TABLE productss DROP created_by_id');
        $this->addSql('DROP INDEX IDX_9AC1397B7E3C61F9 ON pet_profile_management');
        $this->addSql('ALTER TABLE pet_profile_management DROP owner_id, DROP is_pet_of_the_month');
        $this->addSql('ALTER TABLE orders DROP quantity');
    }
}
