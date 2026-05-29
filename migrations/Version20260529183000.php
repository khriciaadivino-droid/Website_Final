<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260529183000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add push token support for customer device notifications';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->getTable('user')->hasColumn('push_token')) {
            $this->addSql('ALTER TABLE `user` ADD push_token VARCHAR(255) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP push_token');
    }
}
