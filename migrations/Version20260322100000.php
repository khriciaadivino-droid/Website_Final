<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add verification and Google OAuth fields to User entity
 */
final class Version20260322100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add verifiedAt and googleId fields to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD verified_at DATETIME NULL');
        $this->addSql('ALTER TABLE `user` ADD google_id VARCHAR(255) NULL UNIQUE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP COLUMN google_id');
        $this->addSql('ALTER TABLE `user` DROP COLUMN verified_at');
    }
}
