<?php

declare(strict_types=1);

namespace SwissEph;

final class LightDeflection
{
    private const AUNIT = 1.49597870700e11;
    private const CLIGHT = 2.99792458e8;
    private const HELGRAVCONST = 1.32712440017989e20;
    private const SUN_RADIUS = 959.63 / 3600.0 * M_PI / 180.0;
    private const DEFL_SPEED_INTERVAL = 0.0000005;

    /**
     * Solar gravitational light deflection for rectangular ecliptic coordinates.
     *
     * $position is geocentric planet vector after light-time correction.
     * $earth is heliocentric Earth vector.
     *
     * @param array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float} $position
     * @param array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float} $earth
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    public static function solar(array $position, array $earth, bool $withSpeed = true): array
    {
        $deflected = self::solarPosition($position, $earth);

        if (!$withSpeed) {
            return $deflected;
        }

        $dt = -self::DEFL_SPEED_INTERVAL;
        $previousPosition = $position;
        $previousEarth = $earth;

        for ($i = 0; $i <= 2; $i++) {
            $previousPosition[$i] = $position[$i] - $dt * $position[$i + 3];
            $previousEarth[$i] = $earth[$i] - $dt * $earth[$i + 3];
        }

        $previousDeflected = self::solarPosition($previousPosition, $previousEarth);

        for ($i = 0; $i <= 2; $i++) {
            $currentCorrection = $deflected[$i] - $position[$i];
            $previousCorrection = $previousDeflected[$i] - $previousPosition[$i];

            $deflected[$i + 3] += ($currentCorrection - $previousCorrection) / $dt;
        }

        return $deflected;
    }

    /**
     * @param array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float} $position
     * @param array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float} $earth
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    private static function solarPosition(array $position, array $earth): array
    {
        $u = [$position[0], $position[1], $position[2]];
        $e = [$earth[0], $earth[1], $earth[2]];
        $q = [
            $position[0] + $earth[0],
            $position[1] + $earth[1],
            $position[2] + $earth[2],
        ];

        $ru = self::length($u);
        $rq = self::length($q);
        $re = self::length($e);

        for ($i = 0; $i <= 2; $i++) {
            $u[$i] /= $ru;
            $q[$i] /= $rq;
            $e[$i] /= $re;
        }

        $uq = self::dot($u, $q);
        $ue = self::dot($u, $e);
        $qe = self::dot($q, $e);

        $sina = sqrt(max(0.0, 1.0 - $ue * $ue));
        $sinSunRadius = self::SUN_RADIUS / $re;
        $effectiveMass = $sina < $sinSunRadius
            ? self::effectiveMass($sina / $sinSunRadius)
            : 1.0;

        $g1 = 2.0 * self::HELGRAVCONST * $effectiveMass / self::CLIGHT / self::CLIGHT / self::AUNIT / $re;
        $g2 = 1.0 + $qe;

        for ($i = 0; $i <= 2; $i++) {
            $position[$i] = $ru * ($u[$i] + $g1 / $g2 * ($uq * $e[$i] - $ue * $q[$i]));
        }

        return $position;
    }

    /**
     * @param array{0:float, 1:float, 2:float} $vector
     */
    private static function length(array $vector): float
    {
        return sqrt($vector[0] * $vector[0] + $vector[1] * $vector[1] + $vector[2] * $vector[2]);
    }

    /**
     * @param array{0:float, 1:float, 2:float} $left
     * @param array{0:float, 1:float, 2:float} $right
     */
    private static function dot(array $left, array $right): float
    {
        return $left[0] * $right[0] + $left[1] * $right[1] + $left[2] * $right[2];
    }

    private static function effectiveMass(float $r): float
    {
        if ($r <= 0.0) {
            return 0.0;
        }

        if ($r >= 1.0) {
            return 1.0;
        }

        $table = [
            [1.000, 1.000000], [0.990, 0.999979], [0.980, 0.999940], [0.970, 0.999881],
            [0.960, 0.999811], [0.950, 0.999724], [0.940, 0.999622], [0.930, 0.999497],
            [0.920, 0.999354], [0.910, 0.999192], [0.900, 0.999000], [0.890, 0.998786],
            [0.880, 0.998535], [0.870, 0.998242], [0.860, 0.997919], [0.850, 0.997571],
            [0.840, 0.997198], [0.830, 0.996792], [0.820, 0.996316], [0.810, 0.995791],
            [0.800, 0.995226], [0.790, 0.994625], [0.780, 0.993991], [0.770, 0.993326],
            [0.760, 0.992598], [0.750, 0.991770], [0.740, 0.990873], [0.730, 0.989919],
            [0.720, 0.988912], [0.710, 0.987856], [0.700, 0.986755], [0.690, 0.985610],
            [0.680, 0.984398], [0.670, 0.982986], [0.660, 0.981437], [0.650, 0.979779],
            [0.640, 0.978024], [0.630, 0.976182], [0.620, 0.974256], [0.610, 0.972253],
            [0.600, 0.970174], [0.590, 0.968024], [0.580, 0.965594], [0.570, 0.962797],
            [0.560, 0.959758], [0.550, 0.956515], [0.540, 0.953088], [0.530, 0.949495],
            [0.520, 0.945741], [0.510, 0.941838], [0.500, 0.937790], [0.490, 0.933563],
            [0.480, 0.928668], [0.470, 0.923288], [0.460, 0.917527], [0.450, 0.911432],
            [0.440, 0.905035], [0.430, 0.898353], [0.420, 0.891022], [0.410, 0.882940],
            [0.400, 0.874312], [0.390, 0.865206], [0.380, 0.855423], [0.370, 0.844619],
            [0.360, 0.833074], [0.350, 0.820876], [0.340, 0.808031], [0.330, 0.793962],
            [0.320, 0.778931], [0.310, 0.763021], [0.300, 0.745815], [0.290, 0.727557],
            [0.280, 0.708234], [0.270, 0.687583], [0.260, 0.665741], [0.250, 0.642597],
            [0.240, 0.618252], [0.230, 0.592586], [0.220, 0.565747], [0.210, 0.537697],
            [0.200, 0.508554], [0.190, 0.478420], [0.180, 0.447322], [0.170, 0.415454],
            [0.160, 0.382892], [0.150, 0.349955], [0.140, 0.316691], [0.130, 0.283565],
            [0.120, 0.250431], [0.110, 0.218327], [0.100, 0.186794], [0.090, 0.156287],
            [0.080, 0.128421], [0.070, 0.102237], [0.060, 0.077393], [0.050, 0.054833],
            [0.040, 0.036361], [0.030, 0.020953], [0.020, 0.009645], [0.010, 0.002767],
            [0.000, 0.000000],
        ];

        for ($i = 0; $table[$i][0] > $r; $i++) {
        }

        $previous = $table[$i - 1];
        $current = $table[$i];

        $factor = ($r - $previous[0]) / ($current[0] - $previous[0]);

        return $previous[1] + $factor * ($current[1] - $previous[1]);
    }
}