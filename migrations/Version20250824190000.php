<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add social media settings to site_setting table for global configuration
 */
final class Version20250824190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add social media settings to site_setting table for global Twitter and Pinterest configuration';
    }

    public function up(Schema $schema): void
    {
        // Add Twitter Site Handle setting
        $this->addSql("INSERT INTO site_setting (constant_name, title, type, value, manage_by_super_admin_only, created, modified, creator, modified_by) VALUES ('twitter-site-handle', 'Twitter Site Handle', 'text', '@woodenizr', 0, NOW(), NOW(), 'System', 'System')");
        
        // Add Twitter Creator Handle setting
        $this->addSql("INSERT INTO site_setting (constant_name, title, type, value, manage_by_super_admin_only, created, modified, creator, modified_by) VALUES ('twitter-creator-handle', 'Twitter Creator Handle', 'text', '@woodenizr', 0, NOW(), NOW(), 'System', 'System')");
        
        // Add Pinterest Rich Pins setting
        $this->addSql("INSERT INTO site_setting (constant_name, title, type, value, manage_by_super_admin_only, created, modified, creator, modified_by) VALUES ('pinterest-rich-pins-enabled', 'Enable Pinterest Rich Pins', 'boolean', '1', 0, NOW(), NOW(), 'System', 'System')");
    }

    public function down(Schema $schema): void
    {
        // Remove social media settings from site_setting table
        $this->addSql("DELETE FROM site_setting WHERE constant_name = 'twitter-site-handle'");
        $this->addSql("DELETE FROM site_setting WHERE constant_name = 'twitter-creator-handle'");
        $this->addSql("DELETE FROM site_setting WHERE constant_name = 'pinterest-rich-pins-enabled'");
    }
}
