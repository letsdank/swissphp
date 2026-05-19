<?php

declare(strict_types=1);

namespace SwissEph;

final class EarthPosition
{
    private const SPEED_INTERVAL = 0.001;

    /**
     * Heliocentric ecliptic Earth position from Moshier tables with EMB -> Earth correction.
     *
     * Returns Swiss-style coordinates:
     * [longitude, latitude, radius, longitudeSpeed, latitudeSpeed, radiusSpeed].
     *
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    public static function heliocentric(float $tjdEt): array
    {
        $position = self::heliocentricWithoutSpeed($tjdEt);
        $previous = self::heliocentricWithoutSpeed($tjdEt - self::SPEED_INTERVAL);

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
    public static function heliocentricUt(float $tjdUt): array
    {
        return self::heliocentric($tjdUt + DeltaT::deltatEx($tjdUt, -1));
    }

    /**
     * @return array{0:float, 1:float, 2:float}
     */
    private static function heliocentricWithoutSpeed(float $tjdEt): array
    {
        $earthMoonBarycenter = MoshierPlanet::heliocentricWithoutSpeed(
            MoshierPlanetTables::EARTH,
            $tjdEt
        );

        $cartesian = Coordinates::polcart([
            deg2rad($earthMoonBarycenter[0]),
            deg2rad($earthMoonBarycenter[1]),
            $earthMoonBarycenter[2],
        ]);

        $epsJ2000 = deg2rad(SiderealTime::meanObliquity(Moshier::J2000));

        $cartesian = Coordinates::coortrf($cartesian, -$epsJ2000);
        $offset = MoshierMoon::earthMoonBarycenterOffset($tjdEt);

        for ($i = 0; $i <= 2; $i++) {
            $cartesian[$i] -= $offset[$i];
        }

        $cartesian = Coordinates::coortrf($cartesian, $epsJ2000);
        $polar = Coordinates::cartpol($cartesian);

        return [
            Angle::degnorm(rad2deg($polar[0])),
            rad2deg($polar[1]),
            $polar[2],
        ];
    }
}