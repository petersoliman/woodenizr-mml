<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240910194833 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE brand (id INT AUTO_INCREMENT NOT NULL, seo_id INT DEFAULT NULL, post_id INT DEFAULT NULL, jp_id VARCHAR(45) DEFAULT NULL, title VARCHAR(45) NOT NULL, publish TINYINT(1) NOT NULL, featured TINYINT(1) NOT NULL, tarteb SMALLINT DEFAULT 0, deleted DATETIME DEFAULT NULL, deleted_by VARCHAR(255) DEFAULT NULL, created DATETIME NOT NULL, creator VARCHAR(255) NOT NULL, modified DATETIME NOT NULL, modified_by VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_1C52F95897E3DD86 (seo_id), UNIQUE INDEX UNIQ_1C52F9584B89032C (post_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE brand_translations (translatable_id INT NOT NULL, language_id INT NOT NULL, title VARCHAR(100) NOT NULL, INDEX IDX_B018D342C2AC5D3 (translatable_id), INDEX IDX_B018D3482F1BAF4 (language_id), PRIMARY KEY(translatable_id, language_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE brand ADD CONSTRAINT FK_1C52F95897E3DD86 FOREIGN KEY (seo_id) REFERENCES seo (id)');
        $this->addSql('ALTER TABLE brand ADD CONSTRAINT FK_1C52F9584B89032C FOREIGN KEY (post_id) REFERENCES post (id)');
        $this->addSql('ALTER TABLE brand_translations ADD CONSTRAINT FK_B018D342C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES brand (id)');
        $this->addSql('ALTER TABLE brand_translations ADD CONSTRAINT FK_B018D3482F1BAF4 FOREIGN KEY (language_id) REFERENCES language (id)');
        $this->addSql('ALTER TABLE blog CHANGE subtitle subtitle VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD brand_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD44F5D008 FOREIGN KEY (brand_id) REFERENCES brand (id)');
        $this->addSql('CREATE INDEX IDX_D34A04AD44F5D008 ON product (brand_id)');
        $this->addSql('ALTER TABLE product_search ADD brand_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product_search ADD CONSTRAINT FK_D68C9A0344F5D008 FOREIGN KEY (brand_id) REFERENCES brand (id)');
        $this->addSql('CREATE INDEX IDX_D68C9A0344F5D008 ON product_search (brand_id)');
        $this->addSql('ALTER TABLE product_price ADD weight DOUBLE PRECISION DEFAULT NULL');


        $this->addSql("INSERT INTO image_setting (id, entity_name, back_route, upload_path, auto_resize, quality, gallery, created, creator, modified, modified_by) VALUES (10, 'Brand', 'brand_index', 'brand/', 1, 1, 0, NOW(), 'System', NOW(), 'System');");
        $this->addSql("INSERT INTO image_setting_has_type (radio_button, width, height, thumb_width, thumb_height, validate_width_and_height, validate_size, image_type_id, image_setting_id) VALUES (1, 1000, 1000, NULL, NULL, 0, 1, 1, 10);");
        $this->addSql("INSERT INTO image_setting_has_type (radio_button, width, height, thumb_width, thumb_height, validate_width_and_height, validate_size, image_type_id, image_setting_id) VALUES (1, 1920, 300, NULL, NULL, 0, 1, 3, 10);");

        $this->addSql('INSERT INTO seo_base_route (entity_name, base_route, created, creator, modified, modified_by) VALUES ("App\\\ProductBundle\\\Entity\\\Brand", "brand", NOW(), "System", NOW(), "System");');
        $this->addSql('INSERT INTO seo_base_route (entity_name, base_route, created, creator, modified, modified_by) VALUES ("App\\\ProductBundle\\\Entity\\\Product", "product", NOW(), "System", NOW(), "System");');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD44F5D008');
        $this->addSql('ALTER TABLE product_search DROP FOREIGN KEY FK_D68C9A0344F5D008');
        $this->addSql('ALTER TABLE brand DROP FOREIGN KEY FK_1C52F95897E3DD86');
        $this->addSql('ALTER TABLE brand DROP FOREIGN KEY FK_1C52F9584B89032C');
        $this->addSql('ALTER TABLE brand_translations DROP FOREIGN KEY FK_B018D342C2AC5D3');
        $this->addSql('ALTER TABLE brand_translations DROP FOREIGN KEY FK_B018D3482F1BAF4');
        $this->addSql('DROP TABLE brand');
        $this->addSql('DROP TABLE brand_translations');
        $this->addSql('ALTER TABLE blog CHANGE subtitle subtitle VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX IDX_D34A04AD44F5D008 ON product');
        $this->addSql('ALTER TABLE product DROP brand_id');
        $this->addSql('DROP INDEX IDX_D68C9A0344F5D008 ON product_search');
        $this->addSql('ALTER TABLE product_search DROP brand_id');
        $this->addSql('ALTER TABLE product_price DROP weight');


        $this->addSql('DELETE FROM image_setting WHERE id = 10');
        $this->addSql('DELETE FROM image_setting_has_type WHERE image_setting_id = 10');
        $this->addSql('DELETE FROM seo_base_route WHERE entity_name = "App\\ProductBundle\\Entity\\Brand"');

    }
}
