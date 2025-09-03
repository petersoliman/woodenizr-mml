<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Remove WhatsApp image dimension fields from seo table (moved to gallery section as helpful note)
 */
final class Version20250824180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove WhatsApp image dimension fields from seo table since they are now handled as helpful notes in the gallery section';
    }

    public function up(Schema $schema): void
    {
        // Remove WhatsApp image dimension fields from seo table
        $this->addSql('ALTER TABLE seo DROP whatsapp_image_width');
        $this->addSql('ALTER TABLE seo DROP whatsapp_image_height');
    }

    public function down(Schema $schema): void
    {
        // Add back WhatsApp image dimension fields to seo table
        $this->addSql('ALTER TABLE seo ADD whatsapp_image_width INT DEFAULT 1200');
        $this->addSql('ALTER TABLE seo ADD whatsapp_image_height INT DEFAULT 630');
    }
}

