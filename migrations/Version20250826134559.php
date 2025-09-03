<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250826134559 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE seo DROP whatsapp_image_width, DROP whatsapp_image_height, DROP mobile_app_capable, DROP apple_mobile_app_capable, DROP apple_mobile_app_title, DROP twitter_site, DROP twitter_creator, DROP pinterest_rich_pin, DROP author');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE seo ADD whatsapp_image_width INT DEFAULT NULL, ADD whatsapp_image_height INT DEFAULT NULL, ADD mobile_app_capable TINYINT(1) DEFAULT 0, ADD apple_mobile_app_capable TINYINT(1) DEFAULT 0, ADD apple_mobile_app_title VARCHAR(100) DEFAULT NULL, ADD twitter_site VARCHAR(100) DEFAULT NULL, ADD twitter_creator VARCHAR(100) DEFAULT NULL, ADD pinterest_rich_pin TINYINT(1) DEFAULT 1, ADD author VARCHAR(100) DEFAULT NULL');
    }
}
