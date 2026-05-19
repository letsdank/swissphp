<?php

namespace SwissEph;

final class SwissDate
{
    public const JULIAN_CALENDAR = 0;
    public const GREGORIAN_CALENDAR = 1;

    public const OK = 0;
    public const ERR = -1;

    public static function julday(
        int   $year,
        int   $month,
        int   $day,
        float $hour,
        int   $gregflag
    ): float
    {
        $u = (float)$year;

        if ($month < 3) {
            $u -= 1.0;
        }

        $u0 = $u + 4712.0;
        $u1 = (float)$month + 1.0;

        if ($u1 < 4.0) {
            $u1 += 12.0;
        }

        $jd = floor($u0 * 365.25)
            + floor(30.6 * $u1 + 0.000001)
            + $day
            + $hour / 24.0
            - 63.5;

        if ($gregflag === self::GREGORIAN_CALENDAR) {
            $u2 = floor(abs($u) / 100.0) - floor(abs($u) / 400.0);

            if ($u < 0.0) {
                $u2 = -$u2;
            }

            $jd = $jd - $u2 + 2.0;

            if (
                $u < 0.0
                && $u / 100.0 === floor($u / 100.0)
                && $u / 400.0 !== floor($u / 400.0)
            ) {
                $jd -= 1.0;
            }
        }

        return $jd;
    }

    /**
     * @return array{year:int, month:int, day:int, hour:float}
     */
    public static function revjul(float $jd, int $gregflag): array
    {
        $u0 = $jd + 32082.5;

        if ($gregflag === self::GREGORIAN_CALENDAR) {
            $u1 = $u0 + floor($u0 / 36525.0) - floor($u0 / 146100.0) - 38.0;

            if ($jd >= 1830691.5) {
                $u1 += 1.0;
            }

            $u0 = $u0 + floor($u1 / 36525.0) - floor($u1 / 146100.0) - 38.0;
        }

        $u2 = floor($u0 + 123.0);
        $u3 = floor(($u2 - 122.2) / 365.25);
        $u4 = floor(($u2 - floor(365.25 * $u3)) / 30.6001);

        $month = (int)($u4 - 1.0);

        if ($month > 12) {
            $month -= 12;
        }

        $day = (int)($u2 - floor(365.25 * $u3) - floor(30.6001 * $u4));
        $year = (int)($u3 + floor(($u4 - 2.0) / 12.0) - 4800.0);
        $hour = ($jd - floor($jd + 0.5) + 0.5) * 24.0;

        return [
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'hour' => $hour,
        ];
    }

    /**
     * Compatible with swe_date_conversion().
     *
     * @return array{rc:int, jd:float}
     */
    public static function dateConversion(
        int    $year,
        int    $month,
        int    $day,
        float  $uttime,
        string $calendar
    ): array
    {
        $gregflag = $calendar === 'g'
            ? self::GREGORIAN_CALENDAR
            : self::JULIAN_CALENDAR;

        $jd = self::julday($year, $month, $day, $uttime, $gregflag);
        $rev = self::revjul($jd, $gregflag);

        $rc = (
            $rev['year'] === $year
            && $rev['month'] === $month
            && $rev['day'] === $day
        ) ? self::OK : self::ERR;

        return [
            'rc' => $rc,
            'jd' => $jd,
        ];
    }

    /**
     * Compatible with swe_utc_time_zone().
     *
     * For local time to UTC, pass positive timezone east of Greenwich.
     * For UTC to local time, pass negative timezone ease of Greenwich.
     *
     * @return array{year:int, month:int, day:int, hour:int, minute:int, second:float}
     */
    public static function utcTimeZone(
        int   $year,
        int   $month,
        int   $day,
        int   $hour,
        int   $minute,
        float $second,
        float $timezone
    ): array
    {
        $haveLeapSecond = false;

        if ($second >= 60.0) {
            $haveLeapSecond = true;
            $second -= 1.0;
        }

        $dhour = (float)$hour + (float)$minute / 60.0 + $second / 3600.0;
        $tjd = self::julday($year, $month, $day, 0.0, self::GREGORIAN_CALENDAR);

        $dhour -= $timezone;

        if ($dhour < 0.0) {
            $tjd -= 1.0;
            $dhour += 24.0;
        }

        if ($dhour >= 24.0) {
            $tjd += 1.0;
            $dhour -= 24.0;
        }

        $rev = self::revjul($tjd + 0.001, self::GREGORIAN_CALENDAR);

        $outHour = (int)$dhour;
        $d = ($dhour - (float)$outHour) * 60.0;
        $outMinute = (int)$d;
        $outSecond = ($d - (float)$outMinute) * 60.0;

        if ($haveLeapSecond) {
            $outSecond += 1.0;
        }

        return [
            'year' => $rev['year'],
            'month' => $rev['month'],
            'day' => $rev['day'],
            'hour' => $outHour,
            'minute' => $outMinute,
            'second' => $outSecond,
        ];
    }
}