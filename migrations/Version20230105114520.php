<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\BaseBundle\SystemConfiguration;
use App\CMSBundle\Entity\DynamicPage;
use App\CMSBundle\Entity\SiteSetting;
use App\CMSBundle\Enum\SiteSettingTypeEnum;
use App\ContentBundle\Entity\Post;
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
final class Version20230105114520 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function getDescription(): string
    {
        return 'Initial Values for E-Commerce';
    }

    public function up(Schema $schema): void
    {
        //LANGUAGE ARABIC
        if (SystemConfiguration::ENABLE_ARABIC_LANG) {
            $this->addSql("INSERT INTO `language` (`id`, `locale`, `title`, `flag_asset`) VALUES (1, 'ar', 'Arabic', 'admin/images/flags/eg.png')");
        }


        //PAYMENT METHODS
        $this->addSql("INSERT INTO `payment_method` (`title`, `note`, `type`, `active`, `fees`, `deleted`, `deleted_by`, `created`, `creator`, `modified`, `modified_by`) VALUES ('Cash on delivery', NULL, :type, '1', '0', NULL, NULL, NOW(), 'System', NOW(), 'System')",
            ["type" => PaymentMethodEnum::CASH_ON_DELIVERY->value]
        );

        $this->addSql("INSERT INTO `payment_method` (`title`, `note`, `type`, `active`, `fees`, `deleted`, `deleted_by`, `created`, `creator`, `modified`, `modified_by`) VALUES ('Credit Card', NULL, :type, '1', '0', NULL, NULL, NOW(), 'System', NOW(), 'System')",
            ["type" => PaymentMethodEnum::CREDIT_CARD->value]
        );

        // CURRENCIES
        $this->addSql("INSERT INTO `currency` (`id`, `code`, `symbol`, `title`, `default`, `deleted`, `deleted_by`, `created`, `creator`, `modified`, `modified_by`) VALUES (1, 'EGP', 'EGP', 'Egyptian Pound', '1', NULL, NULL, NOW(), 'System', NOW(), 'System')");
        $this->addSql("INSERT INTO `exchange_rate` (`source_currency_id`, `target_currency_id`, `ratio`, `created`, `creator`, `modified`, `modified_by`) VALUES ( 1, 1, 1, NOW(), 'System', NOW(), 'System');");

        //IMAGE TYPE
        $this->addSql("INSERT INTO `image_type` (`id`, `name`) VALUES (1, 'Main Image')");
        $this->addSql("INSERT INTO `image_type` (`id`, `name`) VALUES (2, 'Gallery')");
        $this->addSql("INSERT INTO `image_type` (`id`, `name`) VALUES (3, 'Cover Photo')");

        // IMAGE SETTINGS
        $this->addSql("INSERT INTO image_setting (id, entity_name, back_route, upload_path, auto_resize, quality, gallery, created, creator, modified, modified_by) VALUES (1, 'Product', 'product_edit', 'product/', 1, 1, 0, NOW(), 'System', NOW(), 'System');");
        $this->addSql("INSERT INTO image_setting_has_type (radio_button, width, height, thumb_width, thumb_height, validate_width_and_height, validate_size, image_type_id, image_setting_id) VALUES (1, 1000, 1000, NULL, NULL, 0, 1, 1, 1);");
        $this->addSql("INSERT INTO image_setting_has_type (radio_button, width, height, thumb_width, thumb_height, validate_width_and_height, validate_size, image_type_id, image_setting_id) VALUES (0, 1000, 1000, NULL, NULL, 0, 1, 2, 1);");

        $this->addSql("INSERT INTO image_setting (id, entity_name, back_route, upload_path, auto_resize, quality, gallery, created, creator, modified, modified_by) VALUES (2, 'Blog', 'blog_edit', 'blog/', 1, 1, 0, NOW(), 'System', NOW(), 'System');");
        $this->addSql("INSERT INTO image_setting_has_type (radio_button, width, height, thumb_width, thumb_height, validate_width_and_height, validate_size, image_type_id, image_setting_id) VALUES (1, 1000, 1000, NULL, NULL, 0, 1, 1, 2);");
        $this->addSql("INSERT INTO image_setting_has_type (radio_button, width, height, thumb_width, thumb_height, validate_width_and_height, validate_size, image_type_id, image_setting_id) VALUES (1, 1400, 500, NULL, NULL, 0, 1, 3, 2);");

        $this->addSql("INSERT INTO image_setting (id, entity_name, back_route, upload_path, auto_resize, quality, gallery, created, creator, modified, modified_by) VALUES (3, 'DynamicPage', 'dynamic_page_edit', 'dynamic-page/', 1, 1, 0, NOW(), 'System', NOW(), 'System');");

        $this->addSql("INSERT INTO image_setting (id, entity_name, back_route, upload_path, auto_resize, quality, gallery, created, creator, modified, modified_by) VALUES (4, 'Category', 'category_edit', 'category/', 1, 1, 0, NOW(), 'System', NOW(), 'System');");
        $this->addSql("INSERT INTO image_setting_has_type (radio_button, width, height, thumb_width, thumb_height, validate_width_and_height, validate_size, image_type_id, image_setting_id) VALUES (1, 1920, 300, NULL, NULL, 0, 1, 3, 4);");

        if (SystemConfiguration::ENABLE_COLLECTION) {
            $this->addSql("INSERT INTO image_setting (id, entity_name, back_route, upload_path, auto_resize, quality, gallery, created, creator, modified, modified_by) VALUES (5, 'Collection', 'collection_edit', 'collection/', 1, 1, 0, NOW(), 'System', NOW(), 'System');");
            $this->addSql("INSERT INTO image_setting_has_type (radio_button, width, height, thumb_width, thumb_height, validate_width_and_height, validate_size, image_type_id, image_setting_id) VALUES (1, 1000, 1000, NULL, NULL, 0, 1, 1, 5);");
            $this->addSql("INSERT INTO image_setting_has_type (radio_button, width, height, thumb_width, thumb_height, validate_width_and_height, validate_size, image_type_id, image_setting_id) VALUES (1, 1920, 300, NULL, NULL, 0, 1, 3, 5);");
        }
        if (SystemConfiguration::ENABLE_OCCASION) {
            $this->addSql("INSERT INTO image_setting (id, entity_name, back_route, upload_path, auto_resize, quality, gallery, created, creator, modified, modified_by) VALUES (6, 'Occasion', 'occasion_edit', 'occasion/', 1, 1, 0, NOW(), 'System', NOW(), 'System');");
            $this->addSql("INSERT INTO image_setting_has_type (radio_button, width, height, thumb_width, thumb_height, validate_width_and_height, validate_size, image_type_id, image_setting_id) VALUES (1, 1920, 300, NULL, NULL, 0, 1, 3, 6);");
        }

        $this->addSql("INSERT INTO image_setting (id, entity_name, back_route, upload_path, auto_resize, quality, gallery, created, creator, modified, modified_by) VALUES (7, 'Project', 'project_edit', 'project/', 1, 1, 0, NOW(), 'System', NOW(), 'System');");
        $this->addSql("INSERT INTO image_setting_has_type (radio_button, width, height, thumb_width, thumb_height, validate_width_and_height, validate_size, image_type_id, image_setting_id) VALUES (1, 1000, 1000, NULL, NULL, 0, 1, 1, 7);");
        $this->addSql("INSERT INTO image_setting_has_type (radio_button, width, height, thumb_width, thumb_height, validate_width_and_height, validate_size, image_type_id, image_setting_id) VALUES (0, 1000, 1000, NULL, NULL, 0, 1, 2, 7);");

        // DYNAMIC CONTENT
        $this->addSql("INSERT INTO `dynamic_content` (`id`, `title`) VALUES (1, 'General Info')");
        $this->addSql("INSERT INTO `dynamic_content_attribute` (`id`, `dynamic_content_id`, `title`, `value`, `type`, `hint`, `image_width`, `image_height`) VALUES (1, 1, 'Email', 'info@ecommerce.com', 1, NULL, NULL, NULL);");
        $this->addSql("INSERT INTO `dynamic_content_attribute` (`id`, `dynamic_content_id`, `title`, `value`, `type`, `hint`, `image_width`, `image_height`) VALUES (2, 1, 'Address', 'Address, ALexandria', 2, NULL, NULL, NULL);");
        $this->addSql("INSERT INTO `dynamic_content_attribute` (`id`, `dynamic_content_id`, `title`, `value`, `type`, `hint`, `image_width`, `image_height`) VALUES (3, 1, 'Facebook Page Link', 'http://facebook.com', 3, NULL, NULL, NULL);");
        $this->addSql("INSERT INTO `dynamic_content_attribute` (`id`, `dynamic_content_id`, `title`, `value`, `type`, `hint`, `image_width`, `image_height`) VALUES (4, 1, 'Instagram Page Link', 'http://Instagram.com', 3, NULL, NULL, NULL);");
        $this->addSql("INSERT INTO `dynamic_content_attribute` (`id`, `dynamic_content_id`, `title`, `value`, `type`, `hint`, `image_width`, `image_height`) VALUES (5, 1, 'Twitter Page Link', 'http://Twitter.com', 3, NULL, NULL, NULL);");
        $this->addSql("INSERT INTO `dynamic_content_attribute` (`id`, `dynamic_content_id`, `title`, `value`, `type`, `hint`, `image_width`, `image_height`) VALUES (6, 1, 'Linkedin Page Link', 'http://Linkedin.com', 3, NULL, NULL, NULL);");
        $this->addSql("INSERT INTO `dynamic_content_attribute` (`id`, `dynamic_content_id`, `title`, `value`, `type`, `hint`, `image_width`, `image_height`) VALUES (7, 1, 'Youtube Channel Link', 'http://Youtube.com', 3, NULL, NULL, NULL);");
        $this->addSql("INSERT INTO `dynamic_content_attribute` (`id`, `dynamic_content_id`, `title`, `value`, `type`, `hint`, `image_width`, `image_height`) VALUES (8, 1, 'Header Image', NULL, 4, '1920px * 350px', 1920, 350);");
        $this->addSql("INSERT INTO `dynamic_content_attribute` (`id`, `dynamic_content_id`, `title`, `value`, `type`, `hint`, `image_width`, `image_height`) VALUES (9, 1, 'Login page image aside', NULL, 4, '780px * 1000px', 780, 1000)");


        //SITE SETTINGS
        $this->addSql("INSERT INTO site_setting (`constant_name`, `title`, `type`, `manage_by_super_admin_only`, `value`, `creator`, `created`, `modified`, `modified_by`) VALUES (:constant_name, 'Website Head Tags', :type, '0', '', 'System', NOW(), NOW(), 'System')",
            ["constant_name" => SiteSetting::WEBSITE_HEAD_TAGS, "type" => SiteSettingTypeEnum::HTML_TAG->value]
        );
        $this->addSql("INSERT INTO site_setting (`constant_name`, `title`, `type`, `manage_by_super_admin_only`, `value`, `creator`, `created`, `modified`, `modified_by`) VALUES (:constant_name, 'Google Tag Manager Id', :type, '0', '', 'System', NOW(), NOW(), 'System')",
            ["constant_name" => SiteSetting::GOOGLE_TAG_MANAGER_ID, "type" => SiteSettingTypeEnum::TEXT->value]
        );
        $this->addSql("INSERT INTO site_setting (`constant_name`, `title`, `type`, `manage_by_super_admin_only`, `value`, `creator`, `created`, `modified`, `modified_by`) VALUES (:constant_name, 'Facebook Chat Page Id', :type, '0', '', 'System', NOW(), NOW(), 'System')",
            ["constant_name" => SiteSetting::FACEBOOK_CHAT_PAGE_ID, "type" => SiteSettingTypeEnum::TEXT->value]
        );
        $this->addSql("INSERT INTO site_setting (`constant_name`, `title`, `type`, `manage_by_super_admin_only`, `value`, `creator`, `created`, `modified`, `modified_by`) VALUES (:constant_name, 'Facebook Pixel Id', :type, '0', '', 'System', NOW(), NOW(), 'System')",
            ["constant_name" => SiteSetting::FACEBOOK_PIXEL_ID, "type" => SiteSettingTypeEnum::TEXT->value]
        );
        $this->addSql("INSERT INTO site_setting (`constant_name`, `title`, `type`, `manage_by_super_admin_only`, `value`, `creator`, `created`, `modified`, `modified_by`) VALUES (:constant_name, 'Website Primary Color', :type, '1', '#79BBC3', 'System', NOW(), NOW(), 'System')",
            ["constant_name" => "website-primary-color", "type" => SiteSettingTypeEnum::COLOR_CODE->value]
        );
        $this->addSql("INSERT INTO site_setting (`constant_name`, `title`, `type`, `manage_by_super_admin_only`, `value`, `creator`, `created`, `modified`, `modified_by`) VALUES (:constant_name, 'Website Header Color', :type, '1', '#79BBC3', 'System', NOW(), NOW(), 'System')",
            ["constant_name" => "website-header-color", "type" => SiteSettingTypeEnum::COLOR_CODE->value]
        );
        $this->addSql("INSERT INTO site_setting (`constant_name`, `title`, `type`, `manage_by_super_admin_only`, `value`, `creator`, `created`, `modified`, `modified_by`) VALUES (:constant_name, 'Website Header Text Color', :type, '1', '#3f3f3f', 'System', NOW(), NOW(), 'System')",
            ["constant_name" => "website-header-text-color", "type" => SiteSettingTypeEnum::COLOR_CODE->value]
        );
        $this->addSql("INSERT INTO site_setting (`constant_name`, `title`, `type`, `manage_by_super_admin_only`, `value`, `creator`, `created`, `modified`, `modified_by`) VALUES (:constant_name, 'Website Footer Color', :type, '1', '#79BBC3', 'System', NOW(), NOW(), 'System')",
            ["constant_name" => "website-footer-color", "type" => SiteSettingTypeEnum::COLOR_CODE->value]
        );
        $this->addSql("INSERT INTO site_setting (`constant_name`, `title`, `type`, `manage_by_super_admin_only`, `value`, `creator`, `created`, `modified`, `modified_by`) VALUES (:constant_name, 'Website Footer Text Color', :type, '1', '#686868', 'System', NOW(), NOW(), 'System')",
            ["constant_name" => "website-footer-text-color", "type" => SiteSettingTypeEnum::COLOR_CODE->value]
        );
        $this->addSql("INSERT INTO site_setting (`constant_name`, `title`, `type`, `manage_by_super_admin_only`, `value`, `creator`, `created`, `modified`, `modified_by`) VALUES (:constant_name, 'Website Logo', :type, '1', null, 'System', NOW(), NOW(), 'System')",
            ["constant_name" => "website-logo", "type" => SiteSettingTypeEnum::SVG_CODE->value]
        );
        $this->addSql("INSERT INTO site_setting (`constant_name`, `title`, `type`, `manage_by_super_admin_only`, `value`, `creator`, `created`, `modified`, `modified_by`) VALUES (:constant_name, 'Mailchimp List ID', :type, '1', '', 'System', NOW(), NOW(), 'System')",
            ["constant_name" => "mailchimp-list-id", "type" => SiteSettingTypeEnum::TEXT->value]
        );
        $this->addSql("INSERT INTO site_setting (`constant_name`, `title`, `type`, `manage_by_super_admin_only`, `value`, `creator`, `created`, `modified`, `modified_by`) VALUES (:constant_name, 'Mailchimp API Key', :type, '1', '', 'System', NOW(), NOW(), 'System')",
            ["constant_name" => "mailchimp-api-key", "type" => SiteSettingTypeEnum::TEXT->value]
        );
        $this->addSql("INSERT INTO site_setting (`constant_name`, `title`, `type`, `manage_by_super_admin_only`, `value`, `creator`, `created`, `modified`, `modified_by`) VALUES (:constant_name, 'Contact us map coordinate', :type, '1', '30.0690556, 31.219615', 'System', NOW(), NOW(), 'System')",
            ["constant_name" => "contact-us-map-coordinate", "type" => SiteSettingTypeEnum::TEXT->value]
        );
        $this->addSql("INSERT INTO site_setting (`constant_name`, `title`, `type`, `manage_by_super_admin_only`, `value`, `creator`, `created`, `modified`, `modified_by`) VALUES (:constant_name, 'Google map API Key', :type, '1', 'AIzaSyD6Xwank3PinQcm0dxXUTAzvn15cdD9Y84', 'System', NOW(), NOW(), 'System')",
            ["constant_name" => "google-map-api-key", "type" => SiteSettingTypeEnum::TEXT->value]
        );
        $this->addSql("INSERT INTO site_setting (`constant_name`, `title`, `type`, `manage_by_super_admin_only`, `value`, `creator`, `created`, `modified`, `modified_by`) VALUES (:constant_name, 'Website Title', :type, '1', 'E-Commerce', 'System', NOW(), NOW(), 'System')",
            ["constant_name" => "website-title", "type" => SiteSettingTypeEnum::TEXT->value]
        );
        $this->addSql("INSERT INTO site_setting (`constant_name`, `title`, `type`, `manage_by_super_admin_only`, `value`, `creator`, `created`, `modified`, `modified_by`) VALUES (:constant_name, 'Send Emails from', :type, '1', 'no-reply@e-commerce.com', 'System', NOW(), NOW(), 'System')",
            ["constant_name" => "email-from", "type" => SiteSettingTypeEnum::EMAIL->value]
        );

        $this->addSql("INSERT INTO site_setting (`constant_name`, `title`, `type`, `manage_by_super_admin_only`, `value`, `creator`, `created`, `modified`, `modified_by`) VALUES (:constant_name, 'Admin Email', :type, '0', 'info@e-commerce.com', 'System', NOW(), NOW(), 'System')",
            ["constant_name" => "admin-email", "type" => SiteSettingTypeEnum::EMAIL->value]
        );

        $this->addSql("INSERT INTO site_setting (`constant_name`, `title`, `type`, `manage_by_super_admin_only`, `value`, `creator`, `created`, `modified`, `modified_by`) VALUES (:constant_name, 'Facebook Conversion API - Access token', :type, '0', NULL, 'System', NOW(), NOW(), 'System')",
            ["constant_name" => "facebook-conversion-api-access-token", "type" => SiteSettingTypeEnum::TEXT->value]
        );
        $this->addSql("INSERT INTO site_setting (`constant_name`, `title`, `type`, `manage_by_super_admin_only`, `value`, `creator`, `created`, `modified`, `modified_by`) VALUES (:constant_name, 'Facebook Conversion API - Pixel ID', :type, '0', NULL, 'System', NOW(), NOW(), 'System')",
            ["constant_name" => "facebook-conversion-api-pixel-id", "type" => SiteSettingTypeEnum::TEXT->value]
        );
        $this->addSql("INSERT INTO site_setting (`constant_name`, `title`, `type`, `manage_by_super_admin_only`, `value`, `creator`, `created`, `modified`, `modified_by`) VALUES (:constant_name, 'Facebook Conversion API - Test Event Code', :type, '1', NULL, 'System', NOW(), NOW(), 'System')",
            ["constant_name" => "facebook-conversion-api-test-event-code", "type" => SiteSettingTypeEnum::TEXT->value]
        );
    }

    public function postUp(Schema $schema): void
    {
        //SEO BASE ROUTES
        $this->newSeoBaseRoute(SeoPage::class, "seo_page");
        $this->newSeoBaseRoute(DynamicPage::class, "page");

        // SEO
        $this->newSeo('Home Page', 'Home Page', 'home-page', 'home-page');
        $this->newSeo('On Sale', 'On Sale', 'sale', 'sale');
        $this->newSeo('Products', 'Products', 'products', 'products');
        $this->newSeo('Categories List Page', 'Categories', 'categories', 'categories');
        if (SystemConfiguration::ENABLE_COLLECTION) {
            $this->newSeo('Collections List Page', 'Collections', 'collections', 'collections');
        }
        if (SystemConfiguration::ENABLE_OCCASION) {
            $this->newSeo('Occasions List Page', 'Occasions', 'occasions', 'occasions');
        }
        $this->newSeo('Blogs List Page', 'Blogs', 'blogs', 'blogs');
        $this->newSeo('FAQs Page', 'FAQs', 'faq', 'faq');
        $this->newSeo('Projects List Page', 'Projects', 'projects', 'project');
        $this->newSeo('Contact Us Page', 'Contact Us', 'contact-us', 'contact-us');

        $this->createDynamicPage("Terms and Conditions", "Terms and Conditions", "terms-and-conditions");
    }

    private function newSeoBaseRoute(string $entityName, $baseRoute): void
    {
        $em = $this->container->get('doctrine.orm.entity_manager');

        $seoBaseRoute = new SeoBaseRoute();
        $seoBaseRoute->setEntityName($entityName);
        $seoBaseRoute->setBaseRoute($baseRoute);
        $seoBaseRoute->setCreated(new \DateTime());
        $seoBaseRoute->setCreator("System");
        $seoBaseRoute->setModified(new \DateTime());
        $seoBaseRoute->setModifiedBy("System");

        $em->persist($seoBaseRoute);
        $em->flush();
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

    private function createDynamicPage($dynamicPageTitle, $seoTitle, $seoSlug): void
    {
        //getting entity manager
        $em = $this->container->get('doctrine.orm.entity_manager');

        //setting seo pages values
        $dynamicPage = new DynamicPage();
        $dynamicPage->setTitle($dynamicPageTitle);
        $dynamicPage->setCreated(new \DateTime());
        $dynamicPage->setCreator('System');
        $dynamicPage->setModified(new \DateTime());
        $dynamicPage->setModifiedBy('System');

        $post = new Post();
        $post->setContent(["description"=>"", "brief"=>""]);
        $dynamicPage->setPost($post);

        $seo = new Seo();
        $seo->setSeoBaseRoute($em->getRepository(SeoBaseRoute::class)->findByEntity($dynamicPage));
        $seo->setTitle($seoTitle);
        $seo->setSlug($seoSlug);
        $seo->setState(1);
        $seo->setLastModified(new \DateTime());

        $dynamicPage->setSeo($seo);

        $em->persist($dynamicPage);
        $em->flush();
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
