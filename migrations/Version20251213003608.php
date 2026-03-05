<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251213003608 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE productss DROP FOREIGN KEY FK_9003CDBBB03A8386');
        $this->addSql('ALTER TABLE productss ADD CONSTRAINT FK_9003CDBBB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE productss DROP FOREIGN KEY FK_9003CDBBB03A8386');
        $this->addSql('ALTER TABLE productss ADD CONSTRAINT FK_9003CDBBB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
