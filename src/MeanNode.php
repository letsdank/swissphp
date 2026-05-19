<?php

declare(strict_types=1);

namespace SwissEph;

final class MeanNode
{
    private const SPEED_INTERVAL = 0.001;
    private const MOON_MEAN_DISTANCE_AU = 384400000.0 / 149597870700.0;

    /** @var array<int, float> */
    private const Z = [
        -1.312045233711e+01,
        -1.138215912580e-03,
        -9.646018347184e-06,
        -5.663161722088e+00,
        5.722859298199e-03,
        -8.466472828815e-05,
    ];

    /**
     * Mean lunar node, ecliptic of date.
     *
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    public static function geocentric(float $tjdEt): array
    {
        $position = self::geocentricWithoutSpeed($tjdEt);
        $previous = self::geocentricWithoutSpeed($tjdEt - self::SPEED_INTERVAL);

        return [
            $position[0],
            0.0,
            $position[2],
            Angle::difdeg2n($position[0], $previous[0]) / self::SPEED_INTERVAL,
            0.0,
            0.0,
        ];
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
    private static function geocentricWithoutSpeed(float $tjdEt): array
    {
        [$meanLongitude, $meanDistanceFromNode] = self::meanElements($tjdEt);

        return [
            Angle::degnorm(($meanLongitude - $meanDistanceFromNode) / 3600.0),
            0.0,
            self::MOON_MEAN_DISTANCE_AU,
        ];
    }

    /**
     * @return array{0:float, 1:float}
     */
    private static function meanElements(float $tjdEt): array
    {
        $t = ($tjdEt - Moshier::J2000) / 36525.0;
        $t2 = $t * $t;
        $fracT = fmod($t, 1.0);

        $meanDistanceFromNode = Moshier::mods3600(
            1739232000.0 * $fracT
            + 295263.0983 * $t
            - 2.079419901760e-01 * $t
            + 335779.55755
        );

        $meanLongitude = Moshier::mods3600(
            1731456000.0 * $fracT
            + 1108372.83264 * $t
            - 6.784914260953e-01 * $t
            + 785939.95571
        );

        $meanDistanceFromNode += ((self::Z[2] * $t + self::Z[1]) * $t + self::Z[0]) * $t2;
        $meanLongitude += ((self::Z[5] * $t + self::Z[4]) * $t + self::Z[3]) * $t2;

        return [$meanLongitude, $meanDistanceFromNode];
    }
}