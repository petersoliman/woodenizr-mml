<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Remove mobile app fields from seo table (not needed without mobile app)
 */
final class Version20250824170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove mobile app fields from seo table since no mobile app exists yet';
    }

    public function up(Schema $schema): void
    {
        // Remove mobile app fields from seo table
        $this->addSql('ALTER TABLE seo DROP mobile_app_capable');
        $this->addSql('ALTER TABLE seo DROP apple_mobile_app_capable');
        $this->addSql('ALTER TABLE seo DROP apple_mobile_app_title');
    }

    public function down(Schema $schema): void
    {
        // Add back mobile app fields to seo table
        $this->addSql('ALTER TABLE seo ADD mobile_app_capable TINYINT(1) DEFAULT 1');
        $this->addSql('ALTER TABLE seo ADD apple_mobile_app_capable TINYINT(1) DEFAULT 1');
        $this->addSql('ALTER TABLE seo ADD apple_mobile_app_title VARCHAR(255) DEFAULT NULL');
    }
}

