<?php

namespace App\ThreeSixtyViewBundle\Enums;

enum ThreeSixtyViewImageExtensionEnums: string
{
    case IMAGE_EXTENSION_PNG = "image/png";
    case IMAGE_EXTENSION_JPG = "image/jpg";
    case IMAGE_EXTENSION_JPEG = "image/jpeg";

    function name(): string
    {
        return match ($this) {
            self::IMAGE_EXTENSION_JPG => "jpg",
            self::IMAGE_EXTENSION_JPEG => "jpeg",
            self::IMAGE_EXTENSION_PNG => "png",
        };
    }
}
