<?php

namespace App\ProductBundle\Entity;

use App\ProductBundle\Enum\ContentRecommendationStateEnum;
use App\ProductBundle\Repository\ContentRecommendationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use PN\ServiceBundle\Interfaces\DateTimeInterface;
use PN\ServiceBundle\Interfaces\UUIDInterface;
use PN\ServiceBundle\Model\DateTimeTrait;
use PN\ServiceBundle\Model\UuidTrait;
use PN\ServiceBundle\Model\VirtualDeleteTrait;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="content_recommendation")
 * @ORM\Entity(repositoryClass=ContentRecommendationRepository::class)
 * @Gedmo\Loggable
 */
class ContentRecommendation implements DateTimeInterface, UUIDInterface
{
    use VirtualDeleteTrait,
        DateTimeTrait,
        UuidTrait;

    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @Gedmo\Versioned
     * @ORM\ManyToOne(targetEntity="Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private ?Product $product = null;

    /**
     * Recommended content data stored as JSON
     * 
     * This field can store any valid JSON structure such as:
     * - {"title": "New Product Title", "description": "Updated description"}
     * - {"seo": {"meta_title": "SEO Title", "meta_description": "SEO Desc"}}
     * - {"prices": [{"currency": "USD", "amount": 100}]}
     * - Complex nested objects and arrays
     * 
     * The data is automatically serialized/deserialized by Doctrine's JSON type.
     * 
     * @Gedmo\Versioned
     * @ORM\Column(name="recommended", type="json", nullable=true)
     */
    private ?array $recommended = null;

    /**
     * Content recommendation state
     * 
     * Possible values:
     * - 1: NEW (default) - New recommendation waiting for review
     * - 2: ACCEPTED - Recommendation has been approved and accepted
     * - 3: REJECTED - Recommendation has been reviewed and rejected
     * 
     * @see ContentRecommendationStateEnum for enum values and labels
     * 
     * @Gedmo\Versioned
     * @ORM\Column(name="state", type="integer", length=1, nullable=false)
     */
    private int $state = 1; // Default to NEW state (1)

    /**
     * @ORM\Column(name="notes", type="text", nullable=true)
     */
    private ?string $notes = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get recommended content data as array (JSON-compatible format)
     * 
     * This method returns the JSON data as a PHP array that can be:
     * - Used directly in PHP code
     * - Serialized to JSON using json_encode()
     * - Passed to Doctrine for database storage
     * 
     * @return array|null The JSON data as PHP array, or null if not set
     */
    public function getRecommended(): ?array
    {
        return $this->recommended;
    }

    /**
     * Set recommended content data (accepts multiple formats)
     * 
     * This method accepts:
     * - PHP array: ['key' => 'value', 'nested' => ['data']]
     * - JSON string: '{"key": "value", "nested": {"data": true}}'
     * - null: clears the field
     * 
     * @param array|string|null $recommended Data in array or JSON string format
     * @return self
     * @throws \InvalidArgumentException if JSON string is invalid
     */
    public function setRecommended($recommended): self
    {
        if ($recommended === null) {
            $this->recommended = null;
            return $this;
        }

        // If it's already an array, use it directly
        if (is_array($recommended)) {
            $this->recommended = $recommended;
            return $this;
        }

        // If it's a string, try to decode as JSON
        if (is_string($recommended)) {
            $decoded = json_decode($recommended, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('Invalid JSON provided: ' . json_last_error_msg());
            }
            
            $this->recommended = $decoded;
            return $this;
        }

        // Invalid type
        throw new \InvalidArgumentException('Recommended data must be array, JSON string, or null');
    }

    /**
     * Get recommended content as formatted JSON string
     * 
     * Returns a pretty-printed JSON string with proper formatting
     * for display purposes or API responses.
     * 
     * @param bool $prettyPrint Whether to format with indentation (default: true)
     * @return string|null JSON string representation of the data
     */
    public function getRecommendedAsJson(bool $prettyPrint = true): ?string
    {
        if ($this->recommended === null) {
            return null;
        }

        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        if ($prettyPrint) {
            $flags |= JSON_PRETTY_PRINT;
        }

        return json_encode($this->recommended, $flags);
    }

    /**
     * Get specific value from recommended JSON data
     * 
     * Allows easy access to nested values using dot notation.
     * Examples: 'title', 'seo.meta_title', 'prices.0.currency'
     * 
     * @param string $key Dot-notation key to retrieve
     * @param mixed $default Default value if key not found
     * @return mixed The value or default if not found
     */
    public function getRecommendedValue(string $key, $default = null)
    {
        if ($this->recommended === null) {
            return $default;
        }

        $keys = explode('.', $key);
        $value = $this->recommended;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Set specific value in recommended JSON data
     * 
     * Allows setting nested values using dot notation.
     * Creates intermediate arrays as needed.
     * 
     * @param string $key Dot-notation key to set
     * @param mixed $value Value to set
     * @return self
     */
    public function setRecommendedValue(string $key, $value): self
    {
        if ($this->recommended === null) {
            $this->recommended = [];
        }

        $keys = explode('.', $key);
        $current = &$this->recommended;

        // Navigate to the parent of the target key
        for ($i = 0; $i < count($keys) - 1; $i++) {
            $k = $keys[$i];
            if (!is_array($current)) {
                $current = [];
            }
            if (!array_key_exists($k, $current)) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }

        // Set the final value
        $finalKey = end($keys);
        $current[$finalKey] = $value;
        
        return $this;
    }

    /**
     * Check if recommended data has a specific key
     * 
     * @param string $key Dot-notation key to check
     * @return bool True if key exists
     */
    public function hasRecommendedKey(string $key): bool
    {
        if ($this->recommended === null) {
            return false;
        }

        $keys = explode('.', $key);
        $value = $this->recommended;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return false;
            }
            $value = $value[$k];
        }

        return true;
    }

    /**
     * Validate that the recommended data is valid JSON structure
     * 
     * @return bool True if data is valid JSON-compatible
     */
    public function isRecommendedValid(): bool
    {
        if ($this->recommended === null) {
            return true;
        }

        // Try to encode and decode to verify JSON compatibility
        $encoded = json_encode($this->recommended);
        if ($encoded === false) {
            return false;
        }

        $decoded = json_decode($encoded, true);
        return json_last_error() === JSON_ERROR_NONE;
    }

    public function getState(): int
    {
        return $this->state;
    }

    public function setState(int $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getStateEnum(): ContentRecommendationStateEnum
    {
        return ContentRecommendationStateEnum::from($this->state);
    }

    public function setStateEnum(ContentRecommendationStateEnum $state): self
    {
        $this->state = $state->value;

        return $this;
    }

    public function getStateLabel(): string
    {
        return $this->getStateEnum()->getLabel();
    }

    public function getStateBadgeClass(): string
    {
        return $this->getStateEnum()->getBadgeClass();
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    public function isAccepted(): bool
    {
        return $this->state === ContentRecommendationStateEnum::ACCEPTED->value;
    }

    public function isRejected(): bool
    {
        return $this->state === ContentRecommendationStateEnum::REJECTED->value;
    }

    public function isNew(): bool
    {
        return $this->state === ContentRecommendationStateEnum::NEW->value;
    }

    public function __toString(): string
    {
        return sprintf(
            'Content Recommendation #%d for %s',
            $this->id ?? 0,
            $this->product ? $this->product->getTitle() : 'Unknown Product'
        );
    }
}
