<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250824145123 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Revert store_address table: change city_id back to zone_id and restore foreign key to zone table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE store_address DROP FOREIGN KEY FK_14464E669F2C3FAB');
        $this->addSql('DROP INDEX IDX_14464E669F2C3FAB ON store_address');
        $this->addSql('ALTER TABLE store_address CHANGE city_id zone_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE store_address ADD CONSTRAINT FK_14464E669F2C3FAB FOREIGN KEY (zone_id) REFERENCES zone (id)');
        $this->addSql('CREATE INDEX IDX_14464E669F2C3FAB ON store_address (zone_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE store_address DROP FOREIGN KEY FK_14464E669F2C3FAB');
        $this->addSql('DROP INDEX IDX_14464E669F2C3FAB ON store_address');
        $this->addSql('ALTER TABLE store_address CHANGE zone_id city_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE store_address ADD CONSTRAINT FK_14464E669F2C3FAB FOREIGN KEY (city_id) REFERENCES city (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_14464E669F2C3FAB ON store_address (city_id)');
    }
}
