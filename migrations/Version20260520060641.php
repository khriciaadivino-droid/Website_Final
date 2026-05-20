<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260520060641 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE products DROP FOREIGN KEY products_ibfk_1');
        $this->addSql('DROP TABLE categories');
        $this->addSql('DROP TABLE pets');
        $this->addSql('DROP TABLE products');
        $this->addSql('ALTER TABLE orders ADD fulfillment_type VARCHAR(20) DEFAULT NULL, ADD delivery_address LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE productss RENAME INDEX fk_9003cdbbb03a8386 TO IDX_9003CDBBB03A8386');
        $this->addSql('ALTER TABLE user RENAME INDEX google_id TO UNIQ_8D93D64976F5C865');
        $this->addSql('ALTER TABLE verification_token DROP FOREIGN KEY verification_token_ibfk_1');
        $this->addSql('ALTER TABLE verification_token RENAME INDEX token TO UNIQ_C1CC006B5F37A13B');
        $this->addSql('ALTER TABLE verification_token RENAME INDEX fk_c1cc006ba76ed395 TO IDX_C1CC006BA76ED395');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE categories (id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, icon VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'?\' COLLATE `utf8mb4_0900_ai_ci`, createdAt DATETIME DEFAULT NULL, updatedAt DATETIME DEFAULT NULL, UNIQUE INDEX name (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE pets (id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, species VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, breed VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, age INT DEFAULT NULL, ownerName VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, image VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, isPetOfTheMonth TINYINT(1) DEFAULT 0, createdAt DATETIME DEFAULT NULL, updatedAt DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE products (id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, price DOUBLE PRECISION NOT NULL, quantity INT DEFAULT 0, categoryId CHAR(36) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_bin`, image VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, createdAt DATETIME DEFAULT NULL, updatedAt DATETIME DEFAULT NULL, INDEX categoryId (categoryId), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE products ADD CONSTRAINT products_ibfk_1 FOREIGN KEY (categoryId) REFERENCES categories (id) ON UPDATE CASCADE ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE orders DROP fulfillment_type, DROP delivery_address');
        $this->addSql('ALTER TABLE productss RENAME INDEX idx_9003cdbbb03a8386 TO FK_9003CDBBB03A8386');
        $this->addSql('ALTER TABLE `user` RENAME INDEX uniq_8d93d64976f5c865 TO google_id');
        $this->addSql('ALTER TABLE verification_token RENAME INDEX uniq_c1cc006b5f37a13b TO token');
        $this->addSql('ALTER TABLE verification_token RENAME INDEX idx_c1cc006ba76ed395 TO FK_C1CC006BA76ED395');
    }
}
