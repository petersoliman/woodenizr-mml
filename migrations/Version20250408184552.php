<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250408184552 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_has_product_price ADD unit_price_before_commission DOUBLE PRECISION NOT NULL, ADD total_price_before_commission DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE vendor ADD commission_percentage DOUBLE PRECISION NOT NULL');
    }


    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE vendor DROP commission_percentage');
        $this->addSql('ALTER TABLE order_has_product_price DROP unit_price_before_commission, DROP total_price_before_commission');
    }
}
