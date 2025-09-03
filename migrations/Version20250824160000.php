<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add enhanced SEO fields to seo table
 */
final class Version20250824160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add enhanced SEO fields to seo table for better meta tag management';
    }

    public function up(Schema $schema): void
    {
        // Add new SEO fields to seo table
        $this->addSql('ALTER TABLE seo ADD canonical_url VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE seo ADD robots VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE seo ADD author VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE seo ADD twitter_site VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE seo ADD twitter_creator VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE seo ADD pinterest_rich_pin TINYINT(1) DEFAULT 1');
        $this->addSql('ALTER TABLE seo ADD whatsapp_image_width INT DEFAULT 1200');
        $this->addSql('ALTER TABLE seo ADD whatsapp_image_height INT DEFAULT 630');
        $this->addSql('ALTER TABLE seo ADD mobile_app_capable TINYINT(1) DEFAULT 1');
        $this->addSql('ALTER TABLE seo ADD apple_mobile_app_capable TINYINT(1) DEFAULT 1');
        $this->addSql('ALTER TABLE seo ADD apple_mobile_app_title VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Remove enhanced SEO fields from seo table
        $this->addSql('ALTER TABLE seo DROP canonical_url');
        $this->addSql('ALTER TABLE seo DROP robots');
        $this->addSql('ALTER TABLE seo DROP author');
        $this->addSql('ALTER TABLE seo DROP twitter_site');
        $this->addSql('ALTER TABLE seo DROP twitter_creator');
        $this->addSql('ALTER TABLE seo DROP pinterest_rich_pin');
        $this->addSql('ALTER TABLE seo DROP whatsapp_image_width');
        $this->addSql('ALTER TABLE seo DROP whatsapp_image_height');
        $this->addSql('ALTER TABLE seo DROP mobile_app_capable');
        $this->addSql('ALTER TABLE seo DROP apple_mobile_app_capable');
        $this->addSql('ALTER TABLE seo DROP apple_mobile_app_title');
    }
}

