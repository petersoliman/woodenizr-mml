<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250826200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add title field to image table for SEO optimization';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE image ADD title VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE image DROP title');
    }
}
