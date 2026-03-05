<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251015171211 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stocks ADD productss_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE stocks ADD CONSTRAINT FK_56F7980548172CE8 FOREIGN KEY (productss_id) REFERENCES productss (id)');
        $this->addSql('CREATE INDEX IDX_56F7980548172CE8 ON stocks (productss_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stocks DROP FOREIGN KEY FK_56F7980548172CE8');
        $this->addSql('DROP INDEX IDX_56F7980548172CE8 ON stocks');
        $this->addSql('ALTER TABLE stocks DROP productss_id');
    }
}
