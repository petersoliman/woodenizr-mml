<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230927085847 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04ADE4873418');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04ADE4873418 FOREIGN KEY (main_image_id) REFERENCES image (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE product_search DROP FOREIGN KEY FK_D68C9A03E4873418');
        $this->addSql('ALTER TABLE product_search ADD CONSTRAINT FK_D68C9A03E4873418 FOREIGN KEY (main_image_id) REFERENCES image (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04ADE4873418');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04ADE4873418 FOREIGN KEY (main_image_id) REFERENCES image (id)');
        $this->addSql('ALTER TABLE product_search DROP FOREIGN KEY FK_D68C9A03E4873418');
        $this->addSql('ALTER TABLE product_search ADD CONSTRAINT FK_D68C9A03E4873418 FOREIGN KEY (main_image_id) REFERENCES image (id)');
    }
}
