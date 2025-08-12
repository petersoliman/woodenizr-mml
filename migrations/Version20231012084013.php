<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231012084013 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` CHANGE cart cart JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE payment CHANGE user_data user_data JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE sent_data sent_data JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE received_data received_data JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE credit_card_received_data credit_card_received_data JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE post CHANGE content content JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE post_translations CHANGE content content JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE product_search CHANGE titles titles JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE slugs slugs JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE specs specs JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE project CHANGE data data JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment CHANGE user_data user_data JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE sent_data sent_data JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE received_data received_data JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE credit_card_received_data credit_card_received_data JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE post CHANGE content content JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE `user` CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE `order` CHANGE cart cart JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE product_search CHANGE titles titles JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE slugs slugs JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE specs specs JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE project CHANGE data data JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE post_translations CHANGE content content JSON NOT NULL COMMENT \'(DC2Type:json)\'');
    }
}
