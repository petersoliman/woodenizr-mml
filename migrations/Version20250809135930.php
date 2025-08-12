<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Content Recommendation State Values Migration
 * 
 * This migration updates the state field values in the content_recommendation table
 * to use the new standardized mapping:
 * 
 * NEW MAPPING (after migration):
 * - 1 = NEW (default state for new recommendations)
 * - 2 = ACCEPTED (recommendation approved)  
 * - 3 = REJECTED (recommendation declined)
 * 
 * OLD MAPPING (before migration):
 * - 1 = ACCEPTED
 * - 2 = REJECTED  
 * - 3 = NEW
 * 
 * The migration preserves existing data by converting old values to new values.
 */
final class Version20250809135930 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update ContentRecommendation state values: 1=New, 2=Accepted, 3=Rejected';
    }

    public function up(Schema $schema): void
    {
        // Update existing state values to match new mapping:
        // Old: 1=Accepted, 2=Rejected, 3=New
        // New: 1=New, 2=Accepted, 3=Rejected
        
        // Create a temporary column to store the converted values
        $this->addSql('ALTER TABLE content_recommendation ADD COLUMN state_temp INT');
        
        // Convert the values:
        // Old 1 (Accepted) -> New 2 (Accepted)
        $this->addSql('UPDATE content_recommendation SET state_temp = 2 WHERE state = 1');
        // Old 2 (Rejected) -> New 3 (Rejected) 
        $this->addSql('UPDATE content_recommendation SET state_temp = 3 WHERE state = 2');
        // Old 3 (New) -> New 1 (New)
        $this->addSql('UPDATE content_recommendation SET state_temp = 1 WHERE state = 3');
        
        // Copy converted values back to state column
        $this->addSql('UPDATE content_recommendation SET state = state_temp');
        
        // Drop temporary column
        $this->addSql('ALTER TABLE content_recommendation DROP COLUMN state_temp');
    }

    public function down(Schema $schema): void
    {
        // Revert state values to original mapping:
        // New: 1=New, 2=Accepted, 3=Rejected  
        // Old: 1=Accepted, 2=Rejected, 3=New
        
        // Create a temporary column to store the converted values
        $this->addSql('ALTER TABLE content_recommendation ADD COLUMN state_temp INT');
        
        // Convert the values back:
        // New 1 (New) -> Old 3 (New)
        $this->addSql('UPDATE content_recommendation SET state_temp = 3 WHERE state = 1');
        // New 2 (Accepted) -> Old 1 (Accepted)
        $this->addSql('UPDATE content_recommendation SET state_temp = 1 WHERE state = 2');
        // New 3 (Rejected) -> Old 2 (Rejected)
        $this->addSql('UPDATE content_recommendation SET state_temp = 2 WHERE state = 3');
        
        // Copy converted values back to state column
        $this->addSql('UPDATE content_recommendation SET state = state_temp');
        
        // Drop temporary column
        $this->addSql('ALTER TABLE content_recommendation DROP COLUMN state_temp');
    }
}
