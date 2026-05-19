<?php

declare(strict_types=1);

namespace SwissEph;

final class UtcTime
{
    private const J1972 = 2441317.5;
    private const NLEAP_INIT = 10;

    /**
     * Compatible with swe_utc_to_jd().
     *
     * @return array{rc:int, tt:float, ut1:float, error:string}
     */
    public static function utcToJd(
        int   $year,
        int   $month,
        int   $day,
        int   $hour,
        int   $minute,
        float $second,
        int   $gregflag
    ): array
    {
        $tjdUt1 = SwissDate::julday($year, $month, $day, 0.0, $gregflag);
        $rev = SwissDate::revjul($tjdUt1, $gregflag);

        if ($year !== $rev['year'] || $month !== $rev['month'] || $day !== $rev['day']) {
            return [
                'rc' => SwissDate::ERR,
                'tt' => 0.0,
                'ut1' => 0.0,
                'error' => sprintf('invalid date: year = %d, month = %d, day = %d', $year, $month, $day),
            ];
        }

        if (
            $hour < 0 || $hour > 23
            || $minute < 0 || $minute > 59
            || $second < 0.0 || $second >= 61.0
            || ($second >= 60.0 && ($minute < 59 || $hour < 23 || $tjdUt1 < self::J1972))
        ) {
            return [
                'rc' => SwissDate::ERR,
                'tt' => 0.0,
                'ut1' => 0.0,
                'error' => sprintf('invalid time: %d:%d:%.2F', $hour, $minute, $second),
            ];
        }

        $dhour = (float)$hour + (float)$minute / 60.0 + $second / 3600.0;

        if ($tjdUt1 < self::J1972) {
            $ut1 = SwissDate::julday($year, $month, $day, $dhour, $gregflag);

            return [
                'rc' => SwissDate::OK,
                'tt' => $ut1 + DeltaT::deltatEx($ut1, -1),
                'ut1' => $ut1,
                'error' => '',
            ];
        }

        if ($gregflag === SwissDate::JULIAN_CALENDAR) {
            $gregflag = SwissDate::GREGORIAN_CALENDAR;
            $rev = SwissDate::revjul($tjdUt1, $gregflag);
            $year = $rev['year'];
            $month = $rev['month'];
            $day = $rev['day'];
        }

        $ndat = $year * 10000 + $month * 100 + $day;
        $nleap = self::NLEAP_INIT;

        foreach (self::leapSeconds() as $leapDate) {
            if ($ndat <= $leapDate) {
                break;
            }

            $nleap++;
        }

        $deltaSeconds = DeltaT::deltatEx($tjdUt1, -1) * 86400.0;

        if ($deltaSeconds - (float)$nleap - 32.184 >= 1.0) {
            $ut1 = $tjdUt1 + $dhour / 24.0;

            return [
                'rc' => SwissDate::OK,
                'tt' => $ut1 + DeltaT::deltatEx($ut1, -1),
                'ut1' => $ut1,
                'error' => '',
            ];
        }

        if ($second >= 60.0 && !in_array($ndat, self::leapSeconds(), true)) {
            return [
                'rc' => SwissDate::ERR,
                'tt' => 0.0,
                'ut1' => 0.0,
                'error' => sprintf('invalid time (no leap second!): %d:%d:%.2F', $hour, $minute, $second),
            ];
        }

        $d = $tjdUt1 - self::J1972;
        $d += (float)$hour / 24.0 + (float)$minute / 1440.0 + $second / 86400.0;

        $tjdEt1972 = self::J1972 + (32.184 + self::NLEAP_INIT) / 86400.0;
        $tt = $tjdEt1972 + $d + (float)($nleap - self::NLEAP_INIT) / 86400.0;

        $deltaT = DeltaT::deltatEx($tt, -1);
        $ut1 = $tt - DeltaT::deltatEx($tt - $deltaT, -1);
        $ut1 = $tt - DeltaT::deltatEx($ut1, -1);

        return [
            'rc' => SwissDate::OK,
            'tt' => $tt,
            'ut1' => $ut1,
            'error' => '',
        ];
    }

    /**
     * Compatible with swe_jdet_to_utc().
     *
     * @return array{year:int, month:int, day:int, hour:int, minute:int, second:float}
     */
    public static function jdetToUtc(float $tjdEt, int $gregflag): array
    {
        $tjdEt1972 = self::J1972 + (32.184 + self::NLEAP_INIT) / 86400.0;

        $deltaT = DeltaT::deltatEx($tjdEt, -1);
        $tjdUt = $tjdEt - DeltaT::deltatEx($tjdEt - $deltaT, -1);
        $tjdUt = $tjdEt - DeltaT::deltatEx($tjdUt, -1);

        if ($tjdEt < $tjdEt1972) {
            return self::jdToClock($tjdUt, $gregflag);
        }

        $revBefore = SwissDate::revjul($tjdUt - 1.0, SwissDate::GREGORIAN_CALENDAR);
        $ndat = $revBefore['year'] * 10000 + $revBefore['month'] * 100 + $revBefore['day'];

        $nleap = 0;
        $leapSeconds = self::leapSeconds();

        foreach ($leapSeconds as $leapDate) {
            if ($ndat <= $leapDate) {
                break;
            }

            $nleap++;
        }

        $second60 = 0;

        if ($nleap < count($leapSeconds)) {
            $leapDate = $leapSeconds[$nleap];
            $leapYear = intdiv($leapDate, 10000);
            $leapMonth = intdiv($leapDate % 10000, 100);
            $leapDay = $leapDate % 100;

            $tjd = SwissDate::julday($leapYear, $leapMonth, $leapDay, 0.0, SwissDate::GREGORIAN_CALENDAR);
            $nextDay = SwissDate::revjul($tjd + 1.0, SwissDate::GREGORIAN_CALENDAR);

            $dret = self::utcToJd(
                $nextDay['year'],
                $nextDay['month'],
                $nextDay['day'],
                0,
                0,
                0.0,
                SwissDate::GREGORIAN_CALENDAR
            );

            $diff = $tjdEt - $dret['tt'];

            if ($diff >= 0.0) {
                $nleap++;
            } elseif ($diff > -1.0 / 86400.0) {
                $second60 = 1;
            }
        }

        $tjd = self::J1972 + ($tjdEt - $tjdEt1972) - (float)($nleap + $second60) / 86400.0;
        $result = self::jdToClock($tjd, SwissDate::GREGORIAN_CALENDAR);
        $result['second'] += $second60;

        $deltaT = DeltaT::deltatEx($tjdEt, -1);
        $deltaT = DeltaT::deltatEx($tjdEt - $deltaT, -1);

        if ($deltaT * 86400.0 - (float)($nleap + self::NLEAP_INIT) - 32.184 >= 1.0) {
            $result = self::jdToClock($tjdEt - $deltaT, SwissDate::GREGORIAN_CALENDAR);
        }

        if ($gregflag === SwissDate::JULIAN_CALENDAR) {
            $dateJd = SwissDate::julday(
                $result['year'],
                $result['month'],
                $result['day'],
                0.0,
                SwissDate::GREGORIAN_CALENDAR
            );

            $jul = SwissDate::revjul($dateJd, SwissDate::JULIAN_CALENDAR);
            $result['year'] = $jul['year'];
            $result['month'] = $jul['month'];
            $result['day'] = $jul['day'];
        }

        return $result;
    }

    /**
     * Compatible with swe_jdut1_to_utc().
     *
     * @return array{year:int, month:int, day:int, hour:int, minute:int, second:float}
     */
    public static function jdut1ToUtc(float $tjdUt, int $gregflag): array
    {
        return self::jdetToUtc($tjdUt + DeltaT::deltatEx($tjdUt, -1), $gregflag);
    }

    /**
     * @return array{year:int, month:int, day:int, hour:int, minute:int, second:float}
     */
    private static function jdToClock(float $jd, int $gregflag): array
    {
        $rev = SwissDate::revjul($jd, $gregflag);
        $hour = (int)$rev['hour'];
        $minutesFloat = ($rev['hour'] - (float)$hour) * 60.0;
        $minute = (int)$minutesFloat;
        $second = ($minutesFloat - (float)$minute) * 60.0;

        return [
            'year' => $rev['year'],
            'month' => $rev['month'],
            'day' => $rev['day'],
            'hour' => $hour,
            'minute' => $minute,
            'second' => $second,
        ];
    }

    /**
     * Leap seconds inserted at the end of these Gregorian dates.
     *
     * @return list<int>
     */
    private static function leapSeconds(): array
    {
        return [
            19720630,
            19721231,
            19731231,
            19741231,
            19751231,
            19761231,
            19771231,
            19781231,
            19791231,
            19810630,
            19820630,
            19830630,
            19850630,
            19871231,
            19891231,
            19901231,
            19920630,
            19930630,
            19940630,
            19951231,
            19970630,
            19981231,
            20051231,
            20081231,
            20120630,
            20150630,
            20161231,
        ];
    }


}