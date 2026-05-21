<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260520190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Track whether an order has already applied its stock deduction';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders ADD stock_deducted TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('UPDATE orders SET stock_deducted = 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders DROP stock_deducted');
    }
}
