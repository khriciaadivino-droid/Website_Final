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
        $schemaManager = $this->connection->createSchemaManager();

        $hasOrders = $schemaManager->tablesExist(['orders']);
        $hasFulfillmentType = $hasOrders && $this->hasColumn($schemaManager, 'orders', 'fulfillment_type');
        $hasDeliveryAddress = $hasOrders && $this->hasColumn($schemaManager, 'orders', 'delivery_address');

        $hasProductss = $schemaManager->tablesExist(['productss']);
        $hasCreatedById = $hasProductss && $this->hasColumn($schemaManager, 'productss', 'created_by_id');
        $hasProductssIndex = $hasProductss && $this->hasIndex($schemaManager, 'productss', 'IDX_9003CDBBB03A8386');
        $hasLegacyProductssIndex = $hasProductss && $this->hasIndex($schemaManager, 'productss', 'fk_9003cdbbb03a8386');

        $hasUser = $schemaManager->tablesExist(['user']);
        $hasGoogleId = $hasUser && $this->hasColumn($schemaManager, 'user', 'google_id');
        $hasGoogleIdIndex = $hasUser && $this->hasIndex($schemaManager, 'user', 'UNIQ_8D93D64976F5C865');
        $hasLegacyGoogleIdIndex = $hasUser && $this->hasIndex($schemaManager, 'user', 'google_id');

        $hasVerificationToken = $schemaManager->tablesExist(['verification_token']);
        $hasVerificationTokenFk = $hasVerificationToken && $this->hasForeignKey($schemaManager, 'verification_token', 'FK_C1CC006BA76ED395');
        $hasLegacyVerificationTokenFk = $hasVerificationToken && $this->hasForeignKey($schemaManager, 'verification_token', 'verification_token_ibfk_1');
        $hasTokenColumn = $hasVerificationToken && $this->hasColumn($schemaManager, 'verification_token', 'token');
        $hasUserIdColumn = $hasVerificationToken && $this->hasColumn($schemaManager, 'verification_token', 'user_id');
        $hasVerificationTokenIndex = $hasVerificationToken && $this->hasIndex($schemaManager, 'verification_token', 'UNIQ_C1CC006B5F37A13B');
        $hasLegacyVerificationTokenIndex = $hasVerificationToken && $this->hasIndex($schemaManager, 'verification_token', 'token');
        $hasVerificationUserIdIndex = $hasVerificationToken && $this->hasIndex($schemaManager, 'verification_token', 'IDX_C1CC006BA76ED395');
        $hasLegacyVerificationUserIdIndex = $hasVerificationToken && $this->hasIndex($schemaManager, 'verification_token', 'fk_c1cc006ba76ed395');

        $this->addSql('DROP TABLE IF EXISTS products');
        $this->addSql('DROP TABLE IF EXISTS categories');
        $this->addSql('DROP TABLE IF EXISTS pets');

        if ($hasOrders) {
            $columnsToAdd = [];
            if (!$hasFulfillmentType) {
                $columnsToAdd[] = 'ADD fulfillment_type VARCHAR(20) DEFAULT NULL';
            }
            if (!$hasDeliveryAddress) {
                $columnsToAdd[] = 'ADD delivery_address LONGTEXT DEFAULT NULL';
            }
            if ($columnsToAdd !== []) {
                $this->addSql('ALTER TABLE orders ' . implode(', ', $columnsToAdd));
            }
        }

        if ($hasProductss) {
            if ($hasLegacyProductssIndex && !$hasProductssIndex) {
                $this->addSql('ALTER TABLE productss RENAME INDEX fk_9003cdbbb03a8386 TO IDX_9003CDBBB03A8386');
            } elseif ($hasCreatedById && !$hasProductssIndex) {
                $this->addSql('CREATE INDEX IDX_9003CDBBB03A8386 ON productss (created_by_id)');
            }
        }

        if ($hasUser) {
            if ($hasLegacyGoogleIdIndex && !$hasGoogleIdIndex) {
                $this->addSql('ALTER TABLE `user` RENAME INDEX google_id TO UNIQ_8D93D64976F5C865');
            } elseif ($hasGoogleId && !$hasGoogleIdIndex) {
                $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D64976F5C865 ON `user` (google_id)');
            }
        }

        if ($hasVerificationToken) {
            if ($hasLegacyVerificationTokenFk) {
                $this->addSql('ALTER TABLE verification_token DROP FOREIGN KEY verification_token_ibfk_1');
            }

            if ($hasLegacyVerificationTokenIndex && !$hasVerificationTokenIndex) {
                $this->addSql('ALTER TABLE verification_token RENAME INDEX token TO UNIQ_C1CC006B5F37A13B');
            } elseif ($hasTokenColumn && !$hasVerificationTokenIndex) {
                $this->addSql('CREATE UNIQUE INDEX UNIQ_C1CC006B5F37A13B ON verification_token (token)');
            }

            if ($hasLegacyVerificationUserIdIndex && !$hasVerificationUserIdIndex) {
                $this->addSql('ALTER TABLE verification_token RENAME INDEX fk_c1cc006ba76ed395 TO IDX_C1CC006BA76ED395');
            } elseif ($hasUserIdColumn && !$hasVerificationUserIdIndex) {
                $this->addSql('CREATE INDEX IDX_C1CC006BA76ED395 ON verification_token (user_id)');
            }

            if (!$hasVerificationTokenFk && $hasUserIdColumn && $hasUser) {
                $this->addSql('ALTER TABLE verification_token ADD CONSTRAINT FK_C1CC006BA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
            }
        }
    }

    private function hasColumn($schemaManager, string $table, string $column): bool
    {
        $columns = array_change_key_case($schemaManager->listTableColumns($table), CASE_LOWER);

        return array_key_exists(strtolower($column), $columns);
    }

    private function hasForeignKey($schemaManager, string $table, string $foreignKeyName): bool
    {
        foreach ($schemaManager->listTableForeignKeys($table) as $foreignKey) {
            if (strcasecmp($foreignKey->getName(), $foreignKeyName) === 0) {
                return true;
            }
        }

        return false;
    }

    private function hasIndex($schemaManager, string $table, string $indexName): bool
    {
        foreach ($schemaManager->listTableIndexes($table) as $index) {
            if (strcasecmp($index->getName(), $indexName) === 0) {
                return true;
            }
        }

        return false;
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
