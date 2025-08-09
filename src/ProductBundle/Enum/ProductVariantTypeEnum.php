<?php

namespace App\ProductBundle\Enum;

enum ProductVariantTypeEnum: string
{
    case TEXT = "text";
    case COLOR = "color";
    case IMAGE = "image";

    public function name(): string
    {
        return match ($this) {
            self::TEXT => "Text",
            self::COLOR => "Color",
            self::IMAGE => "Image",
        };
    }
}
