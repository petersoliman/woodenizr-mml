<?php

namespace App\ProductBundle\Enum;

/**
 * Content Recommendation State Enum
 * 
 * Defines the possible states for content recommendations in the system.
 * Each recommendation goes through a workflow from NEW to either ACCEPTED or REJECTED.
 * 
 * State Values:
 * - NEW (1): Initial state when a recommendation is first created
 * - ACCEPTED (2): Recommendation has been reviewed and approved
 * - REJECTED (3): Recommendation has been reviewed and declined
 * 
 * @author System Generated
 * @since 2025-08-09
 */
enum ContentRecommendationStateEnum: int
{
    /**
     * NEW state (1) - Default state for new recommendations
     * Indicates the recommendation is waiting for review
     */
    case NEW = 1;
    
    /**
     * ACCEPTED state (2) - Recommendation has been approved
     * Indicates the recommendation has been reviewed and accepted for implementation
     */
    case ACCEPTED = 2;
    
    /**
     * REJECTED state (3) - Recommendation has been declined
     * Indicates the recommendation has been reviewed but was not accepted
     */
    case REJECTED = 3;

    /**
     * Get human-readable label for the state
     * 
     * @return string User-friendly label for display
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::ACCEPTED => 'Accepted',
            self::REJECTED => 'Rejected',
            self::NEW => 'New',
        };
    }

    /**
     * Get CSS badge class for visual representation
     * 
     * @return string CSS class name for styling state badges
     */
    public function getBadgeClass(): string
    {
        return match ($this) {
            self::ACCEPTED => 'label-success',  // Green badge for accepted
            self::REJECTED => 'label-danger',   // Red badge for rejected
            self::NEW => 'label-info',          // Blue badge for new
        };
    }

    /**
     * Get all state choices for form dropdowns
     * 
     * @return array<string, int> Array of [label => value] pairs for forms
     */
    public static function getChoices(): array
    {
        return [
            'New' => self::NEW->value,          // 1
            'Accepted' => self::ACCEPTED->value, // 2
            'Rejected' => self::REJECTED->value, // 3
        ];
    }

    /**
     * Get all enum cases as array
     * 
     * @return array<ContentRecommendationStateEnum> All available enum cases
     */
    public static function getAll(): array
    {
        return [
            self::NEW,      // 1 - Default state
            self::ACCEPTED, // 2 - Approved state
            self::REJECTED, // 3 - Declined state
        ];
    }
}
