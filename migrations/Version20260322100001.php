<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create verification_token table
 */
final class Version20260322100001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create verification_token table for email verification';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE verification_token (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            token VARCHAR(255) NOT NULL UNIQUE,
            created_at DATETIME NOT NULL,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            PRIMARY KEY(id),
            FOREIGN KEY (user_id) REFERENCES `user`(id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE verification_token');
    }
}
