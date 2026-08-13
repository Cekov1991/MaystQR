<?php

namespace App\Support;

class BrandColor
{
    /**
     * Light blue brand palette. Shades 50-500 are the brand palette stops
     * (#FBFDFE, #D4EBF2, #ADD8E6, #86C5DA, #5FB3CE, #3A9FC0); 600-950 are
     * derived from #3A9FC0 with Filament's shade intensity map.
     */
    public const LIGHT_BLUE = [
        50 => '251, 253, 254',
        100 => '212, 235, 242',
        200 => '173, 216, 230',
        300 => '134, 197, 218',
        400 => '95, 179, 206',
        500 => '58, 159, 192',
        600 => '52, 143, 173',
        700 => '44, 119, 144',
        800 => '35, 95, 115',
        900 => '28, 78, 94',
        950 => '17, 48, 58',
    ];
}
