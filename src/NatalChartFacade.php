<?php

declare(strict_types=1);

namespace SwissEph;

final class NatalChartFacade
{
    /**
     * Calculates a natal chart from local civil date and time.
     *
     * The timezone is an offset from UTC in hours. For example, Moscow winter
     * time is +3.0, New York EST is -5.0.
     *
     * @param array<int, int> $bodies
     */
    public static function fromLocalDateTime(
        int        $year,
        int        $month,
        int        $day,
        int        $hour,
        int        $minute,
        float      $second,
        float      $timezone,
        float      $geoLat,
        float      $geoLon,
        string|int $houseSystem = Houses::HSYS_PLACIDUS,
        array      $bodies = NatalChartCalculator::DEFAULT_BODIES,
        int        $flags = Catalog::SEFLG_DEFAULTEPH,
        ?AspectSet $aspectSet = null,
        int        $calendar = SwissDate::GREGORIAN_CALENDAR,
    ): NatalChart
    {
        $utc = SwissDate::utcTimeZone($year, $month, $day, $hour, $minute, $second, $timezone);
        $utcHour = $utc['hour'] + $utc['minute'] / 60.0 + $utc['second'] / 3600.0;
        $tjdUt = SwissDate::julday($utc['year'], $utc['month'], $utc['day'], $utcHour, $calendar);

        return NatalChartCalculator::calculate(
            $tjdUt,
            $geoLat,
            $geoLon,
            $houseSystem,
            $bodies,
            $flags,
            $aspectSet
        );
    }

    /**
     * @param array<int, int> $bodies
     */
    public static function svgFromLocalDateTime(
        int        $year,
        int        $month,
        int        $day,
        int        $hour,
        int        $minute,
        float      $second,
        float      $timezone,
        float      $geoLat,
        float      $geoLon,
        string|int $houseSystem = Houses::HSYS_PLACIDUS,
        array      $bodies = NatalChartCalculator::DEFAULT_BODIES,
        int        $flags = Catalog::SEFLG_DEFAULTEPH,
        ?AspectSet $aspectSet = null,
        int        $calendar = SwissDate::GREGORIAN_CALENDAR,
        int        $size = 720,
    ): string
    {
        return NatalChartRenderer::renderSvg(
            self::fromLocalDateTime(
                $year,
                $month,
                $day,
                $hour,
                $minute,
                $second,
                $timezone,
                $geoLat,
                $geoLon,
                $houseSystem,
                $bodies,
                $flags,
                $aspectSet,
                $calendar
            ),
            $size
        );
    }
}