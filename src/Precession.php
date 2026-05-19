<?php

declare(strict_types=1);

namespace SwissEph;

use InvalidArgumentException;

final class Precession
{
    public const MODEL_IAU_1976 = 'iau1976';
    public const MODEL_NEWCOMB = 'newcomb';

    public const DIRECTION_TO_J2000 = 1;
    public const DIRECTION_FROM_J2000 = -1;

    private const J2000 = 2451545.0;
    private const B1850 = 2396758.2035810;

    /**
     * Swiss Ephemeris compatible subset of swi_precess().
     *
     * @param array{0:float, 1:float, 2:float} $r
     * @return array{0:float, 1:float, 2:float}
     */
    public static function precess(
        array  $r,
        float  $julianDay,
        int    $direction,
        string $model = self::MODEL_IAU_1976
    ): array
    {
        if ($julianDay === self::J2000) {
            return $r;
        }

        [$zeta, $z, $theta] = self::precessionAngles($julianDay, $model);

        $sinTheta = sin($theta);
        $cosTheta = cos($theta);
        $sinZeta = sin($zeta);
        $cosZeta = cos($zeta);
        $sinZ = sin($z);
        $cosZ = cos($z);

        $a = $cosZeta * $cosTheta;
        $b = $sinZeta * $cosTheta;

        if ($direction < 0) {
            return [
                ($a * $cosZ - $sinZeta * $sinZ) * $r[0]
                - ($b * $cosZ + $cosZeta * $sinZ) * $r[1]
                - $sinTheta * $cosZ * $r[2],
                ($a * $sinZ + $sinZeta * $cosZ) * $r[0]
                - ($b * $sinZ - $cosZeta * $cosZ) * $r[1]
                - $sinTheta * $sinZ * $r[2],
                $cosZeta * $sinTheta * $r[0]
                - $sinZeta * $sinTheta * $r[1]
                + $cosTheta * $r[2],
            ];
        }

        return [
            ($a * $cosZ - $sinZeta * $sinZ) * $r[0]
            + ($a * $sinZ + $sinZeta * $cosZ) * $r[1]
            + $cosZeta * $sinTheta * $r[2],
            -($b * $cosZ + $cosZeta * $sinZ) * $r[0]
            - ($b * $sinZ - $cosZeta * $cosZ) * $r[1]
            - $sinZeta * $sinTheta * $r[2],
            -$sinTheta * $cosZ * $r[0]
            - $sinTheta * $sinZ * $r[1]
            + $cosTheta * $r[2],
        ];
    }

    /**
     * @return array{0:float, 1:float, 2:float}
     */
    private static function precessionAngles(float $julianDay, string $model): array
    {
        return match ($model) {
            self::MODEL_IAU_1976 => self::iau1976Angles($julianDay),
            self::MODEL_NEWCOMB => self::newcombAngles($julianDay),
            default => throw new InvalidArgumentException('Unsupported precession model.'),
        };
    }

    /**
     * @return array{0:float, 1:float, 2:float}
     */
    private static function iau1976Angles(float $julianDay): array
    {
        $t = ($julianDay - self::J2000) / 36525.0;

        return [
            deg2rad((((0.017998 * $t + 0.30188) * $t + 2306.2181) * $t) / 3600.0),
            deg2rad((((0.018203 * $t + 1.09468) * $t + 2306.2181) * $t) / 3600.0),
            deg2rad((((-0.041833 * $t - 0.42665) * $t + 2004.3109) * $t) / 3600.0),
        ];
    }

    /**
     * @return array{0:float, 1:float, 2:float}
     */
    private static function newcombAngles(float $julianDay): array
    {
        $millennia = 365242.198782;
        $t1 = (self::J2000 - self::B1850) / $millennia;
        $t2 = ($julianDay - self::B1850) / $millennia;
        $t = $t2 - $t1;
        $tSquared = $t * $t;
        $tCubed = $tSquared * $t;

        $zetaBase = 23035.5548 + 139.720 * $t1 + 0.069 * $t1 * $t1;

        $zeta = $zetaBase * $t
            + (30.242 - 0.269 * $t1) * $tSquared
            + 17.996 * $tCubed;

        $z = $zetaBase * $t
            + (109.478 - 0.387 * $t1) * $tSquared
            + 18.324 * $tCubed;

        $theta = (20051.125 - 85.294 * $t1 - 0.365 * $t1 * $t1) * $t
            + (-42.647 - 0.365 * $t1) * $tSquared
            - 41.802 * $tCubed;

        return [
            deg2rad($zeta / 3600.0),
            deg2rad($z / 3600.0),
            deg2rad($theta / 3600.0),
        ];
    }
}