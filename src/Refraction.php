<?php

declare(strict_types=1);

namespace SwissEph;

final class Refraction
{
    public const DEFAULT_LAPSE_RATE = 0.0065;

    private const EARTH_RADIUS = 6378136.6;

    /**
     * Compatible subset of swe_refrac().
     */
    public static function refrac(
        float $altitude,
        float $pressure,
        float $temperature,
        int   $calcFlag
    ): float
    {
        $pressureTemperatureFactor = $pressure / 1010.0 * 283.0 / (273.0 + $temperature);

        if ($calcFlag === Catalog::SE_TRUE_TO_APP) {
            $trueAltitude = $altitude;

            if ($trueAltitude > 15.0) {
                $a = tan(deg2rad(90.0 - $trueAltitude));
                $refraction = 58.276 * $a - 0.0824 * $a * $a * $a;
                $refraction *= $pressureTemperatureFactor / 3600.0;
            } elseif ($trueAltitude > -5.0) {
                $a = $trueAltitude + 10.3 / ($trueAltitude + 5.11);

                if ($a + 1e-10 >= 90.0) {
                    $refraction = 0.0;
                } else {
                    $refraction = 1.02 / tan(deg2rad($a));
                }

                $refraction *= $pressureTemperatureFactor / 60.0;
            } else {
                $refraction = 0.0;
            }

            $apparentAltitude = $trueAltitude;

            if ($apparentAltitude + $refraction > 0.0) {
                $apparentAltitude += $refraction;
            }

            return $apparentAltitude;
        }

        $apparentAltitude = $altitude;
        $a = $apparentAltitude + 7.31 / ($apparentAltitude + 4.4);

        if ($a + 1e-10 >= 90.0) {
            $refraction = 0.0;
        } else {
            $refraction = 1.0 / tan(deg2rad($a));
            $refraction -= 0.06 * sin(14.7 * $refraction + 13.0);
        }

        $refraction *= $pressureTemperatureFactor / 60.0;
        $trueAltitude = $apparentAltitude;

        if ($apparentAltitude - $refraction > 0.0) {
            $trueAltitude = $apparentAltitude - $refraction;
        }

        return $trueAltitude;
    }

    /**
     * Compatible subset of swe_refrac_extended().
     *
     * @return array{
     *     altitude: float,
     *     trueAltitude: float,
     *     apparentAltitude: float,
     *     refraction: float,
     *     dip: float
     * }
     */
    public static function extended(
        float $altitude,
        float $geoAltitude,
        float $pressure,
        float $temperature,
        float $lapseRate,
        int   $calcFlag
    ): array
    {
        $dip = self::dip($geoAltitude, $pressure, $temperature, $lapseRate);

        if ($altitude > 90.0) {
            $altitude = 180.0 - $altitude;
        }

        if ($calcFlag === Catalog::SE_TRUE_TO_APP) {
            if ($altitude < -10.0) {
                return [
                    'altitude' => $altitude,
                    'trueAltitude' => $altitude,
                    'apparentAltitude' => $altitude,
                    'refraction' => 0.0,
                    'dip' => $dip,
                ];
            }

            $y = $altitude;
            $d = 0.0;
            $yy0 = 0.0;
            $d0 = $d;

            for ($i = 0; $i < 5; $i++) {
                $d = self::astronomicalRefraction($y, $pressure, $temperature);
                $n = $y - $yy0;
                $yy0 = $d - $d0 - $n;

                if ($n != 0.0 && $yy0 != 0.0) {
                    $n = $y - $n * ($altitude + $d - $y) / $yy0;
                } else {
                    $n = $altitude + $d;
                }

                $yy0 = $y;
                $d0 = $d;
                $y = $n;
            }

            $refraction = $d;
            $apparentAltitude = $altitude + $refraction;

            if ($apparentAltitude < $dip) {
                return [
                    'altitude' => $altitude,
                    'trueAltitude' => $altitude,
                    'apparentAltitude' => $altitude,
                    'refraction' => 0.0,
                    'dip' => $dip,
                ];
            }

            return [
                'altitude' => $apparentAltitude,
                'trueAltitude' => $altitude,
                'apparentAltitude' => $apparentAltitude,
                'refraction' => $refraction,
                'dip' => $dip,
            ];
        }

        $refraction = self::astronomicalRefraction($altitude, $pressure, $temperature);
        $trueAltitude = $altitude - $refraction;

        if ($altitude >= $dip) {
            return [
                'altitude' => $trueAltitude,
                'trueAltitude' => $trueAltitude,
                'apparentAltitude' => $altitude,
                'refraction' => $refraction,
                'dip' => $dip,
            ];
        }

        return [
            'altitude' => $altitude,
            'trueAltitude' => $altitude,
            'apparentAltitude' => $altitude,
            'refraction' => 0.0,
            'dip' => $dip,
        ];
    }

    private static function astronomicalRefraction(float $altitude, float $pressure, float $temperature): float
    {
        if ($altitude > 17.904104638432) {
            $refraction = 0.97 / tan(deg2rad($altitude));
        } else {
            $refraction = (34.46 + 4.23 * $altitude + 0.004 * $altitude * $altitude)
                / (1.0 + 0.505 * $altitude + 0.0845 * $altitude * $altitude);
        }

        return (($pressure - 80.0) / 930.0
                / (1.0 + 0.00008 * ($refraction + 39.0) * ($temperature - 10.0))
                * $refraction)
            / 60.0;
    }

    public static function horizonDip(
        float $geoAltitide,
        float $pressure,
        float $temperature,
        float $lapseRate = self::DEFAULT_LAPSE_RATE
    ): float
    {
        return self::dip($geoAltitide, $pressure, $temperature, $lapseRate);
    }

    private static function dip(
        float $geoAltitude,
        float $pressure,
        float $temperature,
        float $lapseRate
    ): float
    {
        $refractionConstant = (0.0342 + $lapseRate) / (0.154 * 0.0238);
        $d = 1.0 - 1.8480 * $refractionConstant * $pressure
            / (273.15 + $temperature)
            / (273.15 + $temperature);

        return -180.0 / M_PI
            * acos(1.0 / (1.0 + $geoAltitude / self::EARTH_RADIUS))
            * sqrt($d);
    }
}