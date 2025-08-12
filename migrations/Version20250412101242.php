<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250412101242 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` ADD vendor_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398F603EE73 FOREIGN KEY (vendor_id) REFERENCES vendor (id)');
        $this->addSql('CREATE INDEX IDX_F5299398F603EE73 ON `order` (vendor_id)');
        $this->addSql('ALTER TABLE vendor ADD email VARCHAR(180) DEFAULT NULL, ADD company_registration_number VARCHAR(255) DEFAULT NULL, ADD sales_tax_number VARCHAR(11) DEFAULT NULL, ADD bank_name VARCHAR(50) DEFAULT NULL, ADD bank_account_swift_code VARCHAR(50) DEFAULT NULL, ADD bank_account_iban VARCHAR(50) DEFAULT NULL, ADD bank_branch VARCHAR(50) DEFAULT NULL, ADD bank_account_no VARCHAR(50) DEFAULT NULL, ADD bank_account_holder_name VARCHAR(225) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE vendor DROP email, DROP company_registration_number, DROP sales_tax_number, DROP bank_name, DROP bank_account_swift_code, DROP bank_account_iban, DROP bank_branch, DROP bank_account_no, DROP bank_account_holder_name');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398F603EE73');
        $this->addSql('DROP INDEX IDX_F5299398F603EE73 ON `order`');
        $this->addSql('ALTER TABLE `order` DROP vendor_id');
    }
}
