<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240929195051 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE store_address (id INT AUTO_INCREMENT NOT NULL, vendor_id INT DEFAULT NULL, title VARCHAR(45) DEFAULT NULL, street_name VARCHAR(500) DEFAULT NULL, land_mark VARCHAR(255) DEFAULT NULL, town VARCHAR(255) DEFAULT NULL, area VARCHAR(50) DEFAULT NULL, floor VARCHAR(100) DEFAULT NULL, apartment VARCHAR(5) DEFAULT NULL, postal_code VARCHAR(10) DEFAULT NULL, mobile_number VARCHAR(20) NOT NULL, full_address LONGTEXT DEFAULT NULL, deleted DATETIME DEFAULT NULL, deleted_by VARCHAR(255) DEFAULT NULL, created DATETIME NOT NULL, creator VARCHAR(255) NOT NULL, modified DATETIME NOT NULL, modified_by VARCHAR(255) NOT NULL, INDEX IDX_14464E66F603EE73 (vendor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE vendor (id INT AUTO_INCREMENT NOT NULL, seo_id INT DEFAULT NULL, post_id INT DEFAULT NULL, image_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, publish TINYINT(1) NOT NULL, deleted DATETIME DEFAULT NULL, deleted_by VARCHAR(255) DEFAULT NULL, created DATETIME NOT NULL, creator VARCHAR(255) NOT NULL, modified DATETIME NOT NULL, modified_by VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_F52233F697E3DD86 (seo_id), UNIQUE INDEX UNIQ_F52233F64B89032C (post_id), UNIQUE INDEX UNIQ_F52233F63DA5256D (image_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE vendor_translations (translatable_id INT NOT NULL, language_id INT NOT NULL, title VARCHAR(100) NOT NULL, INDEX IDX_6DB5AB5F2C2AC5D3 (translatable_id), INDEX IDX_6DB5AB5F82F1BAF4 (language_id), PRIMARY KEY(translatable_id, language_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE store_address ADD CONSTRAINT FK_14464E66F603EE73 FOREIGN KEY (vendor_id) REFERENCES vendor (id)');
        $this->addSql('ALTER TABLE vendor ADD CONSTRAINT FK_F52233F697E3DD86 FOREIGN KEY (seo_id) REFERENCES seo (id)');
        $this->addSql('ALTER TABLE vendor ADD CONSTRAINT FK_F52233F64B89032C FOREIGN KEY (post_id) REFERENCES post (id)');
        $this->addSql('ALTER TABLE vendor ADD CONSTRAINT FK_F52233F63DA5256D FOREIGN KEY (image_id) REFERENCES image (id)');
        $this->addSql('ALTER TABLE vendor_translations ADD CONSTRAINT FK_6DB5AB5F2C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES vendor (id)');
        $this->addSql('ALTER TABLE vendor_translations ADD CONSTRAINT FK_6DB5AB5F82F1BAF4 FOREIGN KEY (language_id) REFERENCES language (id)');
        $this->addSql('ALTER TABLE product ADD vendor_id INT DEFAULT NULL, ADD store_address_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04ADF603EE73 FOREIGN KEY (vendor_id) REFERENCES vendor (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04ADDB5EF498 FOREIGN KEY (store_address_id) REFERENCES store_address (id)');
        $this->addSql('CREATE INDEX IDX_D34A04ADF603EE73 ON product (vendor_id)');
        $this->addSql('CREATE INDEX IDX_D34A04ADDB5EF498 ON product (store_address_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04ADDB5EF498');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04ADF603EE73');
        $this->addSql('ALTER TABLE store_address DROP FOREIGN KEY FK_14464E66F603EE73');
        $this->addSql('ALTER TABLE vendor DROP FOREIGN KEY FK_F52233F697E3DD86');
        $this->addSql('ALTER TABLE vendor DROP FOREIGN KEY FK_F52233F64B89032C');
        $this->addSql('ALTER TABLE vendor DROP FOREIGN KEY FK_F52233F63DA5256D');
        $this->addSql('ALTER TABLE vendor_translations DROP FOREIGN KEY FK_6DB5AB5F2C2AC5D3');
        $this->addSql('ALTER TABLE vendor_translations DROP FOREIGN KEY FK_6DB5AB5F82F1BAF4');
        $this->addSql('DROP TABLE store_address');
        $this->addSql('DROP TABLE vendor');
        $this->addSql('DROP TABLE vendor_translations');
        $this->addSql('DROP INDEX IDX_D34A04ADF603EE73 ON product');
        $this->addSql('DROP INDEX IDX_D34A04ADDB5EF498 ON product');
        $this->addSql('ALTER TABLE product DROP vendor_id, DROP store_address_id');
    }
}
