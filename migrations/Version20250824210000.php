<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add website author setting to site_setting table for global SEO configuration
 */
final class Version20250824210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add website author setting to site_setting table for global SEO author configuration';
    }

    public function up(Schema $schema): void
    {
        // Add Website Author setting
        $this->addSql("INSERT INTO site_setting (constant_name, title, type, value, manage_by_super_admin_only, created, modified, creator, modified_by) VALUES ('website-author', 'Website Author', 'text', 'Woodenizr', 0, NOW(), NOW(), 'System', 'System')");
    }

    public function down(Schema $schema): void
    {
        // Remove Website Author setting from site_setting table
        $this->addSql("DELETE FROM site_setting WHERE constant_name = 'website-author'");
    }
}

