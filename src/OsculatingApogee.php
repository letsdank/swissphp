<?php

declare(strict_types=1);

namespace SwissEph;

final class OsculatingApogee
{
    private const SPEED_INTERVAL = 0.1;
    private const GEOGCONST = 3.98600448e14;
    private const AUNIT = 149597870700.0;
    private const EARTH_MOON_MASS_RATIO = 81.30056907419062;

    /**
     * Osculating lunar apogee from the current MoshierMoon position/speed.
     *
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    public static function geocentric(float $tjdEt): array
    {
        $position = self::apogeeCartesian($tjdEt);
        $previous = self::apogeeCartesian($tjdEt - self::SPEED_INTERVAL);
        $next = self::apogeeCartesian($tjdEt + self::SPEED_INTERVAL);

        return self::fromCartesian([
            $position[0],
            $position[1],
            $position[2],
            ($next[0] - $previous[0]) / (2.0 * self::SPEED_INTERVAL),
            ($next[1] - $previous[1]) / (2.0 * self::SPEED_INTERVAL),
            ($next[2] - $previous[2]) / (2.0 * self::SPEED_INTERVAL),
        ]);
    }

    /**
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    public static function geocentricUt(float $tjdUt): array
    {
        return self::geocentric($tjdUt + DeltaT::deltatEx($tjdUt, -1));
    }

    /**
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    public static function apparent(float $tjdEt, bool $withNutation = true): array
    {
        $position = self::geocentric($tjdEt);

        if ($withNutation) {
            return EclipticNutation::apply($position, $tjdEt, true);
        }

        return $position;
    }

    /**
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    public static function apparentUt(float $tjdUt, bool $withNutation = true): array
    {
        return self::apparent($tjdUt + DeltaT::deltatEx($tjdUt, -1), $withNutation);
    }

    /**
     * @return array{0:float, 1:float, 2:float}
     */
    private static function apogeeCartesian(float $tjdEt): array
    {
        $moon = self::toCartesian(MoshierMoon::geocentric($tjdEt));

        if (abs($moon[5]) < 1e-15) {
            $moon[5] = 1e-15;
        }

        $factor = $moon[2] / $moon[5];
        $sign = $moon[5] / abs($moon[5]);

        $node = [
            ($moon[0] - $factor * $moon[3]) * $sign,
            ($moon[1] - $factor * $moon[4]) * $sign,
            ($moon[2] - $factor * $moon[5]) * $sign,
        ];

        $rxy = sqrt($node[0] * $node[0] + $node[1] * $node[1]);

        if ($rxy == 0.0) {
            return $node;
        }

        $cosNode = $node[0] / $rxy;
        $sinNode = $node[1] / $rxy;

        $position = [$moon[0], $moon[1], $moon[2]];
        $speed = [$moon[3], $moon[4], $moon[5]];
        $normal = self::crossProduct($position, $speed);

        $normalRxy2 = $normal[0] * $normal[0] + $normal[1] * $normal[1];
        $normalLength2 = $normalRxy2 + $normal[2] * $normal[2];
        $normalLength = sqrt($normalLength2);

        if ($normalLength == 0.0) {
            return $node;
        }

        $sinInclination = sqrt($normalRxy2) / $normalLength;

        if ($sinInclination == 0.0) {
            return $node;
        }

        $cosU = $moon[0] * $cosNode + $moon[1] * $sinNode;
        $sinU = $moon[2] / $sinInclination;
        $argumentOfLatitude = atan2($sinU, $cosU);

        $radius = sqrt(self::squareSum($position));
        $speed2 = self::squareSum($speed);
        $gm = self::earthMoonGravitationalConstant();

        $semiAxis = 1.0 / (2.0 / $radius - $speed2 / $gm);
        $parameter = $normalLength2 / $gm;
        $eccentricity = sqrt(max(0.0, 1.0 - $parameter / $semiAxis));

        if ($eccentricity == 0.0) {
            return $node;
        }

        $cosE = (1.0 - $radius / $semiAxis) / $eccentricity;
        $sinE = self::dotProduct($position, $speed)
            / ($eccentricity * sqrt($semiAxis * $gm));

        $trueAnomaly = 2.0 * atan(
                sqrt((1.0 + $eccentricity) / (1.0 - $eccentricity))
                * $sinE
                / (1.0 + $cosE)
            );

        $apogee = [
            self::mod2pi($argumentOfLatitude - $trueAnomaly + M_PI),
            0.0,
            $semiAxis * (1.0 + $eccentricity),
        ];

        $cartesian = Coordinates::polcart($apogee);
        $cartesian = Coordinates::coortrf($cartesian, -asin($sinInclination));
        $polar = Coordinates::cartpol($cartesian);

        return Coordinates::polcart([
            self::mod2pi($polar[0] + atan2($sinNode, $cosNode)),
            $polar[1],
            $polar[2],
        ]);
    }

    private static function earthMoonGravitationalConstant(): float
    {
        return self::GEOGCONST
            * (1.0 + 1.0 / self::EARTH_MOON_MASS_RATIO)
            / self::AUNIT
            / self::AUNIT
            / self::AUNIT
            * 86400.0
            * 86400.0;
    }

    /**
     * @param array{0:float, 1:float, 2:float} $left
     * @param array{0:float, 1:float, 2:float} $right
     * @return array{0:float, 1:float, 2:float}
     */
    private static function crossProduct(array $left, array $right): array
    {
        return [
            $left[1] * $right[2] - $left[2] * $right[1],
            $left[2] * $right[0] - $left[0] * $right[2],
            $left[0] * $right[1] - $left[1] * $right[0],
        ];
    }

    /**
     * @param array{0:float, 1:float, 2:float} $left
     * @param array{0:float, 1:float, 2:float} $right
     */
    private static function dotProduct(array $left, array $right): float
    {
        return $left[0] * $right[0] + $left[1] * $right[1] + $left[2] * $right[2];
    }

    /**
     * @param array{0:float, 1:float, 2:float} $vector
     */
    private static function squareSum(array $vector): float
    {
        return $vector[0] * $vector[0] + $vector[1] * $vector[1] + $vector[2] * $vector[2];
    }

    private static function mod2pi(float $value): float
    {
        $value = fmod($value, 2.0 * M_PI);

        if ($value < 0.0) {
            $value += 2.0 * M_PI;
        }

        return $value;
    }

    /**
     * @param array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float} $position
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    private static function toCartesian(array $position): array
    {
        $position[0] = deg2rad($position[0]);
        $position[1] = deg2rad($position[1]);
        $position[3] = deg2rad($position[3]);
        $position[4] = deg2rad($position[4]);

        return Coordinates::polcartSp($position);
    }

    /**
     * @param array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float} $position
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    private static function fromCartesian(array $position): array
    {
        $polar = Coordinates::cartpolSp($position);

        return [
            rad2deg($polar[0]),
            rad2deg($polar[1]),
            $polar[2],
            rad2deg($polar[3]),
            rad2deg($polar[4]),
            $polar[5],
        ];
    }
}