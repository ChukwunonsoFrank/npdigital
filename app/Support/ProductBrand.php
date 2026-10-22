<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Brand logos shown on product imagery.
 *
 * Brand keys map to the logo files stored in public/brands.
 */
class ProductBrand
{
    /** @var list<string> */
    public const BRANDS = [
        'amazon',
        'bestbuy',
        'costco',
        'ebay',
        'etsy',
        'lowes',
        'target',
        'walmart',
        'wayfair',
    ];

    public static function random(): string
    {
        return self::BRANDS[array_rand(self::BRANDS)];
    }

    /**
     * Pick a stable brand for products that have no stored brand.
     */
    public static function forSeed(string $seed): string
    {
        return self::BRANDS[abs(crc32($seed)) % count(self::BRANDS)];
    }

    /**
     * Use the stored brand when valid, otherwise fall back to a stable one.
     */
    public static function resolve(?string $brand, string $seed): string
    {
        return self::isValid($brand) ? $brand : self::forSeed($seed);
    }

    public static function isValid(?string $brand): bool
    {
        return $brand !== null && in_array($brand, self::BRANDS, true);
    }

    public static function logo(?string $brand): ?string
    {
        return self::isValid($brand) ? asset('brands/'.$brand.'.png') : null;
    }

    public static function label(?string $brand): ?string
    {
        return match ($brand) {
            'bestbuy' => 'Best Buy',
            'lowes' => "Lowe's",
            default => $brand === null ? null : Str::title($brand),
        };
    }
}
