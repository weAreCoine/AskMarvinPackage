<?php

declare(strict_types=1);

namespace Marvin\Ask\Enums;

/**
 * Return durations in seconds
 */
enum DurationInSeconds: int
{
    case MINUTE = 60;
    case HOUR = 3600;
    case DAY = 86400;
    case WEEK = 604800;
    case MONTH = 2592000;
    case YEAR = 31536000;

    public static function inSeconds(
        int $seconds = 0,
        int $minutes = 0,
        int $hours = 0,
        int $days = 0,
        $weeks = 0,
        $months = 0,
        $years = 0
    ): int {
        return $seconds + self::minutes($minutes) + self::hours($hours) + self::days($days) + self::weeks($weeks) + self::months($months) + self::years($years);
    }

    public static function minutes(int $minutesCount = 1): int
    {
        return self::minute() * $minutesCount;
    }

    public static function minute(): int
    {
        return DurationInSeconds::MINUTE->value;
    }

    public static function hours(int $hoursCount = 1): int
    {
        return self::hour() * $hoursCount;
    }

    public static function hour(): int
    {
        return DurationInSeconds::HOUR->value;
    }

    public static function days(int $daysCount = 1): int
    {
        return self::day() * $daysCount;
    }

    public static function day(): int
    {
        return DurationInSeconds::DAY->value;
    }

    public static function weeks(int $weeksCount = 1): int
    {
        return self::week() * $weeksCount;
    }

    public static function week(): int
    {
        return DurationInSeconds::WEEK->value;
    }

    public static function months(int $monthsCount = 1): int
    {
        return self::month() * $monthsCount;
    }

    public static function month(): int
    {
        return DurationInSeconds::MONTH->value;
    }

    public static function years(int $yearsCount = 1): int
    {
        return self::year() * $yearsCount;
    }

    public static function year(): int
    {
        return DurationInSeconds::YEAR->value;
    }
}
