<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250830140953 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE product_cgd (id INT AUTO_INCREMENT NOT NULL, category_id INT NOT NULL, brand_id INT DEFAULT NULL, created_by INT DEFAULT NULL, approved_by INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, images JSON DEFAULT NULL, metadata JSON DEFAULT NULL, sku VARCHAR(255) DEFAULT NULL, price NUMERIC(10, 2) DEFAULT NULL, url VARCHAR(500) DEFAULT NULL, technical_specs JSON DEFAULT NULL, status VARCHAR(20) DEFAULT \'pending\' NOT NULL, approved_at DATETIME DEFAULT NULL, rejection_reason LONGTEXT DEFAULT NULL, batch_id VARCHAR(255) DEFAULT NULL, external_data JSON DEFAULT NULL, deleted DATETIME DEFAULT NULL, deleted_by VARCHAR(255) DEFAULT NULL, created DATETIME NOT NULL, creator VARCHAR(255) NOT NULL, modified DATETIME NOT NULL, modified_by VARCHAR(255) NOT NULL, uuid VARCHAR(50) NOT NULL, UNIQUE INDEX UNIQ_359CA055D17F50A6 (uuid), INDEX IDX_359CA05512469DE2 (category_id), INDEX IDX_359CA05544F5D008 (brand_id), INDEX IDX_359CA055DE12AB56 (created_by), INDEX IDX_359CA0554EA3CB3D (approved_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE product_cgd ADD CONSTRAINT FK_359CA05512469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_cgd ADD CONSTRAINT FK_359CA05544F5D008 FOREIGN KEY (brand_id) REFERENCES brand (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE product_cgd ADD CONSTRAINT FK_359CA055DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE product_cgd ADD CONSTRAINT FK_359CA0554EA3CB3D FOREIGN KEY (approved_by) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_cgd DROP FOREIGN KEY FK_359CA05512469DE2');
        $this->addSql('ALTER TABLE product_cgd DROP FOREIGN KEY FK_359CA05544F5D008');
        $this->addSql('ALTER TABLE product_cgd DROP FOREIGN KEY FK_359CA055DE12AB56');
        $this->addSql('ALTER TABLE product_cgd DROP FOREIGN KEY FK_359CA0554EA3CB3D');
        $this->addSql('DROP TABLE product_cgd');
    }
}
