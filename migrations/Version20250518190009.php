<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250518190009 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE 360_view (id INT AUTO_INCREMENT NOT NULL, image_extension VARCHAR(255) NOT NULL, created DATETIME NOT NULL, creator VARCHAR(255) NOT NULL, modified DATETIME NOT NULL, modified_by VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE three_sixty_view_image (three_sixty_view_id INT NOT NULL, image_id INT NOT NULL, INDEX IDX_F1735398F7B0A1B (three_sixty_view_id), INDEX IDX_F17353983DA5256D (image_id), PRIMARY KEY(three_sixty_view_id, image_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE three_sixty_view_image ADD CONSTRAINT FK_F1735398F7B0A1B FOREIGN KEY (three_sixty_view_id) REFERENCES 360_view (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE three_sixty_view_image ADD CONSTRAINT FK_F17353983DA5256D FOREIGN KEY (image_id) REFERENCES image (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product ADD three_sixty_view_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04ADF7B0A1B FOREIGN KEY (three_sixty_view_id) REFERENCES 360_view (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D34A04ADF7B0A1B ON product (three_sixty_view_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04ADF7B0A1B');
        $this->addSql('ALTER TABLE three_sixty_view_image DROP FOREIGN KEY FK_F1735398F7B0A1B');
        $this->addSql('ALTER TABLE three_sixty_view_image DROP FOREIGN KEY FK_F17353983DA5256D');
        $this->addSql('DROP TABLE 360_view');
        $this->addSql('DROP TABLE three_sixty_view_image');
        $this->addSql('DROP INDEX UNIQ_D34A04ADF7B0A1B ON product');
        $this->addSql('ALTER TABLE product DROP three_sixty_view_id');
    }
}
