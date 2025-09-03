<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250824150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add dimensions fields (length, width, height) to product_price table';
    }

    public function up(Schema $schema): void
    {
        // Add dimensions fields to product_price table
        $this->addSql('ALTER TABLE product_price ADD length DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE product_price ADD width DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE product_price ADD height DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Remove dimensions fields from product_price table
        $this->addSql('ALTER TABLE product_price DROP length');
        $this->addSql('ALTER TABLE product_price DROP width');
        $this->addSql('ALTER TABLE product_price DROP height');
    }
}
