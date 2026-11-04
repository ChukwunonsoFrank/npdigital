<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * The daily window in which users may work on tasks.
 *
 * Times are evaluated in US Eastern time, which is EST for most of the year
 * and EDT during daylight saving.
 */
class OperatingHours
{
    public const TIMEZONE = 'America/New_York';

    public const OPENS_AT_HOUR = 10;

    public const CLOSES_AT_HOUR = 22;

    public static function isOpen(): bool
    {
        $hour = Carbon::now(self::TIMEZONE)->hour;

        return $hour >= self::OPENS_AT_HOUR && $hour < self::CLOSES_AT_HOUR;
    }

    public static function closedMessage(): string
    {
        return 'You can only work on tasks between 10 AM and 10 PM EST. Please come back after 10 AM and before 10 PM EST.';
    }
}
