<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250905153339 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE material DROP FOREIGN KEY FK_7CBE759512469DE2');
        $this->addSql('ALTER TABLE material DROP FOREIGN KEY FK_7CBE75954B89032C');
        $this->addSql('ALTER TABLE material_category_translations DROP FOREIGN KEY FK_5227BC222C2AC5D3');
        $this->addSql('ALTER TABLE material_category_translations DROP FOREIGN KEY FK_5227BC2282F1BAF4');
        $this->addSql('ALTER TABLE material_translations DROP FOREIGN KEY FK_C8CF4E872C2AC5D3');
        $this->addSql('ALTER TABLE material_translations DROP FOREIGN KEY FK_C8CF4E8782F1BAF4');
        $this->addSql('ALTER TABLE original_design DROP FOREIGN KEY FK_9E3379E04B89032C');
        $this->addSql('ALTER TABLE original_design DROP FOREIGN KEY FK_9E3379E097E3DD86');
        $this->addSql('ALTER TABLE original_design_translations DROP FOREIGN KEY FK_E57C69452C2AC5D3');
        $this->addSql('ALTER TABLE original_design_translations DROP FOREIGN KEY FK_E57C694582F1BAF4');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE4B89032C');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE97E3DD86');
        $this->addSql('ALTER TABLE project_product DROP FOREIGN KEY FK_455408C8166D1F9C');
        $this->addSql('ALTER TABLE project_product DROP FOREIGN KEY FK_455408C84584665A');
        $this->addSql('ALTER TABLE project_translations DROP FOREIGN KEY FK_EC103EE42C2AC5D3');
        $this->addSql('ALTER TABLE project_translations DROP FOREIGN KEY FK_EC103EE482F1BAF4');
        $this->addSql('ALTER TABLE team DROP FOREIGN KEY FK_C4E0A61F3DA5256D');
        $this->addSql('ALTER TABLE team_translations DROP FOREIGN KEY FK_CE31D7452C2AC5D3');
        $this->addSql('ALTER TABLE team_translations DROP FOREIGN KEY FK_CE31D74582F1BAF4');
        $this->addSql('ALTER TABLE testimonial_translations DROP FOREIGN KEY FK_9BE970A92C2AC5D3');
        $this->addSql('ALTER TABLE testimonial_translations DROP FOREIGN KEY FK_9BE970A982F1BAF4');
        $this->addSql('DROP TABLE material');
        $this->addSql('DROP TABLE material_category');
        $this->addSql('DROP TABLE material_category_translations');
        $this->addSql('DROP TABLE material_translations');
        $this->addSql('DROP TABLE original_design');
        $this->addSql('DROP TABLE original_design_translations');
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE project_product');
        $this->addSql('DROP TABLE project_translations');
        $this->addSql('DROP TABLE team');
        $this->addSql('DROP TABLE team_translations');
        $this->addSql('DROP TABLE testimonial');
        $this->addSql('DROP TABLE testimonial_translations');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE material (id INT AUTO_INCREMENT NOT NULL, category_id INT DEFAULT NULL, post_id INT DEFAULT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, tarteb SMALLINT DEFAULT NULL, publish TINYINT(1) NOT NULL, deleted DATETIME DEFAULT NULL, deleted_by VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, created DATETIME NOT NULL, creator VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, modified DATETIME NOT NULL, modified_by VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_7CBE759512469DE2 (category_id), UNIQUE INDEX UNIQ_7CBE75954B89032C (post_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE material_category (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, tarteb SMALLINT DEFAULT NULL, publish TINYINT(1) NOT NULL, deleted DATETIME DEFAULT NULL, deleted_by VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, created DATETIME NOT NULL, creator VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, modified DATETIME NOT NULL, modified_by VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE material_category_translations (translatable_id INT NOT NULL, language_id INT NOT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_5227BC222C2AC5D3 (translatable_id), INDEX IDX_5227BC2282F1BAF4 (language_id), PRIMARY KEY(translatable_id, language_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE material_translations (translatable_id INT NOT NULL, language_id INT NOT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_C8CF4E872C2AC5D3 (translatable_id), INDEX IDX_C8CF4E8782F1BAF4 (language_id), PRIMARY KEY(translatable_id, language_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE original_design (id INT AUTO_INCREMENT NOT NULL, seo_id INT DEFAULT NULL, post_id INT DEFAULT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, tarteb SMALLINT DEFAULT NULL, publish TINYINT(1) NOT NULL, featured TINYINT(1) NOT NULL, deleted DATETIME DEFAULT NULL, deleted_by VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, created DATETIME NOT NULL, creator VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, modified DATETIME NOT NULL, modified_by VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, UNIQUE INDEX UNIQ_9E3379E04B89032C (post_id), UNIQUE INDEX UNIQ_9E3379E097E3DD86 (seo_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE original_design_translations (translatable_id INT NOT NULL, language_id INT NOT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_E57C69452C2AC5D3 (translatable_id), INDEX IDX_E57C694582F1BAF4 (language_id), PRIMARY KEY(translatable_id, language_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE project (id INT AUTO_INCREMENT NOT NULL, post_id INT DEFAULT NULL, seo_id INT DEFAULT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, tarteb SMALLINT DEFAULT NULL, data JSON NOT NULL, featured TINYINT(1) NOT NULL, publish TINYINT(1) NOT NULL, deleted DATETIME DEFAULT NULL, deleted_by VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, created DATETIME NOT NULL, creator VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, modified DATETIME NOT NULL, modified_by VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, UNIQUE INDEX UNIQ_2FB3D0EE4B89032C (post_id), UNIQUE INDEX UNIQ_2FB3D0EE97E3DD86 (seo_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE project_product (project_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_455408C8166D1F9C (project_id), INDEX IDX_455408C84584665A (product_id), PRIMARY KEY(project_id, product_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE project_translations (translatable_id INT NOT NULL, language_id INT NOT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_EC103EE42C2AC5D3 (translatable_id), INDEX IDX_EC103EE482F1BAF4 (language_id), PRIMARY KEY(translatable_id, language_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE team (id INT AUTO_INCREMENT NOT NULL, image_id INT DEFAULT NULL, name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, position VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, tarteb SMALLINT DEFAULT NULL, publish TINYINT(1) NOT NULL, created DATETIME NOT NULL, creator VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, modified DATETIME NOT NULL, modified_by VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, deleted DATETIME DEFAULT NULL, deleted_by VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, UNIQUE INDEX UNIQ_C4E0A61F3DA5256D (image_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE team_translations (translatable_id INT NOT NULL, language_id INT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, position VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_CE31D7452C2AC5D3 (translatable_id), INDEX IDX_CE31D74582F1BAF4 (language_id), PRIMARY KEY(translatable_id, language_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE testimonial (id INT AUTO_INCREMENT NOT NULL, client VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, position VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, message LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, publish TINYINT(1) NOT NULL, created DATETIME NOT NULL, creator VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, modified DATETIME NOT NULL, modified_by VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, deleted DATETIME DEFAULT NULL, deleted_by VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE testimonial_translations (translatable_id INT NOT NULL, language_id INT NOT NULL, client VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, position VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, message TINYTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_9BE970A92C2AC5D3 (translatable_id), INDEX IDX_9BE970A982F1BAF4 (language_id), PRIMARY KEY(translatable_id, language_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE material ADD CONSTRAINT FK_7CBE759512469DE2 FOREIGN KEY (category_id) REFERENCES material_category (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE material ADD CONSTRAINT FK_7CBE75954B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE material_category_translations ADD CONSTRAINT FK_5227BC222C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES material_category (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE material_category_translations ADD CONSTRAINT FK_5227BC2282F1BAF4 FOREIGN KEY (language_id) REFERENCES language (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE material_translations ADD CONSTRAINT FK_C8CF4E872C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES material (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE material_translations ADD CONSTRAINT FK_C8CF4E8782F1BAF4 FOREIGN KEY (language_id) REFERENCES language (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE original_design ADD CONSTRAINT FK_9E3379E04B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE original_design ADD CONSTRAINT FK_9E3379E097E3DD86 FOREIGN KEY (seo_id) REFERENCES seo (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE original_design_translations ADD CONSTRAINT FK_E57C69452C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES original_design (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE original_design_translations ADD CONSTRAINT FK_E57C694582F1BAF4 FOREIGN KEY (language_id) REFERENCES language (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE4B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE97E3DD86 FOREIGN KEY (seo_id) REFERENCES seo (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_product ADD CONSTRAINT FK_455408C8166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_product ADD CONSTRAINT FK_455408C84584665A FOREIGN KEY (product_id) REFERENCES product (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_translations ADD CONSTRAINT FK_EC103EE42C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_translations ADD CONSTRAINT FK_EC103EE482F1BAF4 FOREIGN KEY (language_id) REFERENCES language (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE team ADD CONSTRAINT FK_C4E0A61F3DA5256D FOREIGN KEY (image_id) REFERENCES image (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE team_translations ADD CONSTRAINT FK_CE31D7452C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES team (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE team_translations ADD CONSTRAINT FK_CE31D74582F1BAF4 FOREIGN KEY (language_id) REFERENCES language (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE testimonial_translations ADD CONSTRAINT FK_9BE970A92C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES testimonial (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE testimonial_translations ADD CONSTRAINT FK_9BE970A982F1BAF4 FOREIGN KEY (language_id) REFERENCES language (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
