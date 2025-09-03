<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250826140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add social media image types (Facebook, Instagram, Twitter, LinkedIn, Pinterest)';
    }

    public function up(Schema $schema): void
    {
        // Add new image types to image_type table
        $this->addSql("INSERT IGNORE INTO image_type (id, name) VALUES 
            (4, 'Facebook Image'),
            (5, 'Instagram Image'),
            (6, 'Twitter Image'),
            (7, 'LinkedIn Image'),
            (8, 'Pinterest Image')
        ");

        // Configure these types for Product entity (image_setting_id = 1)
        // Facebook Image - 1200x630px, radio button = 0 (checkbox), validate dimensions
        $this->addSql("INSERT IGNORE INTO image_setting_has_type 
            (image_type_id, image_setting_id, radio_button, width, height, validate_width_and_height, validate_size) 
            VALUES (4, 1, 0, 1200, 630, 1, 1)");

        // Instagram Image - 1080x1080px, radio button = 0 (checkbox), validate dimensions
        $this->addSql("INSERT IGNORE INTO image_setting_has_type 
            (image_type_id, image_setting_id, radio_button, width, height, validate_width_and_height, validate_size) 
            VALUES (5, 1, 0, 1080, 1080, 1, 1)");

        // Twitter Image - 1200x675px, radio button = 0 (checkbox), validate dimensions
        $this->addSql("INSERT IGNORE INTO image_setting_has_type 
            (image_type_id, image_setting_id, radio_button, width, height, validate_width_and_height, validate_size) 
            VALUES (6, 1, 0, 1200, 675, 1, 1)");

        // LinkedIn Image - 1200x627px, radio button = 0 (checkbox), validate dimensions
        $this->addSql("INSERT IGNORE INTO image_setting_has_type 
            (image_type_id, image_setting_id, radio_button, width, height, validate_width_and_height, validate_size) 
            VALUES (7, 1, 0, 1200, 627, 1, 1)");

        // Pinterest Image - 1000x1500px, radio button = 0 (checkbox), validate dimensions
        $this->addSql("INSERT IGNORE INTO image_setting_has_type 
            (image_type_id, image_setting_id, radio_button, width, height, validate_width_and_height, validate_size) 
            VALUES (8, 1, 0, 1000, 1500, 1, 1)");
    }

    public function down(Schema $schema): void
    {
        // Remove social media image types from image_setting_has_type
        $this->addSql("DELETE FROM image_setting_has_type WHERE image_type_id IN (4, 5, 6, 7, 8)");
        
        // Remove social media image types from image_type table
        $this->addSql("DELETE FROM image_type WHERE id IN (4, 5, 6, 7, 8)");
    }
}
