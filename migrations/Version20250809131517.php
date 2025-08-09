<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250809131517 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE content_recommendation (id INT AUTO_INCREMENT NOT NULL, product_id INT DEFAULT NULL, recommended JSON DEFAULT NULL, state INT NOT NULL, notes LONGTEXT DEFAULT NULL, deleted DATETIME DEFAULT NULL, deleted_by VARCHAR(255) DEFAULT NULL, created DATETIME NOT NULL, creator VARCHAR(255) NOT NULL, modified DATETIME NOT NULL, modified_by VARCHAR(255) NOT NULL, uuid VARCHAR(50) NOT NULL, UNIQUE INDEX UNIQ_29D9DE06D17F50A6 (uuid), INDEX IDX_29D9DE064584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE content_recommendation ADD CONSTRAINT FK_29D9DE064584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `order` CHANGE cart cart JSON NOT NULL');
        $this->addSql('ALTER TABLE payment CHANGE user_data user_data JSON NOT NULL, CHANGE sent_data sent_data JSON NOT NULL, CHANGE received_data received_data JSON DEFAULT NULL, CHANGE credit_card_received_data credit_card_received_data JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE post CHANGE content content JSON NOT NULL');
        $this->addSql('ALTER TABLE post_translations CHANGE content content JSON NOT NULL');
        $this->addSql('ALTER TABLE product_search CHANGE titles titles JSON NOT NULL, CHANGE slugs slugs JSON NOT NULL, CHANGE specs specs JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE project CHANGE data data JSON NOT NULL');
        $this->addSql('ALTER TABLE shipping_zone_price CHANGE configuration configuration JSON NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE content_recommendation DROP FOREIGN KEY FK_29D9DE064584665A');
        $this->addSql('DROP TABLE content_recommendation');
        $this->addSql('ALTER TABLE `order` CHANGE cart cart JSON NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE payment CHANGE user_data user_data JSON NOT NULL COLLATE `utf8mb4_bin`, CHANGE sent_data sent_data JSON NOT NULL COLLATE `utf8mb4_bin`, CHANGE received_data received_data JSON DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE credit_card_received_data credit_card_received_data JSON DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE post CHANGE content content JSON NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE post_translations CHANGE content content JSON NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE product_search CHANGE titles titles JSON NOT NULL COLLATE `utf8mb4_bin`, CHANGE slugs slugs JSON NOT NULL COLLATE `utf8mb4_bin`, CHANGE specs specs JSON DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE project CHANGE data data JSON NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE shipping_zone_price CHANGE configuration configuration JSON NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE `user` CHANGE roles roles JSON NOT NULL COLLATE `utf8mb4_bin`');
    }
}
