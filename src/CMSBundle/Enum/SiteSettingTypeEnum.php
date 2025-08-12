<?php

namespace App\CMSBundle\Enum;

enum SiteSettingTypeEnum: string
{
    case HTML_TAG = "html-tag";
    case NUMBER = "number";
    case TEXT = "text";
    case URL = "url";
    case EMAIL = "email";
    case COLOR_CODE = "color-code";
    case SVG_CODE = "svg-code";
    case BOOLEAN = "boolean";
    case IMAGE = "image";
    case FAVICON = "favicon";

    function name(): string
    {
        return match ($this) {
            self::HTML_TAG => "HTML Tag",
            self::NUMBER => "Number",
            self::TEXT => "Text",
            self::URL => "URL",
            self::EMAIL => "Email",
            self::COLOR_CODE => "Color",
            self::SVG_CODE => "SVG Color",
            self::BOOLEAN => "Yes/No",
            self::IMAGE => "Image",
            self::FAVICON => "Favicon",
        };
    }


}
