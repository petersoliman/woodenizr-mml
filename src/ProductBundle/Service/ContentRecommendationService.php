<?php

namespace App\ProductBundle\Service;

use App\ProductBundle\Form\Filter\ContentRecommendationFilterType;
use Symfony\Component\Form\FormInterface;

class ContentRecommendationService
{
    /**
     * Collect search data from filter form
     */
    public function collectSearchData(FormInterface $filterForm): \stdClass
    {
        $search = new \stdClass();

        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $data = $filterForm->getData();

            if (isset($data['productTitle']) && !empty($data['productTitle'])) {
                $search->productTitle = $data['productTitle'];
            }

            if (isset($data['productId']) && $data['productId'] !== null) {
                $search->productId = $data['productId'];
            }

            if (isset($data['state']) && $data['state'] !== '') {
                $search->state = $data['state'];
            }

            if (isset($data['createdFrom']) && $data['createdFrom'] !== null) {
                $search->createdFrom = $data['createdFrom']->format('Y-m-d');
            }

            if (isset($data['createdTo']) && $data['createdTo'] !== null) {
                $search->createdTo = $data['createdTo']->format('Y-m-d');
            }
        }

        return $search;
    }

    /**
     * Validate JSON content
     */
    public function validateJsonContent(?string $jsonContent): array
    {
        if (empty($jsonContent)) {
            return ['valid' => true, 'data' => null, 'error' => null];
        }

        $decoded = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'valid' => false,
                'data' => null,
                'error' => 'Invalid JSON format: ' . json_last_error_msg()
            ];
        }

        return ['valid' => true, 'data' => $decoded, 'error' => null];
    }

    /**
     * Format JSON for display
     */
    public function formatJsonForDisplay(?array $data): ?string
    {
        if (empty($data)) {
            return null;
        }

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}




