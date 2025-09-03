<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250826112127 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Add missing site settings for SEO global configuration (only if they don't exist)
        $this->addSql("INSERT IGNORE INTO site_setting (constant_name, title, type, manage_by_super_admin_only, value, creator, created, modified, modified_by) VALUES 
            ('twitter-site-handle', 'Twitter Site Handle', 'text', 0, 'woodenizr', 'System', NOW(), NOW(), 'System'),
            ('twitter-creator-handle', 'Twitter Creator Handle', 'text', 0, 'woodenizr', 'System', NOW(), NOW(), 'System'),
            ('pinterest-rich-pins-enabled', 'Pinterest Rich Pins Enabled', 'boolean', 0, '1', 'System', NOW(), NOW(), 'System'),
            ('website-author', 'Website Author', 'text', 0, 'Woodenizr', 'System', NOW(), NOW(), 'System')
        ");
    }

    public function down(Schema $schema): void
    {
        // This migration only adds settings if they don't exist, so no need to remove them
        // The down method is intentionally empty
    }
}
