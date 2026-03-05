<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251213010736 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE shipment DROP FOREIGN KEY FK_2CB20DC7964396F');
        $this->addSql('ALTER TABLE shipment DROP FOREIGN KEY FK_2CB20DCE3D8151C');
        $this->addSql('DROP INDEX IDX_2CB20DCE3D8151C ON shipment');
        $this->addSql('DROP INDEX IDX_2CB20DC7964396F ON shipment');
        $this->addSql('ALTER TABLE shipment ADD shipping_zone VARCHAR(100) NOT NULL, ADD courier VARCHAR(100) NOT NULL, DROP shipping_zone_id, DROP courier_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE shipment ADD shipping_zone_id INT NOT NULL, ADD courier_id INT NOT NULL, DROP shipping_zone, DROP courier');
        $this->addSql('ALTER TABLE shipment ADD CONSTRAINT FK_2CB20DC7964396F FOREIGN KEY (shipping_zone_id) REFERENCES shipping_zone (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE shipment ADD CONSTRAINT FK_2CB20DCE3D8151C FOREIGN KEY (courier_id) REFERENCES courier (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_2CB20DCE3D8151C ON shipment (courier_id)');
        $this->addSql('CREATE INDEX IDX_2CB20DC7964396F ON shipment (shipping_zone_id)');
    }
}
