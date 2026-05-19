<?php

declare(strict_types=1);

namespace SwissEph;

final class SolarTime
{
    /**
     * Equation of time.
     *
     * Returns E = LAT - LMT in days.
     *
     * Compatible API with swe_time_equ(), but currently uses a compact
     * Meeus/NOAA approximation until the full Swiss Ephemeris Sun calculation
     * is ported.
     *
     * @return array{rc:int, e:float,error:string}
     */
    public static function timeEquation(float $tjdUt): array
    {
        $t = ($tjdUt - 2451545.0) / 36525.0;

        $epsilon = deg2rad(23.439291 - 0.0130042 * $t);

        $l0 = self::normalizeDegrees(
            280.46646 + $t * (36000.76983 + $t * 0.0003032)
        );

        $eccentricity = 0.016708634 - $t * (0.000042037 + 0.0000001267 * $t);

        $meanAnomaly = self::normalizeDegrees(
            357.52911 + $t * (35999.05029 - 0.0001537 * $t)
        );

        $y = tan($epsilon / 2.0);
        $y *= $y;

        $l0Rad = deg2rad($l0);
        $meanAnomalyRad = deg2rad($meanAnomaly);

        $equation = $y * sin(2.0 * $l0Rad)
            - 2.0 * $eccentricity * sin($meanAnomalyRad)
            + 4.0 * $eccentricity * $y * sin($meanAnomalyRad) * cos(2.0 * $l0Rad)
            - 0.5 * $y * $y * sin(4.0 * $l0Rad)
            - 1.25 * $eccentricity * $eccentricity * sin(2.0 * $meanAnomalyRad);

        $minutes = rad2deg($equation) * 4.0;

        return [
            'rc' => SwissDate::OK,
            'e' => $minutes / 1440.0,
            'error' => '',
        ];
    }

    /**
     * Convert Local Mean Time to Local Apparent Time.
     *
     * @return array{rc:int, tjd_lat:float, error:string}
     */
    public static function lmtToLat(float $tjdLmt, float $geoLon): array
    {
        $tjdLmt0 = $tjdLmt - $geoLon / 360.0;
        $equation = self::timeEquation($tjdLmt0);

        return [
            'rc' => $equation['rc'],
            'tjd_lat' => $tjdLmt + $equation['e'],
            'error' => $equation['error'],
        ];
    }

    /**
     * Convert Local Apparent Time to Local Mean Time.
     *
     * @return array{rc:int, tjd_lmt:float, error:string}
     */
    public static function latToLmt(float $tjdLat, float $geoLon): array
    {
        $tjdLmt0 = $tjdLat - $geoLon / 360.0;

        $equation = self::timeEquation($tjdLmt0);
        $equation = self::timeEquation($tjdLmt0 - $equation['e']);
        $equation = self::timeEquation($tjdLmt0 - $equation['e']);

        return [
            'rc' => $equation['rc'],
            'tjd_lmt' => $tjdLat - $equation['e'],
            'error' => $equation['error'],
        ];
    }

    private static function normalizeDegrees(float $degrees): float
    {
        $result = fmod($degrees, 360.0);

        return $result < 0.0 ? $result + 360.0 : $result;
    }
}