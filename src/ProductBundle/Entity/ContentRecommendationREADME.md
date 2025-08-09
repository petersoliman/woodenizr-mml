# ContentRecommendation Entity Documentation

## Overview
The `ContentRecommendation` entity manages content recommendations for products in the system. Each recommendation can be reviewed and either accepted or rejected.

## Entity Structure

### Core Fields
- **id**: Primary key (auto-increment)
- **product**: Relation to Product entity (foreign key: product_id)
- **recommended**: JSON field containing recommendation data
- **state**: Integer field representing the recommendation status
- **notes**: Optional text field for admin notes

### Audit Fields
- **created/creator**: Creation timestamp and user
- **modified/modified_by**: Last modification timestamp and user  
- **deleted/deleted_by**: Soft deletion timestamp and user
- **uuid**: Unique identifier

## State Values

The `state` field uses integer values with specific meanings:

| Value | State | Description | Badge Color |
|-------|-------|-------------|-------------|
| 1 | NEW | Default state for new recommendations waiting for review | Blue (label-info) |
| 2 | ACCEPTED | Recommendation has been reviewed and approved | Green (label-success) |
| 3 | REJECTED | Recommendation has been reviewed and declined | Red (label-danger) |

## Usage Examples

### Creating a New Recommendation
```php
use App\ProductBundle\Entity\ContentRecommendation;
use App\ProductBundle\Enum\ContentRecommendationStateEnum;

$recommendation = new ContentRecommendation();
$recommendation->setProduct($product);
$recommendation->setRecommended(['title' => 'New Title', 'description' => 'Updated description']);
// State defaults to 1 (NEW) automatically
$recommendation->setNotes('Initial recommendation from AI');
```

### Updating State
```php
// Accept a recommendation
$recommendation->setState(ContentRecommendationStateEnum::ACCEPTED->value); // 2

// Reject a recommendation  
$recommendation->setState(ContentRecommendationStateEnum::REJECTED->value); // 3
```

### Querying by State
```php
// Find all new recommendations
$newRecommendations = $repository->findBy(['state' => 1]);

// Find all accepted recommendations
$acceptedRecommendations = $repository->findBy(['state' => 2]);
```

## Enum Helper Methods

The `ContentRecommendationStateEnum` provides utility methods:

```php
// Get human-readable label
ContentRecommendationStateEnum::NEW->getLabel(); // "New"

// Get CSS class for badges
ContentRecommendationStateEnum::ACCEPTED->getBadgeClass(); // "label-success"

// Get choices for forms
ContentRecommendationStateEnum::getChoices(); // ['New' => 1, 'Accepted' => 2, 'Rejected' => 3]
```

## Database Schema

```sql
CREATE TABLE content_recommendation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT DEFAULT NULL,
    recommended JSON DEFAULT NULL,
    state INT(1) NOT NULL DEFAULT 1,
    notes LONGTEXT DEFAULT NULL,
    deleted DATETIME DEFAULT NULL,
    deleted_by VARCHAR(255) DEFAULT NULL,
    created DATETIME NOT NULL,
    creator VARCHAR(255) NOT NULL,
    modified DATETIME NOT NULL,
    modified_by VARCHAR(255) NOT NULL,
    uuid VARCHAR(50) NOT NULL UNIQUE,
    FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE
);
```

## Admin Interface

The entity includes a full CRUD interface accessible at:
- **List**: `/admin/product/admin/content-recommendation`
- **Create**: `/admin/product/admin/content-recommendation/new`
- **View**: `/admin/product/admin/content-recommendation/{id}/show`
- **Edit**: `/admin/product/admin/content-recommendation/{id}/edit`

## Migration History

- **Version20250809131517**: Initial table creation
- **Version20250809135930**: State value mapping update (1=New, 2=Accepted, 3=Rejected)

## Related Files

- **Entity**: `src/ProductBundle/Entity/ContentRecommendation.php`
- **Enum**: `src/ProductBundle/Enum/ContentRecommendationStateEnum.php`
- **Repository**: `src/ProductBundle/Repository/ContentRecommendationRepository.php`
- **Controller**: `src/ProductBundle/Controller/Administration/ContentRecommendationController.php`
- **Form**: `src/ProductBundle/Form/ContentRecommendationType.php`
- **Templates**: `templates/product/admin/contentRecommendation/`




