<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250824144850 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename zone_id to city_id in store_address table and update foreign key to reference city table';
    }

    public function up(Schema $schema): void
    {
        // Drop the existing foreign key constraint
        $this->addSql('ALTER TABLE store_address DROP FOREIGN KEY FK_14464E669F2C3FAB');
        
        // Rename the column from zone_id to city_id
        $this->addSql('ALTER TABLE store_address CHANGE zone_id city_id INT DEFAULT NULL');
        
        // Add new foreign key constraint to reference city table
        $this->addSql('ALTER TABLE store_address ADD CONSTRAINT FK_14464E669F2C3FAB FOREIGN KEY (city_id) REFERENCES city (id)');
    }

    public function down(Schema $schema): void
    {
        // Drop the foreign key constraint to city table
        $this->addSql('ALTER TABLE store_address DROP FOREIGN KEY FK_14464E669F2C3FAB');
        
        // Rename the column back from city_id to zone_id
        $this->addSql('ALTER TABLE store_address CHANGE city_id zone_id INT DEFAULT NULL');
        
        // Restore the original foreign key constraint to zone table
        $this->addSql('ALTER TABLE store_address ADD CONSTRAINT FK_14464E669F2C3FAB FOREIGN KEY (zone_id) REFERENCES zone (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
