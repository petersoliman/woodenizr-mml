<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Created by: cursor
 * Date: 2025-09-01 00:01
 * Reason: Add gcd_status column to product to track GCData status
 */
final class Version20250901000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add gcd_status column to product table (Ready | Generating | Done)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE product ADD gcd_status VARCHAR(20) NOT NULL DEFAULT 'Ready'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product DROP gcd_status');
    }
}


