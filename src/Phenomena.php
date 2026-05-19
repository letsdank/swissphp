<?php

declare(strict_types=1);

namespace SwissEph;

final class Phenomena
{
    private const AUNIT_METERS = 149597870700.0;
    private const EARTH_RADIUS_METERS = 6378136.6;

    private const BODY_DIAMETERS_METERS = [
        Catalog::SE_SUN => 1392000000.0,
        Catalog::SE_MOON => 3475000.0,
        Catalog::SE_MERCURY => 2439400.0 * 2.0,
        Catalog::SE_VENUS => 6051800.0 * 2.0,
        Catalog::SE_MARS => 3389500.0 * 2.0,
        Catalog::SE_JUPITER => 69911000.0 * 2.0,
        Catalog::SE_SATURN => 58232000.0 * 2.0,
        Catalog::SE_URANUS => 25362000.0 * 2.0,
        Catalog::SE_NEPTUNE => 24622000.0 * 2.0,
        Catalog::SE_PLUTO => 1188300.0 * 2.0,
        Catalog::SE_EARTH => 6371008.4 * 2.0,
    ];

    /**
     * Compatible subset of swe_pheno().
     *
     * attr[0] phase angle
     * attr[1] illuminated fraction
     * attr[2] elongation
     * attr[3] apparent diameter
     * attr[4] apparent magnitude
     * attr[5] geocentric horizontal parallax, Moon only
     *
     * @return array{rc:int, attr:array<int, float>, error:string}
     */
    public static function pheno(
        float $tjdEt,
        int   $body,
        int   $flags = Catalog::SEFLG_DEFAULTEPH
    ): array
    {
        if ($body === Catalog::SE_AST_OFFSET + 134340) {
            $body = Catalog::SE_PLUTO;
        }

        $flags = Catalog::normalizeEphemerisFlags($flags);
        $flags &= Catalog::SEFLG_EPHMASK
            | Catalog::SEFLG_TRUEPOS
            | Catalog::SEFLG_J2000
            | Catalog::SEFLG_NONUT
            | Catalog::SEFLG_NOGDEFL
            | Catalog::SEFLG_NOABERR
            | Catalog::SEFLG_TOPOCTR;

        $calcFlags = $flags | Catalog::SEFLG_SPEED;

        try {
            $polarResult = Calculator::calcApparentFlags($tjdEt, $body, $calcFlags);
            $cartesianResult = Calculator::calcApparentFlags($tjdEt, $body, $calcFlags | Catalog::SEFLG_XYZ);
        } catch (\InvalidArgumentException $exception) {
            return [
                'rc' => SwissDate::ERR,
                'attr' => array_fill(0, 20, 0.0),
                'error' => $exception->getMessage(),
            ];
        }

        if ($polarResult['rc'] === SwissDate::ERR || $cartesianResult['rc'] === SwissDate::ERR) {
            return [
                'rc' => SwissDate::ERR,
                'attr' => array_fill(0, 20, 0.0),
                'error' => $polarResult['error'] !== '' ? $polarResult['error'] : $cartesianResult['error'],
            ];
        }

        $attr = array_fill(0, 20, 0.0);
        $polar = $polarResult['xx'];
        $heliocentricDistance = 1.0;
        $heliocentricPolar = null;
        $cartesian = $cartesianResult['xx'];

        if (self::canCalculatePhase($body)) {
            $lightTime = Catalog::hasFlag($flags, Catalog::SEFLG_TRUEPOS)
                ? 0.0
                : $polar[2] * Moshier::LIGHTTIME_AUNIT;

            $heliocentric = self::heliocentricBodyVector($body, $tjdEt - $lightTime);

            if ($heliocentric !== null) {
                $attr[0] = self::angleBetween($cartesian, $heliocentric);
                $attr[1] = (1.0 + cos(deg2rad($attr[0]))) / 2.0;
                $heliocentricDistance = self::vectorLength($heliocentric);
                $heliocentricPolar = self::cartesianToPolar($heliocentric);
            }
        }

        $attr[3] = self::apparentDiameter($body, $polar[2]);
        $attr[4] = self::apparentMagnitude($body, $attr, $polar, $heliocentricDistance, $heliocentricPolar, $tjdEt);

        if ($body !== Catalog::SE_SUN && $body !== Catalog::SE_EARTH) {
            try {
                $sunResult = Calculator::calcApparentFlags($tjdEt, Catalog::SE_SUN, $calcFlags | Catalog::SEFLG_XYZ);
            } catch (\InvalidArgumentException $exception) {
                return [
                    'rc' => SwissDate::ERR,
                    'attr' => array_fill(0, 20, 0.0),
                    'error' => $exception->getMessage(),
                ];
            }

            if ($sunResult['rc'] !== SwissDate::ERR) {
                $attr[2] = self::angleBetween($cartesian, $sunResult['xx']);
            }
        }

        if ($body === Catalog::SE_MOON && $polar[2] > 0.0) {
            $attr[5] = rad2deg(asin(self::EARTH_RADIUS_METERS / ($polar[2] * self::AUNIT_METERS)));
        }

        return [
            'rc' => $polarResult['rc'],
            'attr' => $attr,
            'error' => '',
        ];
    }

    /**
     * @return array{rc:int, attr:array<int, float>, error:string}
     */
    public static function phenoUt(
        float $tjdUt,
        int   $body,
        int   $flags = Catalog::SEFLG_DEFAULTEPH
    ): array
    {
        return self::pheno(
            $tjdUt + DeltaT::deltatEx($tjdUt, $flags),
            $body,
            $flags
        );
    }

    public static function phenoResult(
        float $tjdEt,
        int   $body,
        int   $flags = Catalog::SEFLG_DEFAULTEPH
    ): PhenomenaResult
    {
        return PhenomenaResult::fromArray(self::pheno($tjdEt, $body, $flags));
    }

    public static function phenoUtResult(
        float $tjdUt,
        int   $body,
        int   $flags = Catalog::SEFLG_DEFAULTEPH
    ): PhenomenaResult
    {
        return PhenomenaResult::fromArray(self::phenoUt($tjdUt, $body, $flags));
    }

    private static function canCalculatePhase(int $body): bool
    {
        return $body !== Catalog::SE_SUN
            && $body !== Catalog::SE_EARTH
            && $body !== Catalog::SE_MEAN_NODE
            && $body !== Catalog::SE_TRUE_NODE
            && $body !== Catalog::SE_MEAN_APOG
            && $body !== Catalog::SE_OSCU_APOG;
    }

    /**
     * @return array{0:float, 1:float, 2:float}|null
     */
    private static function heliocentricBodyVector(int $body, float $tjdEt): ?array
    {
        if ($body === Catalog::SE_MOON) {
            $earth = self::polarToCartesian(EarthPosition::heliocentric($tjdEt));
            $moon = self::polarToCartesian(MoshierMoon::geocentric($tjdEt));

            return [
                $earth[0] + $moon[0],
                $earth[1] + $moon[1],
                $earth[2] + $moon[2],
            ];
        }

        if (PlanetPosition::isSupported($body)) {
            return self::polarToCartesian(PlanetPosition::heliocentric($body, $tjdEt));
        }

        return null;
    }

    /**
     * @param array<int, float> $polar
     * @return array{0:float, 1:float, 2:float}
     */
    private static function polarToCartesian(array $polar): array
    {
        return Coordinates::polcart([
            deg2rad($polar[0]),
            deg2rad($polar[1]),
            $polar[2],
        ]);
    }

    /**
     * @param array<int, float> $cartesian
     * @return array{0:float, 1:float, 2:float}
     */
    private static function cartesianToPolar(array $cartesian): array
    {
        $polar = Coordinates::cartpol([$cartesian[0], $cartesian[1], $cartesian[2]]);

        return [
            Angle::degnorm(rad2deg($polar[0])),
            rad2deg($polar[1]),
            $polar[2],
        ];
    }

    /**
     * @param array<int, float> $vector
     */
    private static function vectorLength(array $vector): float
    {
        return sqrt(
            $vector[0] * $vector[0]
            + $vector[1] * $vector[1]
            + $vector[2] * $vector[2]
        );
    }

    /**
     * @param array<int, float> $left
     * @param array<int, float> $right
     */
    private static function angleBetween(array $left, array $right): float
    {
        $leftLength = sqrt($left[0] * $left[0] + $left[1] * $left[1] + $left[2] * $left[2]);
        $rightLength = sqrt($right[0] * $right[0] + $right[1] * $right[1] + $right[2] * $right[2]);

        if ($leftLength == 0.0 || $rightLength == 0.0) {
            return 0.0;
        }

        $cosine = ($left[0] * $right[0] + $left[1] * $right[1] + $left[2] * $right[2])
            / ($leftLength * $rightLength);

        return rad2deg(acos(max(-1.0, min(1.0, $cosine))));
    }

    private static function apparentDiameter(int $body, float $distanceAu): float
    {
        $diameter = self::BODY_DIAMETERS_METERS[$body] ?? 0.0;

        if ($diameter <= 0.0 || $distanceAu <= 0.0) {
            return 0.0;
        }

        $radiusRatio = $diameter / 2.0 / self::AUNIT_METERS / $distanceAu;

        if ($radiusRatio >= 1.0) {
            return 180.0;
        }

        return rad2deg(asin($radiusRatio)) * 2.0;
    }

    /**
     * @param array<int, float> $polar
     */
    private static function planetMagnitude(
        int    $body,
        float  $phaseAngle,
        array  $polar,
        float  $heliocentricDistance,
        ?array $heliocentricPolar,
        float  $tjdEt
    ): float
    {
        $distanceTerm = 5.0 * log10($heliocentricDistance * $polar[2]);
        $a = $phaseAngle;
        $a2 = $a * $a;
        $a3 = $a2 * $a;
        $a4 = $a3 * $a;
        $a5 = $a4 * $a;
        $a6 = $a5 * $a;

        return match ($body) {
            Catalog::SE_MERCURY => -0.613
                + $a * 6.3280E-02
                - $a2 * 1.6336E-03
                + $a3 * 3.3644E-05
                - $a4 * 3.4265E-07
                + $a5 * 1.6893E-09
                - $a6 * 3.0334E-12
                + $distanceTerm,

            Catalog::SE_VENUS => (
                $a <= 163.7
                    ? -4.384
                    - $a * 1.044E-3
                    + $a2 * 3.687E-04
                    - $a3 * 2.814E-06
                    + $a4 * 8.938E-09
                    : 236.05828
                    - $a * 2.81914E+00
                    + $a2 * 8.39034E-03
                ) + $distanceTerm,

            Catalog::SE_MARS => (
                $a <= 50.0
                    ? -1.601
                    + $a * 0.02267
                    - $a2 * 0.0001302
                    : -0.367
                    - $a * 0.02573
                    + $a2 * 0.0003445
                ) + $distanceTerm,

            Catalog::SE_JUPITER => -9.395
                - $a * 3.7E-04
                + $a2 * 6.16E-04
                + $distanceTerm,

            Catalog::SE_SATURN => self::saturnMagnitude($phaseAngle, $polar, $heliocentricDistance, $heliocentricPolar, $tjdEt),

            Catalog::SE_URANUS => -7.110
                + $a * 6.587E-3
                + $a2 * 1.045E-4
                - 0.05
                + $distanceTerm,

            Catalog::SE_NEPTUNE => -7.00 + $distanceTerm,

            Catalog::SE_PLUTO => -1.00 + $distanceTerm,

            default => 0.0,
        };
    }

    /**
     * @param array<int, float> $polar
     */
    private static function saturnMagnitude(
        float  $phaseAngle,
        array  $polar,
        float  $heliocentricDistance,
        ?array $heliocentricPolar,
        float  $tjdEt
    ): float
    {
        $distanceTerm = 5.0 * log10($heliocentricDistance * $polar[2]);
        $t = ($tjdEt - Moshier::J2000) / 36525.0;
        $inclination = deg2rad(28.075216 - 0.012998 * $t + 0.000004 * $t * $t);
        $node = deg2rad(169.508470 + 1.394681 * $t + 0.000412 * $t * $t);

        $geocentricSinB = self::saturnRingLatitudeSine($polar, $inclination, $node);

        if ($heliocentricPolar !== null) {
            $heliocentricSinB = self::saturnRingLatitudeSine($heliocentricPolar, $inclination, $node);
            $sinB = abs(sin((asin($geocentricSinB) + asin($heliocentricSinB)) / 2.0));
        } else {
            $sinB = abs($geocentricSinB);
        }

        return -8.914
            - 1.825 * $sinB
            + 0.026 * $phaseAngle
            - 0.378 * $sinB * exp(-2.25 * $phaseAngle)
            + $distanceTerm;
    }

    /**
     * @param array<int, float> $polar
     */
    private static function saturnRingLatitudeSine(array $polar, float $inclination, float $node): float
    {
        return sin($inclination)
            * cos(deg2rad($polar[1]))
            * sin(deg2rad($polar[0]) - $node)
            - cos($inclination) * sin(deg2rad($polar[1]));
    }

    /**
     * @param array<int, float> $attr
     * @param array<int, float> $polar
     */
    private static function apparentMagnitude(
        int    $body,
        array  $attr,
        array  $polar,
        float  $heliocenticDistance,
        ?array $heliocentricPolar,
        float  $tjdEt
    ): float
    {
        if ($body === Catalog::SE_SUN) {
            $averageDiameter = rad2deg(asin(self::BODY_DIAMETERS_METERS[Catalog::SE_SUN] / 2.0 / self::AUNIT_METERS)) * 2.0;
            $factor = ($attr[3] / $averageDiameter) ** 2;

            return -26.86 - 2.5 * log10($factor);
        }

        if ($body === Catalog::SE_MOON) {
            $phaseAngle = $attr[0];

            if ($phaseAngle <= 147.1385465) {
                $magnitude = -21.62
                    + 0.026 * abs($phaseAngle)
                    + 0.000000004 * $phaseAngle ** 4;
            } else {
                $magnitude = -4.5444 - 2.5 * log10((180.0 - $phaseAngle) ** 3);
            }

            return $magnitude + 5.0 * log10($polar[2] * $heliocenticDistance * self::AUNIT_METERS / self::EARTH_RADIUS_METERS);
        }

        if (PlanetPosition::isSupported($body)) {
            return self::planetMagnitude($body, $attr[0], $polar, $heliocenticDistance, $heliocentricPolar, $tjdEt);
        }

        return 0.0;
    }
}