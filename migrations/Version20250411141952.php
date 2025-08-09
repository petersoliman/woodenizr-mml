<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250411141952 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cart_has_product_price ADD shipping_time_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE cart_has_product_price ADD CONSTRAINT FK_D90A9B5CB8A2DBFF FOREIGN KEY (shipping_time_id) REFERENCES shipping_time (id)');
        $this->addSql('CREATE INDEX IDX_D90A9B5CB8A2DBFF ON cart_has_product_price (shipping_time_id)');
        $this->addSql('ALTER TABLE `order` ADD parent_id INT DEFAULT NULL, ADD shipping_time_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398727ACA70 FOREIGN KEY (parent_id) REFERENCES `order` (id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398B8A2DBFF FOREIGN KEY (shipping_time_id) REFERENCES shipping_time (id)');
        $this->addSql('CREATE INDEX IDX_F5299398727ACA70 ON `order` (parent_id)');
        $this->addSql('CREATE INDEX IDX_F5299398B8A2DBFF ON `order` (shipping_time_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398727ACA70');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398B8A2DBFF');
        $this->addSql('DROP INDEX IDX_F5299398727ACA70 ON `order`');
        $this->addSql('DROP INDEX IDX_F5299398B8A2DBFF ON `order`');
        $this->addSql('ALTER TABLE `order` DROP parent_id, DROP shipping_time_id');
        $this->addSql('ALTER TABLE cart_has_product_price DROP FOREIGN KEY FK_D90A9B5CB8A2DBFF');
        $this->addSql('DROP INDEX IDX_D90A9B5CB8A2DBFF ON cart_has_product_price');
        $this->addSql('ALTER TABLE cart_has_product_price DROP shipping_time_id');
    }
}
