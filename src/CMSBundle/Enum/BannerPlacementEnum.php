<?php

namespace App\CMSBundle\Enum;

enum BannerPlacementEnum: int
{
    case HOME_PAGE_SLIDER = 1;
//    case HOME_PAGE_TOP_2_ITEM = 2;
    case HOME_PAGE_MIDDLE_3_ITEMS = 3;
    case HOME_PAGE_MIDDLE_2_ITEMS = 4;
    case HOME_PAGE_UNDER_MIDDLE_2_ITEMS = 5;
    case HOME_PAGE_UNDER_MIDDLE_FULL_WIDTH = 6;
    case HOME_PAGE_BOTTOM_FULL_WIDTH = 7;

    function name(): string
    {
        return match ($this) {
            self::HOME_PAGE_SLIDER => "Home Page - Slider (400px * 300px)",
//            self::HOME_PAGE_TOP_2_ITEM => "Home Page - Top 2 items per row (800px * 400px)",
            self::HOME_PAGE_MIDDLE_3_ITEMS => "Home Page - Middle 3 items per row (1190px * 910px)",
            self::HOME_PAGE_MIDDLE_2_ITEMS => "Home Page - Middle 2 items per row (800px * 400px)",
            self::HOME_PAGE_UNDER_MIDDLE_2_ITEMS => "Home Page - Under Middle 2 items per row (800px * 400px)",
            self::HOME_PAGE_UNDER_MIDDLE_FULL_WIDTH => "Home Page - Under Middle full width (1620px * 400px)",
            self::HOME_PAGE_BOTTOM_FULL_WIDTH => "Home Page - Bottom full width (1620px * 450px)",
        };
    }

    function dimension(): array
    {
        return match ($this) {
            self::HOME_PAGE_SLIDER => ["width" => 400, "height" => 300],
            /*self::HOME_PAGE_TOP_2_ITEM,*/ self::HOME_PAGE_UNDER_MIDDLE_2_ITEMS, self::HOME_PAGE_MIDDLE_2_ITEMS => ["width" => 800, "height" => 400],
            self::HOME_PAGE_MIDDLE_3_ITEMS => ["width" => 1190, "height" => 910],
            self::HOME_PAGE_UNDER_MIDDLE_FULL_WIDTH => ["width" => 1620, "height" => 400],
            self::HOME_PAGE_BOTTOM_FULL_WIDTH => ["width" => 1620, "height" => 450],
        };
    }

}
