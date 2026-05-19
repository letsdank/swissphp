<?php

declare(strict_types=1);

namespace SwissEph;

final class MoshierMoon
{
    private const J1900 = 2415020.0;
    private const EARTH_MOON_MASS_RATIO = 81.30056907419062;
    private const SPEED_INTERVAL = 0.001;
    private const START = -3100015.5;
    private const END = 8000016.5;

    /**
     * Short Moon series used by Swiss Ephemeris Moshier code for EMB -> Earth correction.
     *
     * Returns geocentric Moon vector divided by (Earth-Moon mass ratio + 1),
     * in rectangular equatorial J2000 coordinates, AU.
     *
     * @return array{0:float, 1:float, 2:float}
     */
    public static function earthMoonBarycenterOffset(float $tjdEt): array
    {
        $moon = self::geocentricEquatorialJ2000($tjdEt);

        return [
            $moon[0] / (self::EARTH_MOON_MASS_RATIO + 1.0),
            $moon[1] / (self::EARTH_MOON_MASS_RATIO + 1.0),
            $moon[2] / (self::EARTH_MOON_MASS_RATIO + 1.0),
        ];
    }

    public static function isInRange(float $tjdEt): bool
    {
        return $tjdEt >= self::START && $tjdEt <= self::END;
    }

    public static function rangeError(float $tjdEt): string
    {
        return sprintf('jd %.6f outside Moshier Moon range %.2f .. %.2f', $tjdEt, self::START, self::END);
    }

    /**
     * Geocentric ecliptic Moon position from the short Moshier Moon series.
     *
     * Returns Swiss-style coordinates:
     * [longitude, latitude, distance, longitudeSpeed, latitudeSpeed, distanceSpeed].
     *
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    public static function geocentric(float $tjdEt): array
    {
        $position = self::geocentricWithoutSpeed($tjdEt);
        $previous = self::geocentricWithoutSpeed($tjdEt - self::SPEED_INTERVAL);

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
    public static function geocentricUt(float $tjdUt): array
    {
        return self::geocentric($tjdUt + DeltaT::deltatEx($tjdUt, -1));
    }

    /**
     * Apparent geocentric ecliptic Moon position.
     *
     * Applies optional solar gravitational deflection, annual aberration,
     * and ecliptic nutation to the short Moshier Moon series.
     *
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    public static function apparent(
        float $tjdEt,
        bool  $withNutation = true,
        bool  $withDeflection = true,
        bool  $withAberration = true
    ): array
    {
        $position = self::geocentric($tjdEt);

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
        float $tjdUt,
        bool  $withNutation = true,
        bool  $withDeflection = true,
        bool  $withAberration = true
    ): array
    {
        return self::apparent(
            $tjdUt + DeltaT::deltatEx($tjdUt, -1),
            $withNutation,
            $withDeflection,
            $withAberration
        );
    }

    public static function apparentResult(
        float $tjdEt,
        bool  $withNutation = true,
        bool  $withDeflection = true,
        bool  $withAberration = true
    ): CalculationResult
    {
        return new CalculationResult(
            Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH,
            self::apparent($tjdEt, $withNutation, $withDeflection, $withAberration)
        );
    }

    public static function apparentUtResult(
        float $tjdUt,
        bool  $withNutation = true,
        bool  $withDeflection = true,
        bool  $withAberration = true
    ): CalculationResult
    {
        return new CalculationResult(
            Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH,
            self::apparentUt($tjdUt, $withNutation, $withDeflection, $withAberration)
        );
    }

    /**
     * Geocentric Moon vector in rectangular equatorial J2000 coordinates, AU.
     *
     * @return array{0:float, 1:float, 2:float}
     */
    public static function geocentricEquatorialJ2000(float $tjdEt): array
    {
        return self::moonVectorEquatorialJ2000($tjdEt);
    }

    /**
     * @return array{0:float, 1:float, 2:float}
     */
    private static function geocentricWithoutSpeed(float $tjdEt): array
    {
        $vector = self::moonVectorEquatorialJ2000($tjdEt);
        $vector = Precession::precess($vector, $tjdEt, Precession::DIRECTION_FROM_J2000);

        $epsJ2000 = deg2rad(SiderealTime::meanObliquity(Moshier::J2000));
        $vector = Coordinates::coortrf($vector, $epsJ2000);

        $polar = Coordinates::cartpol($vector);

        return [
            Angle::degnorm(rad2deg($polar[0])),
            rad2deg($polar[1]),
            $polar[2],
        ];
    }

    /**
     * @return array{0:float, 1:float, 2:float}
     */
    private static function moonVectorEquatorialJ2000(float $tjdEt): array
    {
        $t = ($tjdEt - self::J1900) / 36525.0;

        $meanAnomalyMoon = deg2rad(Angle::degnorm(
            ((1.44e-5 * $t + 0.009192) * $t + 477198.8491) * $t + 296.104608
        ));
        $sinMeanAnomalyMoon = sin($meanAnomalyMoon);
        $cosMeanAnomalyMoon = cos($meanAnomalyMoon);
        $sin2MeanAnomalyMoon = 2.0 * $sinMeanAnomalyMoon * $cosMeanAnomalyMoon;
        $cos2MeanAnomanyMoon = $cosMeanAnomalyMoon * $cosMeanAnomalyMoon
            - $sinMeanAnomalyMoon * $sinMeanAnomalyMoon;

        $meanElongationMoon = deg2rad(2.0 * Angle::degnorm(
                ((1.9e-6 * $t - 0.001436) * $t + 445267.1142) * $t + 350.737486
            ));
        $sin2MeanElongationMoon = sin($meanElongationMoon);
        $cos2MeanElongationMoon = cos($meanElongationMoon);

        $meanDistanceNode = deg2rad(Angle::degnorm(
            ((-3.0e-7 * $t - 0.003211) * $t + 483202.0251) * $t + 11.250889
        ));
        $sinMeanDistanceNode = sin($meanDistanceNode);
        $cosMeanDistanceNode = cos($meanDistanceNode);
        $sin2MeanDistanceNode = 2.0 * $sinMeanDistanceNode * $cosMeanDistanceNode;

        $sin2dMinusMp = $sin2MeanElongationMoon * $cosMeanAnomalyMoon
            - $cos2MeanElongationMoon * $sinMeanAnomalyMoon;
        $cos2dMinusMp = $cos2MeanElongationMoon * $cosMeanAnomalyMoon
            + $sin2MeanElongationMoon * $sinMeanAnomalyMoon;

        $meanLongitudeMoon = ((1.9e-6 * $t - 0.001133) * $t + 481267.8831) * $t + 270.434164;
        $meanAnomalySun = Angle::degnorm(
            ((-3.3e-6 * $t - 1.50e-4) * $t + 35999.0498) * $t + 358.475833
        );

        $longitude = $meanLongitudeMoon
            + 6.288750 * $sinMeanAnomalyMoon
            + 1.274018 * $sin2dMinusMp
            + 0.658309 * $sin2MeanElongationMoon
            + 0.213616 * $sin2MeanAnomalyMoon
            - 0.185596 * sin(deg2rad($meanAnomalySun))
            - 0.114336 * $sin2MeanDistanceNode;

        $sinMpCosF = $sinMeanAnomalyMoon * $cosMeanDistanceNode;
        $cosMpSinF = $cosMeanAnomalyMoon * $sinMeanDistanceNode;

        $latitude = 5.128189 * $sinMeanDistanceNode
            + 0.280606 * ($sinMpCosF + $cosMpSinF)
            + 0.277693 * ($sinMpCosF - $cosMpSinF)
            + 0.173238 * (
                $sin2MeanElongationMoon * $cosMeanDistanceNode
                - $cos2MeanElongationMoon * $sinMeanDistanceNode
            );

        $parallax = 0.950724
            + 0.051818 * $cosMeanAnomalyMoon
            + 0.009531 * $cos2dMinusMp
            + 0.007843 * $cos2MeanElongationMoon
            + 0.002824 * $cos2MeanAnomanyMoon;

        $distance = 4.263523e-5 / sin(deg2rad($parallax));

        $vector = Coordinates::polcart([
            deg2rad(Angle::degnorm($longitude)),
            deg2rad($latitude),
            $distance,
        ]);

        $epsJ2000 = deg2rad(SiderealTime::meanObliquity(Moshier::J2000));
        $vector = Coordinates::coortrf($vector, -$epsJ2000);

        return Precession::precess($vector, $tjdEt, Precession::DIRECTION_TO_J2000);
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