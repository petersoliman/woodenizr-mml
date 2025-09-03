<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250827120343 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE seo ADD author VARCHAR(255) DEFAULT NULL, ADD twitter_site VARCHAR(255) DEFAULT NULL, ADD twitter_creator VARCHAR(255) DEFAULT NULL, ADD whatsapp_image_width INT DEFAULT NULL, ADD whatsapp_image_height INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE seo DROP author, DROP twitter_site, DROP twitter_creator, DROP whatsapp_image_width, DROP whatsapp_image_height');
    }
}
