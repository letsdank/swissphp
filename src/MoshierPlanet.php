<?php

declare(strict_types=1);

namespace SwissEph;

final class MoshierPlanet
{
    private const SPEED_INTERVAL = 0.001;

    /**
     * Heliocentric ecliptic polar position from Moshier tables.
     *
     * Returns:
     * [longitude degrees, latitude degrees, radius AU, longitudeSpeed deg/day, latitudeSpeed dev/day, radiusSpeed AU/day].
     *
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    public static function heliocentric(int $planet, float $tjdEt): array
    {
        $position = self::heliocentricWithoutSpeed($planet, $tjdEt);
        $previous = self::heliocentricWithoutSpeed($planet, $tjdEt - self::SPEED_INTERVAL);

        return [
            $position[0],
            $position[1],
            $position[2],
            Angle::difdeg2n($position[0], $previous[0]) / self::SPEED_INTERVAL,
            ($position[1] - $previous[1]) / self::SPEED_INTERVAL,
            ($position[2] - $previous[2]) / self::SPEED_INTERVAL,
        ];
    }

    /**
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    public static function heliocentricUt(int $planet, float $tjdUt): array
    {
        return self::heliocentric($planet, $tjdUt + DeltaT::deltatEx($tjdUt, -1));
    }

    /**
     * @return array{0:float, 1:float, 2:float}
     */
    public static function heliocentricWithoutSpeed(int $planet, float $tjdEt): array
    {
        Moshier::assertPlanetRange($tjdEt);

        $position = MoshierSeries::evaluatePolar(
            $tjdEt,
            MoshierPlanetTables::planet($planet)
        );

        return [
            Angle::degnorm(rad2deg($position[0])),
            rad2deg($position[1]),
            $position[2],
        ];
    }
}