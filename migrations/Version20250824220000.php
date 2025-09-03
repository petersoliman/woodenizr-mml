<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Remove author field from seo table since it is now handled globally in site settings
 */
final class Version20250824220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove author field from seo table since it is now handled globally in site settings';
    }

    public function up(Schema $schema): void
    {
        // Remove author field from seo table
        $this->addSql('ALTER TABLE seo DROP author');
    }

    public function down(Schema $schema): void
    {
        // Add back author field to seo table
        $this->addSql('ALTER TABLE seo ADD author VARCHAR(255) DEFAULT NULL');
    }
}

