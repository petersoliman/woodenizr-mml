<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241011163447 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE courier (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(50) NOT NULL, deleted DATETIME DEFAULT NULL, deleted_by VARCHAR(255) DEFAULT NULL, created DATETIME NOT NULL, creator VARCHAR(255) NOT NULL, modified DATETIME NOT NULL, modified_by VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE courier_translations (translatable_id INT NOT NULL, language_id INT NOT NULL, title VARCHAR(255) DEFAULT NULL, INDEX IDX_376B70C62C2AC5D3 (translatable_id), INDEX IDX_376B70C682F1BAF4 (language_id), PRIMARY KEY(translatable_id, language_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE shipping_time (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(45) NOT NULL, number_of_delivery_days INT DEFAULT 0, deleted TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE shipping_time_translation (translatable_id INT NOT NULL, language_id INT NOT NULL, name VARCHAR(255) DEFAULT NULL, INDEX IDX_FEE7D9C42C2AC5D3 (translatable_id), INDEX IDX_FEE7D9C482F1BAF4 (language_id), PRIMARY KEY(translatable_id, language_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE shipping_zone (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(50) NOT NULL, deleted DATETIME DEFAULT NULL, deleted_by VARCHAR(255) DEFAULT NULL, created DATETIME NOT NULL, creator VARCHAR(255) NOT NULL, modified DATETIME NOT NULL, modified_by VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE shipping_zone_zone (shipping_zone_id INT NOT NULL, zone_id INT NOT NULL, INDEX IDX_74E650957964396F (shipping_zone_id), INDEX IDX_74E650959F2C3FAB (zone_id), PRIMARY KEY(shipping_zone_id, zone_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE shipping_zone_price (id INT AUTO_INCREMENT NOT NULL, source_shipping_zone_id INT DEFAULT NULL, target_shipping_zone_id INT DEFAULT NULL, shipping_time_id INT DEFAULT NULL, currency_id INT DEFAULT NULL, courier_id INT DEFAULT NULL, calculator VARCHAR(45) NOT NULL, has_rates TINYINT(1) NOT NULL, configuration JSON NOT NULL COMMENT \'(DC2Type:json)\', created DATETIME NOT NULL, creator VARCHAR(255) NOT NULL, modified DATETIME NOT NULL, modified_by VARCHAR(255) NOT NULL, INDEX IDX_D41DDD21EE10FFC9 (source_shipping_zone_id), INDEX IDX_D41DDD21AD9D076F (target_shipping_zone_id), INDEX IDX_D41DDD21B8A2DBFF (shipping_time_id), INDEX IDX_D41DDD2138248176 (currency_id), INDEX IDX_D41DDD21E3D8151C (courier_id), UNIQUE INDEX shipping_zone_unique (source_shipping_zone_id, target_shipping_zone_id, shipping_time_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE shipping_zone_price_specific_weight (id INT AUTO_INCREMENT NOT NULL, shipping_zone_price_id INT DEFAULT NULL, weight DOUBLE PRECISION NOT NULL, rate DOUBLE PRECISION NOT NULL, deleted DATETIME DEFAULT NULL, deleted_by VARCHAR(255) DEFAULT NULL, created DATETIME NOT NULL, creator VARCHAR(255) NOT NULL, modified DATETIME NOT NULL, modified_by VARCHAR(255) NOT NULL, INDEX IDX_1C56A91185E3EB8 (shipping_zone_price_id), UNIQUE INDEX shipping_zone_price_specific_weight_unique (shipping_zone_price_id, weight), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE zone (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(50) NOT NULL, tarteb SMALLINT DEFAULT NULL, deleted DATETIME DEFAULT NULL, deleted_by VARCHAR(255) DEFAULT NULL, created DATETIME NOT NULL, creator VARCHAR(255) NOT NULL, modified DATETIME NOT NULL, modified_by VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE zone_translations (translatable_id INT NOT NULL, language_id INT NOT NULL, title VARCHAR(255) DEFAULT NULL, INDEX IDX_1C7748542C2AC5D3 (translatable_id), INDEX IDX_1C77485482F1BAF4 (language_id), PRIMARY KEY(translatable_id, language_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE courier_translations ADD CONSTRAINT FK_376B70C62C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES courier (id)');
        $this->addSql('ALTER TABLE courier_translations ADD CONSTRAINT FK_376B70C682F1BAF4 FOREIGN KEY (language_id) REFERENCES language (id)');
        $this->addSql('ALTER TABLE shipping_time_translation ADD CONSTRAINT FK_FEE7D9C42C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES shipping_time (id)');
        $this->addSql('ALTER TABLE shipping_time_translation ADD CONSTRAINT FK_FEE7D9C482F1BAF4 FOREIGN KEY (language_id) REFERENCES language (id)');
        $this->addSql('ALTER TABLE shipping_zone_zone ADD CONSTRAINT FK_74E650957964396F FOREIGN KEY (shipping_zone_id) REFERENCES shipping_zone (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE shipping_zone_zone ADD CONSTRAINT FK_74E650959F2C3FAB FOREIGN KEY (zone_id) REFERENCES zone (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE shipping_zone_price ADD CONSTRAINT FK_D41DDD21EE10FFC9 FOREIGN KEY (source_shipping_zone_id) REFERENCES shipping_zone (id)');
        $this->addSql('ALTER TABLE shipping_zone_price ADD CONSTRAINT FK_D41DDD21AD9D076F FOREIGN KEY (target_shipping_zone_id) REFERENCES shipping_zone (id)');
        $this->addSql('ALTER TABLE shipping_zone_price ADD CONSTRAINT FK_D41DDD21B8A2DBFF FOREIGN KEY (shipping_time_id) REFERENCES shipping_time (id)');
        $this->addSql('ALTER TABLE shipping_zone_price ADD CONSTRAINT FK_D41DDD2138248176 FOREIGN KEY (currency_id) REFERENCES currency (id)');
        $this->addSql('ALTER TABLE shipping_zone_price ADD CONSTRAINT FK_D41DDD21E3D8151C FOREIGN KEY (courier_id) REFERENCES courier (id)');
        $this->addSql('ALTER TABLE shipping_zone_price_specific_weight ADD CONSTRAINT FK_1C56A91185E3EB8 FOREIGN KEY (shipping_zone_price_id) REFERENCES shipping_zone_price (id)');
        $this->addSql('ALTER TABLE zone_translations ADD CONSTRAINT FK_1C7748542C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES zone (id)');
        $this->addSql('ALTER TABLE zone_translations ADD CONSTRAINT FK_1C77485482F1BAF4 FOREIGN KEY (language_id) REFERENCES language (id)');
        $this->addSql('ALTER TABLE store_address ADD zone_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE store_address ADD CONSTRAINT FK_14464E669F2C3FAB FOREIGN KEY (zone_id) REFERENCES zone (id)');
        $this->addSql('CREATE INDEX IDX_14464E669F2C3FAB ON store_address (zone_id)');
        $this->addSql('ALTER TABLE order_shipping_address DROP FOREIGN KEY FK_89107D599F2C3FAB');
        $this->addSql('ALTER TABLE order_shipping_address ADD CONSTRAINT FK_89107D599F2C3FAB FOREIGN KEY (zone_id) REFERENCES zone (id)');
        $this->addSql('ALTER TABLE shipping_address DROP FOREIGN KEY FK_EB0669459F2C3FAB');
        $this->addSql('ALTER TABLE shipping_address ADD CONSTRAINT FK_EB0669459F2C3FAB FOREIGN KEY (zone_id) REFERENCES zone (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE store_address DROP FOREIGN KEY FK_14464E669F2C3FAB');
        $this->addSql('ALTER TABLE courier_translations DROP FOREIGN KEY FK_376B70C62C2AC5D3');
        $this->addSql('ALTER TABLE courier_translations DROP FOREIGN KEY FK_376B70C682F1BAF4');
        $this->addSql('ALTER TABLE shipping_time_translation DROP FOREIGN KEY FK_FEE7D9C42C2AC5D3');
        $this->addSql('ALTER TABLE shipping_time_translation DROP FOREIGN KEY FK_FEE7D9C482F1BAF4');
        $this->addSql('ALTER TABLE shipping_zone_zone DROP FOREIGN KEY FK_74E650957964396F');
        $this->addSql('ALTER TABLE shipping_zone_zone DROP FOREIGN KEY FK_74E650959F2C3FAB');
        $this->addSql('ALTER TABLE shipping_zone_price DROP FOREIGN KEY FK_D41DDD21EE10FFC9');
        $this->addSql('ALTER TABLE shipping_zone_price DROP FOREIGN KEY FK_D41DDD21AD9D076F');
        $this->addSql('ALTER TABLE shipping_zone_price DROP FOREIGN KEY FK_D41DDD21B8A2DBFF');
        $this->addSql('ALTER TABLE shipping_zone_price DROP FOREIGN KEY FK_D41DDD2138248176');
        $this->addSql('ALTER TABLE shipping_zone_price DROP FOREIGN KEY FK_D41DDD21E3D8151C');
        $this->addSql('ALTER TABLE shipping_zone_price_specific_weight DROP FOREIGN KEY FK_1C56A91185E3EB8');
        $this->addSql('ALTER TABLE zone_translations DROP FOREIGN KEY FK_1C7748542C2AC5D3');
        $this->addSql('ALTER TABLE zone_translations DROP FOREIGN KEY FK_1C77485482F1BAF4');
        $this->addSql('DROP TABLE courier');
        $this->addSql('DROP TABLE courier_translations');
        $this->addSql('DROP TABLE shipping_time');
        $this->addSql('DROP TABLE shipping_time_translation');
        $this->addSql('DROP TABLE shipping_zone');
        $this->addSql('DROP TABLE shipping_zone_zone');
        $this->addSql('DROP TABLE shipping_zone_price');
        $this->addSql('DROP TABLE shipping_zone_price_specific_weight');
        $this->addSql('DROP TABLE zone');
        $this->addSql('DROP TABLE zone_translations');
        $this->addSql('DROP INDEX IDX_14464E669F2C3FAB ON store_address');
        $this->addSql('ALTER TABLE store_address DROP zone_id');
        $this->addSql('ALTER TABLE order_shipping_address DROP FOREIGN KEY FK_89107D599F2C3FAB');
        $this->addSql('ALTER TABLE order_shipping_address ADD CONSTRAINT FK_89107D599F2C3FAB FOREIGN KEY (zone_id) REFERENCES city (id)');
        $this->addSql('ALTER TABLE shipping_address DROP FOREIGN KEY FK_EB0669459F2C3FAB');
        $this->addSql('ALTER TABLE shipping_address ADD CONSTRAINT FK_EB0669459F2C3FAB FOREIGN KEY (zone_id) REFERENCES city (id)');
    }
}
