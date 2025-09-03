<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Remove social media fields from seo table since they are now handled globally in site settings
 */
final class Version20250824200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove social media fields (twitter_site, twitter_creator, pinterest_rich_pin) from seo table since they are now handled globally in site settings';
    }

    public function up(Schema $schema): void
    {
        // Remove social media fields from seo table
        $this->addSql('ALTER TABLE seo DROP twitter_site');
        $this->addSql('ALTER TABLE seo DROP twitter_creator');
        $this->addSql('ALTER TABLE seo DROP pinterest_rich_pin');
    }

    public function down(Schema $schema): void
    {
        // Add back social media fields to seo table
        $this->addSql('ALTER TABLE seo ADD twitter_site VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE seo ADD twitter_creator VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE seo ADD pinterest_rich_pin TINYINT(1) DEFAULT 1');
    }
}

