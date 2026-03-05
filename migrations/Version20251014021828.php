<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251014021828 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE productss DROP FOREIGN KEY FK_9003CDBB12469DE2');
        $this->addSql('DROP INDEX IDX_9003CDBB12469DE2 ON productss');
        $this->addSql('ALTER TABLE productss ADD category VARCHAR(255) NOT NULL, DROP category_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE productss ADD category_id INT DEFAULT NULL, DROP category');
        $this->addSql('ALTER TABLE productss ADD CONSTRAINT FK_9003CDBB12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_9003CDBB12469DE2 ON productss (category_id)');
    }
}
