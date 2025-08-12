<?php

namespace App\ProductBundle\Enum;

enum ProductBulkGenerateTypeEnum: int
{
    case SEO = 1;
    case PRICES = 2;
    case GENERAL = 3;

    public function getLabel(): string
    {
        return match ($this) {
            self::SEO => 'SEO',
            self::PRICES => 'Prices',
            self::GENERAL => 'General',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::SEO => 'SEO metadata and optimization',
            self::PRICES => 'Product pricing and promotions',
            self::GENERAL => 'General product information',
        };
    }

    public function getBadgeClass(): string
    {
        return match ($this) {
            self::SEO => 'label-info',
            self::PRICES => 'label-success',
            self::GENERAL => 'label-primary',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::SEO => 'icon-search4',
            self::PRICES => 'icon-price-tag2',
            self::GENERAL => 'icon-cog',
        };
    }

    public static function getChoices(): array
    {
        return [
            'SEO' => self::SEO->value,
            'Prices' => self::PRICES->value,
            'General' => self::GENERAL->value,
        ];
    }

    public static function getAll(): array
    {
        return [
            self::SEO,
            self::PRICES,
            self::GENERAL,
        ];
    }
}




