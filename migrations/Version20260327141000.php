<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327141000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create contact_message table for persisted contact form submissions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE contact_message (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, email VARCHAR(180) NOT NULL, phone VARCHAR(50) DEFAULT NULL, subject VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, email_sent TINYINT(1) NOT NULL, delivery_status VARCHAR(50) NOT NULL, delivery_error LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, INDEX idx_contact_message_created_at (created_at), INDEX idx_contact_message_email_sent (email_sent), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE contact_message');
    }
}
