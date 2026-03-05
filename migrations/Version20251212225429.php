<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251212225429 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop payments table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE payments');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE payments (id INT AUTO_INCREMENT NOT NULL, payment_reference VARCHAR(255) NOT NULL, customer_name VARCHAR(255) NOT NULL, amount NUMERIC(10, 2) NOT NULL, payment_method VARCHAR(255) NOT NULL, payment_date DATETIME NOT NULL, status VARCHAR(255) NOT NULL, notes LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }
}
