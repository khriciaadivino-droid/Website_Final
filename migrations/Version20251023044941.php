<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251023044941 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Convert existing string dates to DATE format
        $this->addSql("UPDATE pet_profile_management SET dateofbirth = STR_TO_DATE(dateofbirth, '%m/%d/%Y') WHERE dateofbirth IS NOT NULL AND dateofbirth != ''");
        // Change column type
        $this->addSql('ALTER TABLE pet_profile_management CHANGE dateofbirth dateofbirth DATE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pet_profile_management CHANGE dateofbirth dateofbirth VARCHAR(255) NOT NULL');
    }
}
