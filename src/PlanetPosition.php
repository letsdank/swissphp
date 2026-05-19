<?php

declare(strict_types=1);

namespace SwissEph;

final class PlanetPosition
{
    /**
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    public static function heliocentric(int $ipl, float $tjdEt): array
    {
        return MoshierPlanet::heliocentric(self::moshierPlanet($ipl), $tjdEt);
    }

    /**
     * Geocentric ecliptic planet position from Moshier heliocentric planet minus Earth vector.
     *
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    public static function geocentric(int $ipl, float $tjdEt): array
    {
        $planet = self::toCartesian(self::heliocentric($ipl, $tjdEt));
        $earth = self::toCartesian(EarthPosition::heliocentric($tjdEt));

        return self::fromCartesian(self::subtractVectors($planet, $earth));
    }

    /**
     * Geocentric ecliptic planet position with one-step light-time correction.
     *
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    public static function geocentricLightTime(int $ipl, float $tjdEt): array
    {
        $earth = self::toCartesian(EarthPosition::heliocentric($tjdEt));
        $planet = self::toCartesian(self::heliocentric($ipl, $tjdEt));

        $geocentric = self::subtractVectors($planet, $earth);
        $distance = sqrt(
            $geocentric[0] * $geocentric[0]
            + $geocentric[1] * $geocentric[1]
            + $geocentric[2] * $geocentric[2]
        );

        $lightTime = $distance * Moshier::LIGHTTIME_AUNIT;

        $planet = self::toCartesian(self::heliocentric($ipl, $tjdEt - $lightTime));

        return self::fromCartesian(self::subtractVectors($planet, $earth));
    }

    /**
     * Apparent geocentric ecliptic planet position.
     *
     * Applies light-time, optional solar gravitational deflection,
     * optional annual aberration, and optional ecliptic nutation.
     *
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    public static function apparent(
        int   $ipl,
        float $tjdEt,
        bool  $withNutation = true,
        bool  $withDeflection = true,
        bool  $withAberration = true
    ): array
    {
        $position = self::geocentricLightTime($ipl, $tjdEt);

        $cartesian = self::toCartesian($position);
        $earth = self::toCartesian(EarthPosition::heliocentric($tjdEt));

        if ($withDeflection) {
            $cartesian = LightDeflection::solar($cartesian, $earth, true);
        }

        if ($withAberration) {
            $cartesian = Aberration::annual($cartesian, $earth, true);
        }

        $position = self::fromCartesian($cartesian);

        if ($withNutation) {
            $position = EclipticNutation::apply($position, $tjdEt, true);
        }

        return $position;
    }

    /**
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    public static function apparentUt(
        int   $ipl,
        float $tjdUt,
        bool  $withNutation = true,
        bool  $withDeflection = true,
        bool  $withAberration = true
    ): array
    {
        return self::apparent(
            $ipl,
            $tjdUt + DeltaT::deltatEx($tjdUt, -1),
            $withNutation,
            $withDeflection,
            $withAberration
        );
    }

    public static function apparentResult(
        int   $ipl,
        float $tjdEt,
        bool  $withNutation = true,
        bool  $withDeflection = true,
        bool  $withAberration = true
    ): CalculationResult
    {
        return new CalculationResult(
            Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH,
            self::apparent($ipl, $tjdEt, $withNutation, $withDeflection, $withAberration)
        );
    }

    public static function apparentUtResult(
        int   $ipl,
        float $tjdUt,
        bool  $withNutation = true,
        bool  $withDeflection = true,
        bool  $withAberration = true
    ): CalculationResult
    {
        return new CalculationResult(
            Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH,
            self::apparentUt($ipl, $tjdUt, $withNutation, $withDeflection, $withAberration)
        );
    }

    /**
     * @param array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float} $left
     * @param array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float} $right
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    private static function subtractVectors(array $left, array $right): array
    {
        return [
            $left[0] - $right[0],
            $left[1] - $right[1],
            $left[2] - $right[2],
            $left[3] - $right[3],
            $left[4] - $right[4],
            $left[5] - $right[5],
        ];
    }

    private static function moshierPlanet(int $ipl): int
    {
        return match ($ipl) {
            Catalog::SE_MERCURY => MoshierPlanetTables::MERCURY,
            Catalog::SE_VENUS => MoshierPlanetTables::VENUS,
            Catalog::SE_MARS => MoshierPlanetTables::MARS,
            Catalog::SE_JUPITER => MoshierPlanetTables::JUPITER,
            Catalog::SE_SATURN => MoshierPlanetTables::SATURN,
            Catalog::SE_URANUS => MoshierPlanetTables::URANUS,
            Catalog::SE_NEPTUNE => MoshierPlanetTables::NEPTUNE,
            Catalog::SE_PLUTO => MoshierPlanetTables::PLUTO,
            default => throw new \InvalidArgumentException(sprintf('Unsupported Moshier planet %d.', $ipl)),
        };
    }

    public static function isSupported(int $ipl): bool
    {
        return match ($ipl) {
            Catalog::SE_MERCURY,
            Catalog::SE_VENUS,
            Catalog::SE_MARS,
            Catalog::SE_JUPITER,
            Catalog::SE_SATURN,
            Catalog::SE_URANUS,
            Catalog::SE_NEPTUNE,
            Catalog::SE_PLUTO => true,
            default => false,
        };
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