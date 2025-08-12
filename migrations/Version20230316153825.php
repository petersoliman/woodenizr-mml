<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\OnlinePaymentBundle\Enum\PaymentMethodEnum;
use App\SeoBundle\Entity\Seo;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use PN\SeoBundle\Entity\SeoBaseRoute;
use PN\SeoBundle\Entity\SeoPage;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230316153825 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    public function getDescription(): string
    {
        return 'Initial Values for C-Reality';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO `payment_method` (`title`, `note`, `type`, `active`, `fees`, `deleted`, `deleted_by`, `created`, `creator`, `modified`, `modified_by`) VALUES ('ValU', NULL, :type, '1', '0', NULL, NULL, NOW(), 'System', NOW(), 'System')",
            ["type" => PaymentMethodEnum::ValU->value]
        );

        $this->addSql("INSERT INTO image_setting (id, entity_name, back_route, upload_path, auto_resize, quality, gallery, created, creator, modified, modified_by) VALUES (8, 'OriginalDesign', 'original_design_edit', 'original-design/', 1, 1, 0, NOW(), 'System', NOW(), 'System');");
        $this->addSql("INSERT INTO image_setting_has_type (radio_button, width, height, thumb_width, thumb_height, validate_width_and_height, validate_size, image_type_id, image_setting_id) VALUES (1, 1000, 1000, NULL, NULL, 0, 1, 1, 8);");
        $this->addSql("INSERT INTO image_setting_has_type (radio_button, width, height, thumb_width, thumb_height, validate_width_and_height, validate_size, image_type_id, image_setting_id) VALUES (0, 1000, 1000, NULL, NULL, 0, 1, 2, 8);");


        $this->addSql("INSERT INTO image_setting (id, entity_name, back_route, upload_path, auto_resize, quality, gallery, created, creator, modified, modified_by) VALUES (9, 'Material', 'material_category_index', 'material/', 1, 1, 0, NOW(), 'System', NOW(), 'System');");
        $this->addSql("INSERT INTO image_setting_has_type (radio_button, width, height, thumb_width, thumb_height, validate_width_and_height, validate_size, image_type_id, image_setting_id) VALUES (1, 1000, 1000, NULL, NULL, 0, 1, 1, 9);");

        // DYNAMIC CONTENT
        $this->addSql("INSERT INTO `dynamic_content` (`id`, `title`) VALUES (2, 'Home Page')");
        $this->addSql("INSERT INTO `dynamic_content_attribute` (`id`, `dynamic_content_id`, `title`, `value`, `type`, `hint`, `image_width`, `image_height`) VALUES (11, 2, 'Info Section Title', 'GOOD DESIGN IS ALL ABOUT STORYTELLING!', 1, NULL, NULL, NULL);");
        $this->addSql("INSERT INTO `dynamic_content_attribute` (`id`, `dynamic_content_id`, `title`, `value`, `type`, `hint`, `image_width`, `image_height`) VALUES (12, 2, 'Info Section #1 Image', NULL, 4, '500px * 300px', 500, 300);");
        $this->addSql("INSERT INTO `dynamic_content_attribute` (`id`, `dynamic_content_id`, `title`, `value`, `type`, `hint`, `image_width`, `image_height`) VALUES (13, 2, 'Info Section #1 Title', 'WHO WE ARE', 1, NULL, NULL, NULL);");
        $this->addSql("INSERT INTO `dynamic_content_attribute` (`id`, `dynamic_content_id`, `title`, `value`, `type`, `hint`, `image_width`, `image_height`) VALUES (14, 2, 'Info Section #1 Text', 'Our brand philosophy is a constant reminder that any concept is not complete without coming to reality, we have turned it into our motto and a call for action to implement this concept throughout.', 2, NULL, NULL, NULL);");
        $this->addSql("INSERT INTO `dynamic_content_attribute` (`id`, `dynamic_content_id`, `title`, `value`, `type`, `hint`, `image_width`, `image_height`) VALUES (15, 2, 'Info Section #2 Image', NULL, 4, '500px * 300px', 500, 300);");
        $this->addSql("INSERT INTO `dynamic_content_attribute` (`id`, `dynamic_content_id`, `title`, `value`, `type`, `hint`, `image_width`, `image_height`) VALUES (16, 2, 'Info Section #2 Title', 'OUR MISSION', 1, NULL, NULL, NULL);");
        $this->addSql("INSERT INTO `dynamic_content_attribute` (`id`, `dynamic_content_id`, `title`, `value`, `type`, `hint`, `image_width`, `image_height`) VALUES (17, 2, 'Info Section #2 Text', 'Our brand philosophy is a constant reminder that any concept is not complete without coming to reality, we have turned it into our motto and a call for action to implement this concept throughout.', 2, NULL, NULL, NULL);");
        $this->addSql("INSERT INTO `dynamic_content_attribute` (`id`, `dynamic_content_id`, `title`, `value`, `type`, `hint`, `image_width`, `image_height`) VALUES (18, 2, 'Info Section #3 Image', NULL, 4, '500px * 300px', 500, 300);");
        $this->addSql("INSERT INTO `dynamic_content_attribute` (`id`, `dynamic_content_id`, `title`, `value`, `type`, `hint`, `image_width`, `image_height`) VALUES (19, 2, 'Info Section #3 Title', 'OUR VISION', 1, NULL, NULL, NULL);");
        $this->addSql("INSERT INTO `dynamic_content_attribute` (`id`, `dynamic_content_id`, `title`, `value`, `type`, `hint`, `image_width`, `image_height`) VALUES (20, 2, 'Info Section #3 Text', 'Our brand philosophy is a constant reminder that any concept is not complete without coming to reality, we have turned it into our motto and a call for action to implement this concept throughout.', 2, NULL, NULL, NULL);");


        $this->addSql('CREATE TABLE original_design (id INT AUTO_INCREMENT NOT NULL, seo_id INT DEFAULT NULL, post_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, tarteb SMALLINT DEFAULT NULL, publish TINYINT(1) NOT NULL, featured TINYINT(1) NOT NULL, deleted DATETIME DEFAULT NULL, deleted_by VARCHAR(255) DEFAULT NULL, created DATETIME NOT NULL, creator VARCHAR(255) NOT NULL, modified DATETIME NOT NULL, modified_by VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_9E3379E097E3DD86 (seo_id), UNIQUE INDEX UNIQ_9E3379E04B89032C (post_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE original_design_translations (translatable_id INT NOT NULL, language_id INT NOT NULL, title VARCHAR(255) DEFAULT NULL, INDEX IDX_E57C69452C2AC5D3 (translatable_id), INDEX IDX_E57C694582F1BAF4 (language_id), PRIMARY KEY(translatable_id, language_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE original_design ADD CONSTRAINT FK_9E3379E097E3DD86 FOREIGN KEY (seo_id) REFERENCES seo (id)');
        $this->addSql('ALTER TABLE original_design ADD CONSTRAINT FK_9E3379E04B89032C FOREIGN KEY (post_id) REFERENCES post (id)');
        $this->addSql('ALTER TABLE original_design_translations ADD CONSTRAINT FK_E57C69452C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES original_design (id)');
        $this->addSql('ALTER TABLE original_design_translations ADD CONSTRAINT FK_E57C694582F1BAF4 FOREIGN KEY (language_id) REFERENCES language (id)');


        $this->addSql('CREATE TABLE material (id INT AUTO_INCREMENT NOT NULL, category_id INT DEFAULT NULL, post_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, tarteb SMALLINT DEFAULT NULL, publish TINYINT(1) NOT NULL, deleted DATETIME DEFAULT NULL, deleted_by VARCHAR(255) DEFAULT NULL, created DATETIME NOT NULL, creator VARCHAR(255) NOT NULL, modified DATETIME NOT NULL, modified_by VARCHAR(255) NOT NULL, INDEX IDX_7CBE759512469DE2 (category_id), UNIQUE INDEX UNIQ_7CBE75954B89032C (post_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE material_category (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, tarteb SMALLINT DEFAULT NULL, publish TINYINT(1) NOT NULL, deleted DATETIME DEFAULT NULL, deleted_by VARCHAR(255) DEFAULT NULL, created DATETIME NOT NULL, creator VARCHAR(255) NOT NULL, modified DATETIME NOT NULL, modified_by VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE material_category_translations (translatable_id INT NOT NULL, language_id INT NOT NULL, title VARCHAR(255) DEFAULT NULL, INDEX IDX_5227BC222C2AC5D3 (translatable_id), INDEX IDX_5227BC2282F1BAF4 (language_id), PRIMARY KEY(translatable_id, language_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE material_translations (translatable_id INT NOT NULL, language_id INT NOT NULL, title VARCHAR(255) DEFAULT NULL, INDEX IDX_C8CF4E872C2AC5D3 (translatable_id), INDEX IDX_C8CF4E8782F1BAF4 (language_id), PRIMARY KEY(translatable_id, language_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE material ADD CONSTRAINT FK_7CBE759512469DE2 FOREIGN KEY (category_id) REFERENCES material_category (id)');
        $this->addSql('ALTER TABLE material ADD CONSTRAINT FK_7CBE75954B89032C FOREIGN KEY (post_id) REFERENCES post (id)');
        $this->addSql('ALTER TABLE material_category_translations ADD CONSTRAINT FK_5227BC222C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES material_category (id)');
        $this->addSql('ALTER TABLE material_category_translations ADD CONSTRAINT FK_5227BC2282F1BAF4 FOREIGN KEY (language_id) REFERENCES language (id)');
        $this->addSql('ALTER TABLE material_translations ADD CONSTRAINT FK_C8CF4E872C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES material (id)');
        $this->addSql('ALTER TABLE material_translations ADD CONSTRAINT FK_C8CF4E8782F1BAF4 FOREIGN KEY (language_id) REFERENCES language (id)');

    }
    public function postUp(Schema $schema): void
    {
        $this->newSeo('Original Designs Page', 'Original Designs', 'original-designs', 'original-designs');
        $this->newSeo('Materials Page', 'Materials', 'materials', 'materials');
    }
    private function newSeo($seoPageTitle, $seoTitle, $seoPageType, $seoSlug): void
    {
        //getting entity manager
        $em = $this->container->get('doctrine.orm.entity_manager');

        $seoPage = new SeoPage();
        $seoPage->setTitle($seoPageTitle);
        $seoPage->setType($seoPageType);
        $seoPage->setCreated(new \DateTime());
        $seoPage->setCreator('System');
        $seoPage->setModified(new \DateTime());
        $seoPage->setModifiedBy('System');

        $seo = new Seo();
        $seo->setSeoBaseRoute($em->getRepository(SeoBaseRoute::class)->findByEntity($seoPage));
        $seo->setTitle($seoTitle);
        $seo->setSlug($seoSlug);
        $seo->setState(1);
        $seo->setLastModified(new \DateTime());

        $seoPage->setSeo($seo);

        $em->persist($seoPage);
        $em->persist($seo);

        $em->flush();
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE original_design DROP FOREIGN KEY FK_9E3379E097E3DD86');
        $this->addSql('ALTER TABLE original_design DROP FOREIGN KEY FK_9E3379E04B89032C');
        $this->addSql('ALTER TABLE original_design_translations DROP FOREIGN KEY FK_E57C69452C2AC5D3');
        $this->addSql('ALTER TABLE original_design_translations DROP FOREIGN KEY FK_E57C694582F1BAF4');

        $this->addSql('ALTER TABLE material DROP FOREIGN KEY FK_7CBE759512469DE2');
        $this->addSql('ALTER TABLE material DROP FOREIGN KEY FK_7CBE75954B89032C');
        $this->addSql('ALTER TABLE material_category_translations DROP FOREIGN KEY FK_5227BC222C2AC5D3');
        $this->addSql('ALTER TABLE material_category_translations DROP FOREIGN KEY FK_5227BC2282F1BAF4');
        $this->addSql('ALTER TABLE material_translations DROP FOREIGN KEY FK_C8CF4E872C2AC5D3');
        $this->addSql('ALTER TABLE material_translations DROP FOREIGN KEY FK_C8CF4E8782F1BAF4');


        $this->addSql('DROP TABLE material');
        $this->addSql('DROP TABLE material_category');
        $this->addSql('DROP TABLE material_category_translations');
        $this->addSql('DROP TABLE material_translations');
    }
}
