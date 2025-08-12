<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\CMSBundle\Entity\SiteSetting;
use App\CMSBundle\Enum\SiteSettingTypeEnum;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250412105335 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        //SITE SETTINGS
        $this->addSql("INSERT INTO site_setting (`constant_name`, `title`, `type`, `manage_by_super_admin_only`, `value`, `creator`, `created`, `modified`, `modified_by`) VALUES (:constant_name, 'Website Favicon', :type, '0', '', 'System', NOW(), NOW(), 'System')",
            ["constant_name" => SiteSetting::WEBSITE_FAVICON, "type" => SiteSettingTypeEnum::FAVICON->value]
        );

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
