<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260530120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store live revision counters in the database for reliable real-time updates on Railway';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('live_revision')) {
            $this->addSql('CREATE TABLE live_revision (domain VARCHAR(32) NOT NULL, revision INT NOT NULL DEFAULT 0, PRIMARY KEY(domain)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE live_revision');
    }
}
