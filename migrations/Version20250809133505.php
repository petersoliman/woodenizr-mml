<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250809133505 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE product_bulk_generate (id INT AUTO_INCREMENT NOT NULL, admin_id INT DEFAULT NULL, generated_for INT NOT NULL, start_date DATETIME NOT NULL, end_date DATETIME DEFAULT NULL, total_recommendations INT DEFAULT 0 NOT NULL, admin_note LONGTEXT DEFAULT NULL, status VARCHAR(20) DEFAULT \'pending\' NOT NULL, processed_count INT DEFAULT 0 NOT NULL, error_count INT DEFAULT 0 NOT NULL, error_log JSON DEFAULT NULL, deleted DATETIME DEFAULT NULL, deleted_by VARCHAR(255) DEFAULT NULL, created DATETIME NOT NULL, creator VARCHAR(255) NOT NULL, modified DATETIME NOT NULL, modified_by VARCHAR(255) NOT NULL, uuid VARCHAR(50) NOT NULL, UNIQUE INDEX UNIQ_47D90A8CD17F50A6 (uuid), INDEX IDX_47D90A8C642B8210 (admin_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE product_bulk_generate ADD CONSTRAINT FK_47D90A8C642B8210 FOREIGN KEY (admin_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_bulk_generate DROP FOREIGN KEY FK_47D90A8C642B8210');
        $this->addSql('DROP TABLE product_bulk_generate');
    }
}
