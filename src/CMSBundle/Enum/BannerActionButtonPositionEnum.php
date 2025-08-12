<?php

namespace App\CMSBundle\Enum;

enum BannerActionButtonPositionEnum: string
{
    case LEFT = "left";
    case CENTER = "center";
    case RIGHT = "right";

    function name(): string
    {
        return match ($this) {
            self::LEFT => "Left",
            self::CENTER => "Center",
            self::RIGHT => "Right",
        };
    }
}
