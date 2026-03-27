<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260325053301 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE IF NOT EXISTS activity_log (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, username VARCHAR(255) NOT NULL, role VARCHAR(50) NOT NULL, action VARCHAR(50) NOT NULL, target_data LONGTEXT DEFAULT NULL, timestamp DATETIME NOT NULL, INDEX idx_user_id (user_id), INDEX idx_action (action), INDEX idx_timestamp (timestamp), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS customer (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(100) NOT NULL, phone_number VARCHAR(20) DEFAULT NULL, address LONGTEXT DEFAULT NULL, city VARCHAR(100) DEFAULT NULL, postal_code VARCHAR(20) DEFAULT NULL, country VARCHAR(100) DEFAULT NULL, registration_date DATETIME NOT NULL, last_purchase_date DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS dashboard (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS orders (id INT AUTO_INCREMENT NOT NULL, product_id INT DEFAULT NULL, order_number VARCHAR(50) NOT NULL, customer_name VARCHAR(100) NOT NULL, customer_email VARCHAR(100) DEFAULT NULL, order_date DATETIME NOT NULL, quantity INT NOT NULL, total_amount DOUBLE PRECISION NOT NULL, status VARCHAR(50) NOT NULL, INDEX IDX_E52FFDEE4584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS pet_owners (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(100) NOT NULL, phone_number VARCHAR(20) DEFAULT NULL, address LONGTEXT DEFAULT NULL, registration_date DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS pet_profile_management (id INT AUTO_INCREMENT NOT NULL, owner_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, species VARCHAR(255) NOT NULL, breed VARCHAR(255) NOT NULL, age DOUBLE PRECISION NOT NULL, dateofbirth DATE DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, is_pet_of_the_month TINYINT(1) DEFAULT 0 NOT NULL, INDEX IDX_9AC1397B7E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS productss (id INT AUTO_INCREMENT NOT NULL, category_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(100) NOT NULL, price DOUBLE PRECISION NOT NULL, imagefilename VARCHAR(255) DEFAULT NULL, quantity INT NOT NULL, INDEX IDX_9003CDBB12469DE2 (category_id), INDEX IDX_9003CDBBB03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS stocks (id INT AUTO_INCREMENT NOT NULL, productss_id INT NOT NULL, create_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', update_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', stock_change_log LONGTEXT NOT NULL, quantity_change DOUBLE PRECISION DEFAULT NULL, INDEX IDX_56F7980548172CE8 (productss_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, full_name VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL, last_login_at DATETIME DEFAULT NULL, status VARCHAR(20) NOT NULL, created_by VARCHAR(100) DEFAULT NULL, verified_at DATETIME DEFAULT NULL, google_id VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), UNIQUE INDEX UNIQ_8D93D64976F5C865 (google_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS verification_token (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, token VARCHAR(255) NOT NULL, expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL, used_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_C1CC006B5F37A13B (token), INDEX IDX_C1CC006BA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE4584665A FOREIGN KEY (product_id) REFERENCES productss (id)');
        $this->addSql('ALTER TABLE pet_profile_management ADD CONSTRAINT FK_9AC1397B7E3C61F9 FOREIGN KEY (owner_id) REFERENCES pet_owners (id)');
        $this->addSql('ALTER TABLE productss ADD CONSTRAINT FK_9003CDBB12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE productss ADD CONSTRAINT FK_9003CDBBB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE stocks ADD CONSTRAINT FK_56F7980548172CE8 FOREIGN KEY (productss_id) REFERENCES productss (id)');
        $this->addSql('ALTER TABLE verification_token ADD CONSTRAINT FK_C1CC006BA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE IF EXISTS products');
        $this->addSql('DROP TABLE IF EXISTS product');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE IF NOT EXISTS products (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, price DOUBLE PRECISION NOT NULL, image VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE IF NOT EXISTS product (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, price DOUBLE PRECISION NOT NULL, image VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE4584665A');
        $this->addSql('ALTER TABLE pet_profile_management DROP FOREIGN KEY FK_9AC1397B7E3C61F9');
        $this->addSql('ALTER TABLE productss DROP FOREIGN KEY FK_9003CDBB12469DE2');
        $this->addSql('ALTER TABLE productss DROP FOREIGN KEY FK_9003CDBBB03A8386');
        $this->addSql('ALTER TABLE stocks DROP FOREIGN KEY FK_56F7980548172CE8');
        $this->addSql('ALTER TABLE verification_token DROP FOREIGN KEY FK_C1CC006BA76ED395');
        $this->addSql('DROP TABLE IF EXISTS activity_log');
        $this->addSql('DROP TABLE IF EXISTS category');
        $this->addSql('DROP TABLE IF EXISTS customer');
        $this->addSql('DROP TABLE IF EXISTS dashboard');
        $this->addSql('DROP TABLE IF EXISTS orders');
        $this->addSql('DROP TABLE IF EXISTS pet_owners');
        $this->addSql('DROP TABLE IF EXISTS pet_profile_management');
        $this->addSql('DROP TABLE IF EXISTS productss');
        $this->addSql('DROP TABLE IF EXISTS stocks');
        $this->addSql('DROP TABLE IF EXISTS `user`');
        $this->addSql('DROP TABLE IF EXISTS verification_token');
    }
}
