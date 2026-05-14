<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Data-safe legacy product table restore and backfill';
    }

    public function up(Schema $schema): void
    {
        // Recreate legacy tables only when missing.
        $this->addSql("CREATE TABLE IF NOT EXISTS products (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, price DOUBLE PRECISION NOT NULL, image VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE IF NOT EXISTS product (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT NOT NULL, price DOUBLE PRECISION NOT NULL, image VARCHAR(255) NOT NULL, category VARCHAR(100) NOT NULL, quantity VARCHAR(100) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE IF NOT EXISTS productpet (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, category VARCHAR(100) NOT NULL, quantity VARCHAR(100) NOT NULL, description VARCHAR(100) NOT NULL, price DOUBLE PRECISION NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        // Backfill only missing rows to avoid overwriting existing legacy data.
        $this->addSql("INSERT INTO products (id, name, price, image, description)
            SELECT p.id, p.name, p.price, COALESCE(p.imagefilename, ''), COALESCE(p.description, '')
            FROM productss p
            WHERE NOT EXISTS (
                SELECT 1 FROM products legacy WHERE legacy.id = p.id
            )");

        $this->addSql("INSERT INTO product (id, name, description, price, image, category, quantity)
            SELECT
                p.id,
                p.name,
                COALESCE(p.description, ''),
                p.price,
                COALESCE(p.imagefilename, ''),
                COALESCE(c.name, 'Uncategorized'),
                CAST(COALESCE(p.quantity, 0) AS CHAR)
            FROM productss p
            LEFT JOIN category c ON c.id = p.category_id
            WHERE NOT EXISTS (
                SELECT 1 FROM product legacy WHERE legacy.id = p.id
            )");
    }

    public function down(Schema $schema): void
    {
        // Intentionally left as no-op to avoid destructive rollback on legacy tables.
    }
}
