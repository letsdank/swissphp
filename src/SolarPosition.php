<?php

declare(strict_types=1);

namespace SwissEph;

final class SolarPosition
{
    /**
     * Geometric geocentric ecliptic Sun position from Moshier Earth position.
     *
     * Returns Swiss-style coordinates:
     * [longitude, latitude, radius, longitudeSpeed, latitudeSpeed, radiusSpeed].
     *
     * Angles are in degrees, speeds are per day, radius is in AU.
     *
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    public static function position(float $tjdEt): array
    {
        $earth = EarthPosition::heliocentric($tjdEt);

        return [
            Angle::degnorm($earth[0] + 180.0),
            -$earth[1],
            $earth[2],
            $earth[3],
            -$earth[4],
            $earth[5],
        ];
    }

    /**
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    public static function positionUt(float $tjdUt): array
    {
        return self::position($tjdUt + DeltaT::deltatEx($tjdUt, -1));
    }

    /**
     * Apparent geocentric ecliptic Sun position.
     *
     * Applies annual aberration and approximate ecliptic nutation to the
     * geometric Moshier Sun position.
     *
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    public static function apparent(float $tjdEt, bool $withNutation = true): array
    {
        $position = self::position($tjdEt);

        $cartesian = self::toCartesian($position);
        $earth = self::toCartesian(EarthPosition::heliocentric($tjdEt));

        $cartesian = Aberration::annual($cartesian, $earth, true);
        $position = self::fromCartesian($cartesian);

        if ($withNutation) {
            $position = EclipticNutation::apply($position, $tjdEt, true);
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

    public static function apparentResult(float $tjdEt, bool $withNutation = true): CalculationResult
    {
        return new CalculationResult(
            Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH,
            self::apparent($tjdEt, $withNutation)
        );
    }

    public static function apparentUtResult(float $tjdUt, bool $withNutation = true): CalculationResult
    {
        return new CalculationResult(
            Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH,
            self::apparentUt($tjdUt, $withNutation)
        );
    }

    public static function longitude(float $tjdEt): float
    {
        return self::position($tjdEt)[0];
    }

    public static function longitudeUt(float $tjdUt): float
    {
        return self::longitude($tjdUt + DeltaT::deltatEx($tjdUt, -1));
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