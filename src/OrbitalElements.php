<?php

declare(strict_types=1);

namespace SwissEph;

final class OrbitalElements
{
    private const AUNIT = 149597870700.0;
    private const HELGRAVCONST = 1.32712440017987e20;
    private const GEOGCONST = 3.98600448e14;
    private const EARTH_MOON_MASS_RATIO = 81.30056907419062;
    private const TROPICAL_YEAR_DAYS = 365.242198781;

    /** @var array<int, float> */
    private const PLANET_MASS_RATIO = [
        Catalog::SE_MERCURY => 6023600.0,
        Catalog::SE_VENUS => 408523.719,
        Catalog::SE_EARTH => 328900.5,
        Catalog::SE_MARS => 3098703.59,
        Catalog::SE_JUPITER => 1047.348644,
        Catalog::SE_SATURN => 3497.9018,
        Catalog::SE_URANUS => 22902.98,
        Catalog::SE_NEPTUNE => 19412.26,
        Catalog::SE_PLUTO => 136566000.0,
    ];

    /** @var array<int, float> */
    private const AA_INNER_MASS_SUM = [
        Catalog::SE_MERCURY => 0.0,
        Catalog::SE_VENUS => 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_MERCURY],
        Catalog::SE_EARTH => 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_MERCURY]
            + 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_VENUS],
        Catalog::SE_MARS => 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_MERCURY]
            + 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_VENUS]
            + 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_EARTH],
        Catalog::SE_JUPITER => 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_MERCURY]
            + 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_VENUS]
            + 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_EARTH]
            + 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_MARS],
        Catalog::SE_SATURN => 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_MERCURY]
            + 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_VENUS]
            + 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_EARTH]
            + 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_MARS]
            + 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_JUPITER],
        Catalog::SE_URANUS => 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_MERCURY]
            + 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_VENUS]
            + 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_EARTH]
            + 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_MARS]
            + 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_JUPITER]
            + 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_SATURN],
        Catalog::SE_NEPTUNE => 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_MERCURY]
            + 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_VENUS]
            + 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_EARTH]
            + 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_MARS]
            + 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_JUPITER]
            + 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_SATURN]
            + 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_URANUS],
        Catalog::SE_PLUTO => 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_MERCURY]
            + 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_VENUS]
            + 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_EARTH]
            + 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_MARS]
            + 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_JUPITER]
            + 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_SATURN]
            + 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_URANUS]
            + 1.0 / self::PLANET_MASS_RATIO[Catalog::SE_NEPTUNE],
    ];

    /**
     * Swiss Ephemeris compatible subset of swe_get_orbital_elements().
     *
     * @return array{rc:int, dret:array<int, float>, error:string}
     */
    public static function get(
        float $tjdEt,
        int   $body,
        int   $flags = Catalog::SEFLG_DEFAULTEPH
    ): array
    {
        $body = self::normalizeBody($body);

        if (!self::isSupported($body)) {
            return [
                'rc' => SwissDate::ERR,
                'dret' => self::zeroElements(),
                'error' => sprintf('orbital elements for planet %d are not implemented', $body),
            ];
        }

        try {
            $state = self::stateVector($body, $tjdEt);
        } catch (\InvalidArgumentException $exception) {
            return [
                'rc' => SwissDate::ERR,
                'dret' => self::zeroElements(),
                'error' => $exception->getMessage(),
            ];
        }

        return [
            'rc' => Catalog::normalizeEphemerisFlags($flags),
            'dret' => self::elementsFromState($tjdEt, $body, $state, $flags),
            'error' => '',
        ];
    }

    /**
     * @return array{rc:int, dret:array<int, float>, error:string}
     */
    public static function getUt(
        float $tjdUt,
        int   $body,
        int   $flags = Catalog::SEFLG_DEFAULTEPH
    ): array
    {
        return self::get(
            $tjdUt + DeltaT::deltatEx($tjdUt, $flags),
            $body,
            $flags
        );
    }

    public static function getResult(
        float $tjdEt,
        int   $body,
        int   $flags = Catalog::SEFLG_DEFAULTEPH
    ): OrbitalElementsResult
    {
        return OrbitalElementsResult::fromArray(self::get($tjdEt, $body, $flags));
    }

    public static function getUtResult(
        float $tjdUt,
        int   $body,
        int   $flags = Catalog::SEFLG_DEFAULTEPH
    ): OrbitalElementsResult
    {
        return OrbitalElementsResult::fromArray(self::getUt($tjdUt, $body, $flags));
    }

    /**
     * Swiss Ephemeris compatible subset of swe_orbit_max_min_true_distance().
     *
     * @return array{rc:int, max:float, min:float, true:float, error:string}
     */
    public static function maxMinTrueDistance(
        float $tjdEt,
        int   $body,
        int   $flags = Catalog::SEFLG_DEFAULTEPH
    ): array
    {
        $elements = self::get($tjdEt, $body, $flags);

        if ($elements['rc'] === SwissDate::ERR) {
            return [
                'rc' => SwissDate::ERR,
                'max' => 0.0,
                'min' => 0.0,
                'true' => 0.0,
                'error' => $elements['error'],
            ];
        }

        return [
            'rc' => $elements['rc'],
            'max' => $elements['dret'][16],
            'min' => $elements['dret'][15],
            'true' => self::stateVector(self::normalizeBody($body), $tjdEt)[2],
            'error' => '',
        ];
    }

    /**
     * @return array{rc:int, max:float, min:float, true:float, error:string}
     */
    public static function maxMinTrueDistanceUt(
        float $tjdUt,
        int   $body,
        int   $flags = Catalog::SEFLG_DEFAULTEPH
    ): array
    {
        return self::maxMinTrueDistance(
            $tjdUt + DeltaT::deltatEx($tjdUt, $flags),
            $body,
            $flags
        );
    }

    public static function maxMinTrueDistanceResult(
        float $tjdEt,
        int   $body,
        int   $flags = Catalog::SEFLG_DEFAULTEPH
    ): OrbitDistanceResult
    {
        return OrbitDistanceResult::fromArray(self::maxMinTrueDistance($tjdEt, $body, $flags));
    }

    public static function maxMinTrueDistanceUtResult(
        float $tjdUt,
        int   $body,
        int   $flags = Catalog::SEFLG_DEFAULTEPH
    ): OrbitDistanceResult
    {
        return OrbitDistanceResult::fromArray(self::maxMinTrueDistanceUt($tjdUt, $body, $flags));
    }

    /**
     * @param array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float} $state
     * @return array<int, float>
     */
    private static function elementsFromState(float $tjdEt, int $body, array $state, int $flags): array
    {
        $cartesian = self::toCartesianVector($state);
        $position = [$cartesian[0], $cartesian[1], $cartesian[2]];
        $velocity = [$cartesian[3], $cartesian[4], $cartesian[5]];

        $radius = self::length($position);
        $speed2 = self::dotProduct($velocity, $velocity);
        $gm = self::gravitationalConstant($body, $flags);

        $normal = self::crossProduct($position, $velocity);
        $normalLength = self::length($normal);

        $inclination = $normalLength == 0.0
            ? 0.0
            : acos(max(-1.0, min(1.0, $normal[2] / $normalLength)));

        $node = [-$normal[1], $normal[0], 0.0];
        $nodeLength = self::length($node);

        $nodeLongitude = $nodeLength == 0.0
            ? 0.0
            : atan2($node[1], $node[0]);
        $nodeLongitude = self::mod2pi($nodeLongitude);

        $eccentricityVector = self::subtract3(
            self::multiply3(1.0 / $gm, self::crossProduct($velocity, $normal)),
            self::multiply3(1.0 / $radius, $position)
        );

        $eccentricity = self::length($eccentricityVector);
        $semiAxis = 1.0 / (2.0 / $radius - $speed2 / $gm);

        $argumentOfPeriapsis = 0.0;

        if ($nodeLength != 0.0 && $eccentricity != 0.0) {
            $argumentOfPeriapsis = self::angleBetween($node, $eccentricityVector);

            if ($eccentricityVector[2] < 0.0) {
                $argumentOfPeriapsis = 2.0 * M_PI - $argumentOfPeriapsis;
            }
        }

        $trueAnomaly = 0.0;

        if ($eccentricity != 0.0) {
            $trueAnomaly = self::angleBetween($eccentricityVector, $position);

            if (self::dotProduct($position, $velocity) < 0.0) {
                $trueAnomaly = 2.0 * M_PI - $trueAnomaly;
            }
        }

        $eccentricAnomaly = 2.0 * atan2(
                sqrt(1.0 - $eccentricity) * sin($trueAnomaly / 2.0),
                sqrt(1.0 + $eccentricity) * cos($trueAnomaly / 2.0)
            );
        $eccentricAnomaly = self::mod2pi($eccentricAnomaly);

        $meanAnomaly = self::mod2pi($eccentricAnomaly - $eccentricity * sin($eccentricAnomaly));
        $periapsisLongitude = self::mod2pi($nodeLongitude + $argumentOfPeriapsis);
        $meanLongitude = self::mod2pi($periapsisLongitude + $meanAnomaly);

        $periodDays = 2.0 * M_PI * sqrt($semiAxis * $semiAxis * $semiAxis / $gm);
        $meanDailyMotion = 360.0 / $periodDays;
        $timeOfPerihelion = $tjdEt - rad2deg($meanAnomaly) / $meanDailyMotion;

        return [
            0 => $semiAxis,
            1 => $eccentricity,
            2 => Angle::degnorm(rad2deg($inclination)),
            3 => Angle::degnorm(rad2deg($nodeLongitude)),
            4 => Angle::degnorm(rad2deg($argumentOfPeriapsis)),
            5 => Angle::degnorm(rad2deg($periapsisLongitude)),
            6 => Angle::degnorm(rad2deg($meanAnomaly)),
            7 => Angle::degnorm(rad2deg($trueAnomaly)),
            8 => Angle::degnorm(rad2deg($eccentricAnomaly)),
            9 => Angle::degnorm(rad2deg($meanLongitude)),
            10 => $periodDays / self::TROPICAL_YEAR_DAYS,
            11 => $meanDailyMotion,
            12 => $periodDays / self::TROPICAL_YEAR_DAYS,
            13 => self::synodicPeriod($body, $periodDays),
            14 => $timeOfPerihelion,
            15 => $semiAxis * (1.0 - $eccentricity),
            16 => $semiAxis * (1.0 + $eccentricity),
        ];
    }

    /**
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    private static function stateVector(int $body, float $tjdEt): array
    {
        if ($body === Catalog::SE_MOON) {
            if (!MoshierMoon::isInRange($tjdEt)) {
                throw new \InvalidArgumentException(MoshierMoon::rangeError($tjdEt));
            }

            return MoshierMoon::geocentric($tjdEt);
        }

        if ($body === Catalog::SE_EARTH) {
            return EarthPosition::heliocentric($tjdEt);
        }

        return PlanetPosition::heliocentric($body, $tjdEt);
    }

    private static function isSupported(int $body): bool
    {
        return $body === Catalog::SE_MOON
            || $body === Catalog::SE_EARTH
            || PlanetPosition::isSupported($body);
    }

    private static function normalizeBody(int $body): int
    {
        if ($body === Catalog::SE_AST_OFFSET + 134340) {
            return Catalog::SE_PLUTO;
        }

        return $body;
    }

    private static function synodicPeriod(int $body, float $periodDays): float
    {
        if ($body === Catalog::SE_MOON) {
            $period = 1.0 / (1.0 / $periodDays - 1.0 / self::TROPICAL_YEAR_DAYS);

            return -$period;
        }

        if ($body === Catalog::SE_EARTH) {
            return 0.0;
        }

        return 1.0 / (1.0 / self::TROPICAL_YEAR_DAYS - 1.0 / $periodDays);
    }

    private static function gravitationalConstant(int $body, int $flags): float
    {
        if ($body === Catalog::SE_MOON) {
            return self::GEOGCONST
                * (1.0 + 1.0 / self::EARTH_MOON_MASS_RATIO)
                / self::AUNIT
                / self::AUNIT
                / self::AUNIT
                * 86400.0
                * 86400.0;
        }

        $planetMass = Catalog::hasFlag($flags, Catalog::SEFLG_ORBEL_AA)
            ? (self::AA_INNER_MASS_SUM[$body] ?? 0.0)
            : 1.0 / self::PLANET_MASS_RATIO[$body];

        return self::HELGRAVCONST
            * (1.0 + $planetMass)
            / self::AUNIT
            / self::AUNIT
            / self::AUNIT
            * 86400.0
            * 86400.0;
    }

    /**
     * @param array<int, float> $position
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    private static function toCartesianVector(array $position): array
    {
        $position[0] = deg2rad($position[0]);
        $position[1] = deg2rad($position[1]);
        $position[3] = deg2rad($position[3]);
        $position[4] = deg2rad($position[4]);

        return Coordinates::polcartSp($position);
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
     * @param array{0:float, 1:float, 2:float} $left
     * @param array{0:float, 1:float, 2:float} $right
     * @return array{0:float, 1:float, 2:float}
     */
    private static function subtract3(array $left, array $right): array
    {
        return [
            $left[0] - $right[0],
            $left[1] - $right[1],
            $left[2] - $right[2],
        ];
    }

    /**
     * @param array{0:float, 1:float, 2:float} $vector
     * @return array{0:float, 1:float, 2:float}
     */
    private static function multiply3(float $factor, array $vector): array
    {
        return [
            $factor * $vector[0],
            $factor * $vector[1],
            $factor * $vector[2],
        ];
    }

    /**
     * @param array{0:float, 1:float, 2:float} $vector
     */
    private static function length(array $vector): float
    {
        return sqrt(self::dotProduct($vector, $vector));
    }

    /**
     * @param array{0:float, 1:float, 2:float} $left
     * @param array{0:float, 1:float, 2:float} $right
     */
    private static function angleBetween(array $left, array $right): float
    {
        $denominator = self::length($left) * self::length($right);

        if ($denominator == 0.0) {
            return 0.0;
        }

        $cosine = self::dotProduct($left, $right) / $denominator;
        $cosine = max(-1.0, min(1.0, $cosine));

        return acos($cosine);
    }

    private static function mod2pi(float $value): float
    {
        $value = fmod($value, 2.0 * M_PI);

        if ($value < 2.0) {
            $value += 2.0 * M_PI;
        }

        return $value;
    }

    /**
     * @return array<int, float>
     */
    private static function zeroElements(): array
    {
        return array_fill(0, 17, 0.0);
    }
}