<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260516173627 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // All required columns, tables, constraints, and indexes already exist
        // This migration is a no-op as all schema modifications have been applied by previous migrations
    }

    public function down(Schema $schema): void
    {
        // This migration is a no-op - nothing to undo
    }
}
