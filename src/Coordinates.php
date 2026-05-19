<?php

declare(strict_types=1);

namespace SwissEph;

final class Coordinates
{
    /**
     * Compatible with swe_cotrans().
     *
     * Input and output are polar coordinates in degrees:
     * [longitude, latitude, radius].
     *
     * For ecliptic to equatorial, eps must be negative.
     * For equatorial to ecliptic, eps must be positive.
     *
     * @param array{0:float, 1:float, 2:float} $xpo
     * @return array{0:float, 1:float, 2:float}
     */
    public static function cotrans(array $xpo, float $eps): array
    {
        $x = [
            deg2rad($xpo[0]),
            deg2rad($xpo[1]),
            1.0,
        ];

        $x = self::polcart($x);
        $x = self::coortrf($x, deg2rad($eps));
        $x = self::cartpol($x);

        return [
            rad2deg($x[0]),
            rad2deg($x[1]),
            $xpo[2],
        ];
    }

    /**
     * Compatible with swe_cotrans_sp().
     *
     * Input and output are polar position + speed:
     * [longitude, latitude, radius, longitudeSpeed, latitudeSpeed, radiusSpeed].
     * Angles and angular speeds are in degrees.
     *
     * @param array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float} $xpo
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    public static function cotransSp(array $xpo, float $eps): array
    {
        $x = [
            deg2rad($xpo[0]),
            deg2rad($xpo[1]),
            1.0,
            deg2rad($xpo[3]),
            deg2rad($xpo[4]),
            $xpo[5],
        ];

        $x = self::polcartSp($x);

        $e = deg2rad($eps);
        $position = self::coortrf([$x[0], $x[1], $x[2]], $e);
        $speed = self::coortrf([$x[3], $x[4], $x[5]], $e);

        $x = self::cartpolSp([
            $position[0],
            $position[1],
            $position[2],
            $speed[0],
            $speed[1],
            $speed[2],
        ]);

        return [
            rad2deg($x[0]),
            rad2deg($x[1]),
            $xpo[2],
            rad2deg($x[3]),
            rad2deg($x[4]),
            $xpo[5],
        ];
    }

    /**
     * @param array{0:float, 1:float, 2:float} $xpo
     * @return array{0:float, 1:float, 2:float}
     */
    public static function coortrf(array $xpo, float $eps): array
    {
        $sineps = sin($eps);
        $coseps = cos($eps);

        return [
            $xpo[0],
            $xpo[1] * $coseps + $xpo[2] * $sineps,
            -$xpo[1] * $sineps + $xpo[2] * $coseps,
        ];
    }

    /**
     * @param array{0:float, 1:float, 2:float} $x
     * @return array{0:float, 1:float, 2:float}
     */
    public static function cartpol(array $x): array
    {
        if ($x[0] == 0.0 && $x[1] == 0.0 && $x[2] == 0.0) {
            return [0.0, 0.0, 0.0];
        }

        $rxy2 = $x[0] * $x[0] + $x[1] * $x[1];
        $radius = sqrt($rxy2 + $x[2] * $x[2]);
        $rxy = sqrt($rxy2);

        $lon = atan2($x[1], $x[0]);

        if ($lon < 0.0) {
            $lon += 2.0 * M_PI;
        }

        if ($rxy == 0.0) {
            $lat = $x[2] >= 0.0 ? M_PI / 2.0 : -M_PI / 2.0;
        } else {
            $lat = atan($x[2] / $rxy);
        }

        return [$lon, $lat, $radius];
    }

    /**
     * @param array{0:float, 1:float, 2:float} $l
     * @return array{0:float, 1:float, 2:float}
     */
    public static function polcart(array $l): array
    {
        $cosLat = cos($l[1]);

        return [
            $l[2] * $cosLat * cos($l[0]),
            $l[2] * $cosLat * sin($l[0]),
            $l[2] * sin($l[1]),
        ];
    }

    /**
     * @param array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float} $x
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    public static function cartpolSp(array $x): array
    {
        if ($x[0] == 0.0 && $x[1] == 0.0 && $x[2] == 0.0) {
            $motion = self::cartpol([$x[3], $x[4], $x[5]]);

            return [
                $motion[0],
                $motion[1],
                0.0,
                0.0,
                0.0,
                sqrt($x[3] * $x[3] + $x[4] * $x[4] + $x[5] * $x[5]),
            ];
        }

        if ($x[3] == 0.0 && $x[4] == 0.0 && $x[5] == 0.0) {
            $position = self::cartpol([$x[0], $x[1], $x[2]]);

            return [
                $position[0],
                $position[1],
                $position[2],
                0.0,
                0.0,
                0.0,
            ];
        }

        $rxy2 = $x[0] * $x[0] + $x[1] * $x[1];
        $radius = sqrt($rxy2 + $x[2] * $x[2]);
        $rxy = sqrt($rxy2);

        $lon = atan2($x[1], $x[0]);

        if ($lon < 0.0) {
            $lon += 2.0 * M_PI;
        }

        $lat = atan($x[2] / $rxy);

        $coslon = $x[0] / $rxy;
        $sinlon = $x[1] / $rxy;
        $coslat = $rxy / $radius;
        $sinlat = $x[2] / $radius;

        $xx3 = $x[3] * $coslon + $x[4] * $sinlon;
        $xx4 = -$x[3] * $sinlon + $x[4] * $coslon;
        $lonSpeed = $xx4 / $rxy;

        $xx4 = -$sinlat * $xx3 + $coslat * $x[5];
        $xx5 = $coslat * $xx3 + $sinlat * $x[5];

        return [
            $lon,
            $lat,
            $radius,
            $lonSpeed,
            $xx4 / $radius,
            $xx5,
        ];
    }

    /**
     * @param array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float} $l
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    public static function polcartSp(array $l): array
    {
        if ($l[3] == 0.0 && $l[4] == 0.0 && $l[5] == 0.0) {
            $position = self::polcart([$l[0], $l[1], $l[2]]);

            return [
                $position[0],
                $position[1],
                $position[2],
                0.0,
                0.0,
                0.0,
            ];
        }

        $coslon = cos($l[0]);
        $sinlon = sin($l[0]);
        $coslat = cos($l[1]);
        $sinlat = sin($l[1]);

        $x0 = $l[2] * $coslat * $coslon;
        $x1 = $l[2] * $coslat * $sinlon;
        $x2 = $l[2] * $sinlat;

        $rxyz = $l[2];
        $rxy = sqrt($x0 * $x0 + $x1 * $x1);

        $xx5 = $l[5];
        $xx4 = $l[4] * $rxyz;
        $x5 = $sinlat * $xx5 + $coslat * $xx4;

        $xx3 = $coslat * $xx5 - $sinlat * $xx4;
        $xx4 = $l[3] * $rxy;
        $x3 = $coslon * $xx3 - $sinlon * $xx4;
        $x4 = $sinlon * $xx3 + $coslon * $xx4;

        return [$x0, $x1, $x2, $x3, $x4, $x5];
    }
}