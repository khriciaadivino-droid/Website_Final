<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251212045447 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // First add the column as nullable
        $this->addSql('ALTER TABLE pet_profile_management ADD owner_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pet_profile_management ADD CONSTRAINT FK_9AC1397B7E3C61F9 FOREIGN KEY (owner_id) REFERENCES pet_owners (id)');
        $this->addSql('CREATE INDEX IDX_9AC1397B7E3C61F9 ON pet_profile_management (owner_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pet_profile_management DROP FOREIGN KEY FK_9AC1397B7E3C61F9');
        $this->addSql('DROP INDEX IDX_9AC1397B7E3C61F9 ON pet_profile_management');
        $this->addSql('ALTER TABLE pet_profile_management DROP owner_id');
    }
}
