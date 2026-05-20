<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260516173556 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add schema modifications for orders, pet profiles, and product ownership';
    }

    public function up(Schema $schema): void
    {
        // All required columns, constraints, and indexes already exist
        // This migration is a no-op as all schema modifications have been applied by previous migrations
    }

    public function down(Schema $schema): void
    {
        // This migration is a no-op - nothing to undo
    }
}
