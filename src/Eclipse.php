<?php

declare(strict_types=1);

namespace SwissEph;

final class Eclipse
{
    private const AUNIT_METERS = 149597870700.0;
    private const DSUN = 1392000000.0 / self::AUNIT_METERS;
    private const DMOON = 3476300.0 / self::AUNIT_METERS;
    private const DEARTH = 6378140.0 * 2.0 / self::AUNIT_METERS;
    private const RSUN = self::DSUN / 2.0;
    private const RMOON = self::DMOON / 2.0;
    private const REARTH = self::DEARTH / 2.0;
    private const EARTH_OBLATENESS = 1.0 / 298.257223563;

    private const LUNAR_ECLIPSE_SEARCH_STEP_DAYS = 1.0;
    private const LUNAR_ECLIPSE_SEARCH_MAX_DAYS = 740.0;
    private const LUNAR_ECLIPSE_CONTACT_STEP_DAYS = 0.05;
    private const LUNAR_ECLIPSE_CONTACT_MAX_STEPS = 80;
    private const PHASE_BISECTION_ITERATIONS = 80;
    private const LOCAL_LUNAR_ECLIPSE_SEARCH_MAX_EVENTS = 40;
    private const LOCAL_LUNAR_MOONRISE_SEARCH_MARGIN_DAYS = 0.05;

    /**
     * Geocentric subset of swe_lun_eclipse_how().
     *
     * attr[0] umbral magnitude
     * attr[1] penumbral magnitude
     * attr[7] distance of Moon from opposition in degrees
     * attr[8] umbral magnitude
     * attr[9] Saros series number, not implemented yet
     * attr[10] Saros series member number, not implemented yet
     *
     * @return array{rc:int, attr:array<int, float>, dcore:array<int, float>, error:string}
     */
    public static function lunarHow(
        float     $tjdUt,
        int       $flags = Catalog::SEFLG_DEFAULTEPH,
        ?Observer $observer = null,
        float     $pressure = 0.0,
        float     $temperature = 10.0,
    ): array
    {
        $attr = array_fill(0, 20, 0.0);
        $dcore = array_fill(0, 10, 0.0);

        if (
            $observer !== null
            && (
                $observer->altitude < Observer::MIN_GEOGRAPHIC_ALTITUDE
                || $observer->altitude > Observer::MAX_GEOGRAPHIC_LATITUDE
            )
        ) {
            return [
                'rc' => SwissDate::ERR,
                'attr' => $attr,
                'dcore' => $dcore,
                'error' => sprintf(
                    'location for eclipses must be between %.0f and %.0f m above sea',
                    Observer::MIN_GEOGRAPHIC_ALTITUDE,
                    Observer::MAX_GEOGRAPHIC_LATITUDE
                ),
            ];
        }

        $calcFlags = Catalog::normalizeEphemerisFlags($flags)
            | Catalog::SEFLG_SPEED
            | Catalog::SEFLG_EQUATORIAL
            | Catalog::SEFLG_XYZ;

        $moon = Calculator::calcUt($tjdUt, Catalog::SE_MOON, $calcFlags);
        $sun = Calculator::calcUt($tjdUt, Catalog::SE_SUN, $calcFlags);

        if ($moon['rc'] === SwissDate::ERR || $sun['rc'] === SwissDate::ERR) {
            return [
                'rc' => SwissDate::ERR,
                'attr' => $attr,
                'dcore' => $dcore,
                'error' => $moon['error'] !== '' ? $moon['error'] : $sun['error'],
            ];
        }

        $rm = $moon['xx'];
        $rs = $sun['xx'];

        $dm = self::vectorLength($rm);
        $ds = self::vectorLength($rs);

        $sunUnit = [$rs[0] / $ds, $rs[1] / $ds, $rs[2] / $ds];
        $moonUnit = [$rm[0] / $dm, $rm[1] / $dm, $rm[2] / $dm];

        $dctr = rad2deg(acos(self::clamp(self::dot($sunUnit, $moonUnit), -1.0, 1.0)));

        for ($i = 0; $i < 3; $i++) {
            $rs[$i] -= $rm[$i];
            $rm[$i] = -$rm[$i];
        }

        $e = [
            $rm[0] - $rs[0],
            $rm[1] - $rs[1],
            $rm[2] - $rs[2],
        ];

        $dsm = self::vectorLength($e);

        for ($i = 0; $i < 3; $i++) {
            $e[$i] /= $dsm;
        }

        $f1 = (self::RSUN - self::REARTH) / $dsm;
        $cosf1 = sqrt(1.0 - $f1 * $f1);
        $f2 = (self::RSUN + self::REARTH) / $dsm;
        $cosf2 = sqrt(1.0 - $f2 * $f2);

        $s0 = -self::dot($rm, $e);
        $r0 = sqrt($dm * $dm - $s0 * $s0);

        $d0 = abs($s0 / $dsm * (self::DSUN - self::DEARTH) - self::DEARTH)
            * (1.0 + 1.0 / 50.0)
            / $cosf1;

        $D0 = ($s0 / $dsm * (self::DSUN + self::DEARTH) + self::DEARTH)
            * (1.0 + 1.0 / 50.0)
            / $cosf2;

        $d0 /= $cosf1;
        $D0 /= $cosf2;

        $d0 *= 0.99405;
        $D0 *= 0.98813;

        $dcore[0] = $r0;
        $dcore[1] = $d0;
        $dcore[2] = $D0;
        $dcore[3] = $cosf1;
        $dcore[4] = $cosf2;

        $rc = 0;

        if ($d0 / 2.0 >= $r0 + self::RMOON / $cosf1) {
            $rc = Catalog::SE_ECL_TOTAL;
            $attr[0] = ($d0 / 2.0 - $r0 + self::RMOON) / self::DMOON;
        } elseif ($d0 / 2.0 >= $r0 - self::RMOON / $cosf1) {
            $rc = Catalog::SE_ECL_PARTIAL;
            $attr[0] = ($d0 / 2.0 - $r0 + self::RMOON) / self::DMOON;
        } elseif ($D0 / 2.0 >= $r0 - self::RMOON / $cosf2) {
            $rc = Catalog::SE_ECL_PENUMBRAL;
            $attr[0] = 0.0;
        }

        $geocentricRc = $rc;
        $attr[8] = $attr[0];
        $attr[1] = ($D0 / 2.0 - $r0 + self::RMOON) / self::DMOON;

        if ($rc !== 0) {
            $attr[7] = 180.0 - abs($dctr);
        }

        $attr[9] = -99999999.0;
        $attr[10] = -99999999.0;

        if ($observer !== null) {
            $moonEquatorial = Calculator::calcUt(
                $tjdUt,
                Catalog::SE_MOON,
                $calcFlags & ~Catalog::SEFLG_XYZ
            );

            if ($moonEquatorial['rc'] === SwissDate::ERR) {
                return [
                    'rc' => SwissDate::ERR,
                    'attr' => $attr,
                    'dcore' => $dcore,
                    'error' => $moonEquatorial['error'],
                ];
            }

            $horizontal = AzimuthAltitude::azalt(
                $tjdUt,
                Catalog::SE_EQU2HOR,
                $observer,
                $pressure,
                $temperature,
                $moonEquatorial['xx']
            );

            $attr[4] = $horizontal[0];
            $attr[5] = $horizontal[1];
            $attr[6] = $horizontal[2];

            if ($rc !== 0 && $horizontal[2] <= 0.0) {
                $rc = 0;
            }
        }

        return [
            'rc' => $rc,
            'attr' => $attr,
            'dcore' => $dcore,
            'error' => $geocentricRc === 0 ? sprintf('no lunar eclipse at tjd = %.6F', $tjdUt) : '',
        ];
    }

    public static function lunarHowResult(
        float     $tjdUt,
        int       $flags = Catalog::SEFLG_DEFAULTEPH,
        ?Observer $observer = null,
        float     $pressure = 0.0,
        float     $temperature = 10.0,
    ): EclipseResult
    {
        return EclipseResult::fromArray(self::lunarHow($tjdUt, $flags, $observer, $pressure, $temperature));
    }

    /**
     * Finds the next or previous lunar eclipse by scanning full moons.
     *
     * This is a compact Swiss Ephemeris compatible subset of swe_lun_eclipse_when().
     * tret[0] contains the UT Julian day of maximum eclipse.
     *
     * @return array{rc:int, tret:array<int, float>, attr:array<int, float>, dcore:array<int, float>, error:string}
     */
    public static function lunarWhen(
        float $tjdUt,
        int   $flags = Catalog::SEFLG_DEFAULTEPH,
        int   $eclipseTypes = Catalog::SE_ECL_ALLTYPES_LUNAR,
        bool  $backward = false,
    ): array
    {
        $tret = array_fill(0, 10, 0.0);
        $attr = array_fill(0, 20, 0.0);
        $dcore = array_fill(0, 10, 0.0);

        $direction = $backward ? -1.0 : 1.0;
        $left = $tjdUt;
        $leftDifference = self::lunarOppositionDifference($left, $flags);
        $end = $tjdUt + $direction * self::LUNAR_ECLIPSE_SEARCH_MAX_DAYS;

        if (is_nan($leftDifference)) {
            return [
                'rc' => SwissDate::ERR,
                'tret' => $tret,
                'attr' => $attr,
                'dcore' => $dcore,
                'error' => 'lunar eclipse search failed because Sun or Moon position could not be calculated',
            ];
        }

        for (
            $right = $tjdUt + $direction * self::LUNAR_ECLIPSE_SEARCH_STEP_DAYS;
            $backward ? $right >= $end - 1e-12 : $right <= $end + 1e-12;
            $right += $direction * self::LUNAR_ECLIPSE_SEARCH_STEP_DAYS
        ) {
            $rightDifference = self::lunarOppositionDifference($right, $flags);

            if (self::crossed($leftDifference, $rightDifference)) {
                $maximum = self::refineLunarOpposition($left, $right, $flags);
                $how = self::lunarHow($maximum, $flags);

                if ($how['rc'] === SwissDate::ERR) {
                    return [
                        'rc' => SwissDate::ERR,
                        'tret' => $tret,
                        'attr' => $attr,
                        'dcore' => $dcore,
                        'error' => $how['error'],
                    ];
                }

                if ($how['rc'] !== 0 && ($how['rc'] & $eclipseTypes) !== 0) {
                    $tret[0] = $maximum;
                    $tret = self::lunarContactTimes($tret, $maximum, $how['rc'], $flags);

                    return [
                        'rc' => $how['rc'],
                        'tret' => $tret,
                        'attr' => $how['attr'],
                        'dcore' => $how['dcore'],
                        'error' => '',
                    ];
                }
            }

            $left = $right;
            $leftDifference = $rightDifference;
        }

        return [
            'rc' => 0,
            'tret' => $tret,
            'attr' => $attr,
            'dcore' => $dcore,
            'error' => 'no lunar eclipse found within search window',
        ];
    }

    public static function lunarWhenResult(
        float $tjdUt,
        int   $flags = Catalog::SEFLG_DEFAULTEPH,
        int   $eclipseTypes = Catalog::SE_ECL_ALLTYPES_LUNAR,
        bool  $backward = false,
    ): EclipseWhenResult
    {
        return EclipseWhenResult::fromArray(self::lunarWhen($tjdUt, $flags, $eclipseTypes, $backward));
    }

    /**
     * @param array<int, float> $tret
     * @return array<int, float>
     */
    private static function lunarContactTimes(array $tret, float $maximum, int $rc, int $flags): array
    {
        $penumbralMask = Catalog::SE_ECL_PENUMBRAL | Catalog::SE_ECL_PARTIAL | Catalog::SE_ECL_TOTAL;
        $partialMask = Catalog::SE_ECL_PARTIAL | Catalog::SE_ECL_TOTAL;

        if (($rc & $penumbralMask) !== 0) {
            $tret[6] = self::lunarPhaseBoundary($maximum, $flags, $penumbralMask, true);
            $tret[7] = self::lunarPhaseBoundary($maximum, $flags, $penumbralMask, false);
        }

        if (($rc & $partialMask) !== 0) {
            $tret[2] = self::lunarPhaseBoundary($maximum, $flags, $partialMask, true);
            $tret[3] = self::lunarPhaseBoundary($maximum, $flags, $partialMask, false);
        }

        if (($rc & Catalog::SE_ECL_TOTAL) !== 0) {
            $tret[4] = self::lunarPhaseBoundary($maximum, $flags, Catalog::SE_ECL_TOTAL, true);
            $tret[5] = self::lunarPhaseBoundary($maximum, $flags, Catalog::SE_ECL_TOTAL, false);
        }

        return $tret;
    }

    private static function lunarPhaseBoundary(float $maximum, int $flags, int $phaseMask, bool $backward): float
    {
        $inside = $maximum;
        $outside = $maximum;
        $step = $backward ? -self::LUNAR_ECLIPSE_CONTACT_STEP_DAYS : self::LUNAR_ECLIPSE_CONTACT_STEP_DAYS;

        for ($i = 0; $i < self::LUNAR_ECLIPSE_CONTACT_MAX_STEPS; $i++) {
            $outside += $step;

            if (!self::isLunarPhase($outside, $flags, $phaseMask)) {
                break;
            }
        }

        if (self::isLunarPhase($outside, $flags, $phaseMask)) {
            return 0.0;
        }

        for ($i = 0; $i < self::PHASE_BISECTION_ITERATIONS; $i++) {
            $middle = ($inside + $outside) / 2.0;

            if (self::isLunarPhase($middle, $flags, $phaseMask)) {
                $inside = $middle;
            } else {
                $outside = $middle;
            }
        }

        return ($inside + $outside) / 2.0;
    }

    private static function isLunarPhase(float $tjdUt, int $flags, int $phaseMask): bool
    {
        $result = self::lunarHow($tjdUt, $flags);

        return $result['rc'] !== SwissDate::ERR
            && $result['rc'] !== 0
            && ($result['rc'] & $phaseMask) !== 0;
    }

    public static function lunarWhenLoc(
        float    $tjdUt,
        int      $flags,
        Observer $observer,
        bool     $backward = false,
        float    $pressure = 0.0,
        float    $temperature = 10.0,
        int      $eclipseTypes = Catalog::SE_ECL_ALLTYPES_LUNAR,
    ): array
    {
        $tret = array_fill(0, 10, 0.0);
        $attr = array_fill(0, 20, 0.0);
        $dcore = array_fill(0, 10, 0.0);
        $cursor = $tjdUt;

        for ($i = 0; $i < self::LOCAL_LUNAR_ECLIPSE_SEARCH_MAX_EVENTS; $i++) {
            $global = self::lunarWhen($cursor, $flags, $eclipseTypes, $backward);

            if ($global['rc'] === SwissDate::ERR || $global['rc'] === 0) {
                return [
                    'rc' => $global['rc'],
                    'tret' => $tret,
                    'attr' => $attr,
                    'dcore' => $dcore,
                    'error' => $global['error'],
                ];
            }

            $maximum = $global['tret'][0];
            $global['tret'][0] = $maximum;
            $global['tret'] = self::localLunarMoonriseMoonsetTimes(
                $global['tret'],
                $flags,
                $observer
            );

            $visibilityFlags = self::localLunarVisibilityFlags(
                $global['tret'],
                $flags,
                $observer,
                $pressure,
                $temperature
            );

            if (($visibilityFlags & Catalog::SE_ECL_VISIBLE) !== 0) {
                $local = self::lunarHow($global['tret'][0], $flags, $observer, $pressure, $temperature);

                if ($local['rc'] === SwissDate::ERR) {
                    return [
                        'rc' => SwissDate::ERR,
                        'tret' => $tret,
                        'attr' => $attr,
                        'dcore' => $dcore,
                        'error' => $local['error'],
                    ];
                }

                if ($local['rc'] !== 0 && ($local['rc'] & $eclipseTypes) !== 0) {
                    return [
                        'rc' => $local['rc'] | $visibilityFlags,
                        'tret' => $global['tret'],
                        'attr' => $local['attr'],
                        'dcore' => $local['dcore'],
                        'error' => '',
                    ];
                }
            }

            $cursor = $maximum + ($backward ? -1e-5 : 1e-5);
        }

        return [
            'rc' => 0,
            'tret' => $tret,
            'attr' => $attr,
            'dcore' => $dcore,
            'error' => 'no local lunar eclipse found within search window',
        ];
    }

    public static function lunarWhenLocResult(
        float    $tjdUt,
        int      $flags,
        Observer $observer,
        bool     $backward = false,
        float    $pressure = 0.0,
        float    $temperature = 10.0,
        int      $eclipseTypes = Catalog::SE_ECL_ALLTYPES_LUNAR,
    ): EclipseWhenResult
    {
        return EclipseWhenResult::fromArray(
            self::lunarWhenLoc($tjdUt, $flags, $observer, $backward, $pressure, $temperature, $eclipseTypes)
        );
    }

    /**
     * Swiss Ephemeris compatible placeholder for swe_sol_eclipse_when_glob().
     *
     * @return array{rc:int, tret:array<int, float>, attr:array<int, float>, dcore:array<int, float>, error:string}
     */
    public static function solarWhenGlob(
        float $tjdUt,
        int   $flags = Catalog::SEFLG_DEFAULTEPH,
        int   $eclipseTypes = Catalog::SE_ECL_ALLTYPES_SOLAR,
        bool  $backward = false,
    ): array
    {
        if ($eclipseTypes === (Catalog::SE_ECL_PARTIAL | Catalog::SE_ECL_CENTRAL)) {
            return [
                'rc' => SwissDate::ERR,
                'tret' => array_fill(0, 10, 0.0),
                'attr' => array_fill(0, 20, 0.0),
                'dcore' => array_fill(0, 10, 0.0),
                'error' => 'central partial eclipses do not exist',
            ];
        }

        if ($eclipseTypes === (Catalog::SE_ECL_ANNULAR_TOTAL | Catalog::SE_ECL_NONCENTRAL)) {
            return [
                'rc' => SwissDate::ERR,
                'tret' => array_fill(0, 10, 0.0),
                'attr' => array_fill(0, 20, 0.0),
                'dcore' => array_fill(0, 10, 0.0),
                'error' => 'non-central hybrid (annular-total) eclipses do not exist',
            ];
        }

        return [
            'rc' => SwissDate::ERR,
            'tret' => array_fill(0, 10, 0.0),
            'attr' => array_fill(0, 20, 0.0),
            'dcore' => array_fill(0, 10, 0.0),
            'error' => 'global solar eclipse search is not implemented yet',
        ];
    }

    public static function solarWhenGlobResult(
        float $tjdUt,
        int   $flags = Catalog::SEFLG_DEFAULTEPH,
        int   $eclipseTypes = Catalog::SE_ECL_ALLTYPES_SOLAR,
        bool  $backward = false,
    ): SolarEclipseWhenResult
    {
        return SolarEclipseWhenResult::fromArray(
            self::solarWhenGlob($tjdUt, $flags, $eclipseTypes, $backward)
        );
    }

    /**
     * Swiss Ephemeris compatible placeholder for swe_sol_eclipse_where().
     *
     * @return array{rc:int, geopos:array<int, float>, attr:array<int, float>, dcore:array<int, float>, error:string}
     */
    public static function solarWhere(
        float $tjdUt,
        int   $flags = Catalog::SEFLG_DEFAULTEPH,
    ): array
    {
        $seed = self::solarWhereSeed($tjdUt, $flags);

        if ($seed['rc'] === SwissDate::ERR || $seed['rc'] === 0) {
            return [
                'rc' => $seed['rc'],
                'geopos' => $seed['geopos'],
                'attr' => array_fill(0, 20, 0.0),
                'dcore' => $seed['dcore'],
                'error' => $seed['error'],
            ];
        }

        $maximum = self::solarWhereMaximum(
            $tjdUt,
            $flags,
            $seed['geopos'][0],
            $seed['geopos'][1]
        );

        if ($maximum['how']['rc'] === SwissDate::ERR) {
            return [
                'rc' => SwissDate::ERR,
                'geopos' => $seed['geopos'],
                'attr' => $maximum['how']['attr'],
                'dcore' => $seed['dcore'],
                'error' => $maximum['how']['error'],
            ];
        }

        if ($maximum['how']['rc'] === 0) {
            return [
                'rc' => 0,
                'geopos' => array_fill(0, 10, 0.0),
                'attr' => $maximum['how']['attr'],
                'dcore' => $seed['dcore'],
                'error' => sprintf('no solar eclipse at tjd = %.6F', $tjdUt),
            ];
        }

        $geopos = array_fill(0, 10, 0.0);
        $geopos[0] = $maximum['longitude'];
        $geopos[1] = $maximum['latitude'];

        $attr = $maximum['how']['attr'];
        $attr[3] = $seed['dcore'][0];

        $rc = $maximum['how']['rc'];
        $rc |= ($rc & (Catalog::SE_ECL_TOTAL | Catalog::SE_ECL_ANNULAR)) !== 0
            ? Catalog::SE_ECL_CENTRAL
            : Catalog::SE_ECL_NONCENTRAL;

        return [
            'rc' => $rc,
            'geopos' => $geopos,
            'attr' => $attr,
            'dcore' => $seed['dcore'],
            'error' => '',
        ];
    }

    public static function solarWhereResult(
        float $tjdUt,
        int   $flags = Catalog::SEFLG_DEFAULTEPH,
    ): SolarEclipseResult
    {
        return SolarEclipseResult::fromArray(self::solarWhere($tjdUt, $flags));
    }

    /**
     * Swiss Ephemeris compatible placeholder for swe_sol_eclipse_how().
     *
     * @return array{rc:int, attr:array<int, float>, dcore:array<int, float>, error:string}
     */
    public static function solarHow(
        float    $tjdUt,
        Observer $observer,
        int      $flags = Catalog::SEFLG_DEFAULTEPH,
    ): array
    {
        if (
            $observer->altitude < Observer::MIN_GEOGRAPHIC_ALTITUDE
            || $observer->altitude > Observer::MAX_GEOGRAPHIC_LATITUDE
        ) {
            return [
                'rc' => SwissDate::ERR,
                'attr' => array_fill(0, 20, 0.0),
                'dcore' => array_fill(0, 10, 0.0),
                'error' => sprintf(
                    'location for eclipses must be between %.0f and %.0f m above sea',
                    Observer::MIN_GEOGRAPHIC_ALTITUDE,
                    Observer::MAX_GEOGRAPHIC_LATITUDE
                ),
            ];
        }

        $attr = array_fill(0, 20, 0.0);
        $dcore = array_fill(0, 10, 0.0);

        $calcFlags = Catalog::normalizeEphemerisFlags($flags)
            | Catalog::SEFLG_SPEED
            | Catalog::SEFLG_EQUATORIAL;

        $sun = Calculator::calcTopoUt($tjdUt, Catalog::SE_SUN, $calcFlags, $observer);
        $moon = Calculator::calcTopoUt($tjdUt, Catalog::SE_MOON, $calcFlags, $observer);

        if ($sun['rc'] === SwissDate::ERR || $moon['rc'] === SwissDate::ERR) {
            return [
                'rc' => SwissDate::ERR,
                'attr' => $attr,
                'dcore' => $dcore,
                'error' => $sun['error'] !== '' ? $sun['error'] : $moon['error'],
            ];
        }

        $horizontal = AzimuthAltitude::azalt(
            $tjdUt,
            Catalog::SE_EQU2HOR,
            $observer,
            0.0,
            10.0,
            $sun['xx']
        );

        $attr[4] = $horizontal[0];
        $attr[5] = $horizontal[1];
        $attr[6] = $horizontal[2];
        $attr[7] = self::angularSeparationDegrees($sun['xx'], $moon['xx']);
        $attr[1] = self::angularDiameterRatio($sun['xx'][2], $moon['xx'][2]);

        $sunRadius = self::angularRadiusDegrees(self::DSUN, $sun['xx'][2]);
        $moonRadius = self::angularRadiusDegrees(self::DMOON, $moon['xx'][2]);

        $rc = 0;

        if ($attr[7] < $sunRadius + $moonRadius) {
            $attr[0] = max(0.0, min(1.0, ($sunRadius + $moonRadius - $attr[7]) / (2.0 * $sunRadius)));
            $attr[2] = self::discObscuration($sunRadius, $moonRadius, $attr[7]);

            if ($attr[7] <= abs($moonRadius - $sunRadius)) {
                $rc = $moonRadius >= $sunRadius ? Catalog::SE_ECL_TOTAL : Catalog::SE_ECL_ANNULAR;
                $attr[8] = $moonRadius >= $sunRadius ? $attr[1] : $attr[0];
            } else {
                $rc = Catalog::SE_ECL_PARTIAL;
                $attr[8] = $attr[0];
            }
        }

        $attr[9] = -99999999.0;
        $attr[10] = -99999999.0;

        return [
            'rc' => $rc,
            'attr' => $attr,
            'dcore' => $dcore,
            'error' => '',
        ];
    }

    public static function solarHowResult(
        float    $tjdUt,
        Observer $observer,
        int      $flags = Catalog::SEFLG_DEFAULTEPH,
    ): SolarEclipseResult
    {
        return SolarEclipseResult::fromArray(self::solarHow($tjdUt, $observer, $flags));
    }

    /**
     * @return array{rc:int, geopos:array<int, float>, dcore:array<int, float>, error:string}
     */
    private static function solarWhereSeed(float $tjdUt, int $flags): array
    {
        $geopos = array_fill(0, 10, 0.0);
        $dcore = array_fill(0, 10, 0.0);

        $calcFlags = Catalog::normalizeEphemerisFlags($flags)
            | Catalog::SEFLG_SPEED
            | Catalog::SEFLG_EQUATORIAL;

        $cartesianFlags = $calcFlags | Catalog::SEFLG_XYZ;
        $polarFlags = $calcFlags | Catalog::SEFLG_RADIANS;

        $moonCartesian = Calculator::calcUt($tjdUt, Catalog::SE_MOON, $cartesianFlags);
        $sunCartesian = Calculator::calcUt($tjdUt, Catalog::SE_SUN, $cartesianFlags);
        $moonPolar = Calculator::calcUt($tjdUt, Catalog::SE_MOON, $polarFlags);
        $sunPolar = Calculator::calcUt($tjdUt, Catalog::SE_SUN, $polarFlags);

        foreach ([$moonCartesian, $sunCartesian, $moonPolar, $sunPolar] as $result) {
            if ($result['rc'] === SwissDate::ERR) {
                return [
                    'rc' => SwissDate::ERR,
                    'geopos' => $geopos,
                    'dcore' => $dcore,
                    'error' => $result['error'],
                ];
            }
        }

        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, $flags);
        $nutation = SiderealTime::nutationApprox($tjdEt);
        $eps = SiderealTime::meanObliquity($tjdEt);
        $sidereal = SiderealTime::sidtime0($tjdUt, $eps + $nutation['deps'], $nutation['dpsi']) * 15.0;

        $earthOblateness = 1.0 - self::EARTH_OBLATENESS;
        $moon = array_slice($moonCartesian['xx'], 0, 3);
        $sun = array_slice($sunCartesian['xx'], 0, 3);

        for ($iteration = 0; $iteration < 2; $iteration++) {
            $rm = Coordinates::polcart([
                $moonPolar['xx'][0],
                $moonPolar['xx'][1],
                $moonPolar['xx'][2],
            ]);
            $rm[2] /= $earthOblateness;
            $dm = self::vectorLength($rm);

            $rs = Coordinates::polcart([
                $sunPolar['xx'][0],
                $sunPolar['xx'][1],
                $sunPolar['xx'][2],
            ]);
            $rs[2] /= $earthOblateness;

            $e = [
                $rm[0] - $rs[0],
                $rm[1] - $rs[1],
                $rm[2] - $rs[2],
            ];
            $dsm = self::vectorLength($e);

            for ($i = 0; $i < 3; $i++) {
                $e[$i] /= $dsm;
            }

            $sinf1 = (self::RSUN - self::RMOON) / $dsm;
            $cosf1 = sqrt(1.0 - $sinf1 * $sinf1);
            $sinf2 = (self::RSUN + self::RMOON) / $dsm;
            $cosf2 = sqrt(1.0 - $sinf2 * $sinf2);

            $s0 = -self::dot($rm, $e);
            $r0 = sqrt($dm * $dm - $s0 * $s0);

            $d0 = ($s0 / $dsm * (self::DSUN - self::DMOON) - self::DMOON) / $cosf1;
            $D0 = ($s0 / $dsm * (self::DSUN + self::DMOON) + self::DMOON) / $cosf2;

            $rc = 0;

            if (self::REARTH * $cosf1 >= $r0) {
                $rc |= Catalog::SE_ECL_CENTRAL;
            } elseif ($r0 <= self::REARTH * $cosf1 + abs($d0) / 2.0) {
                $rc |= Catalog::SE_ECL_NONCENTRAL;
            } elseif ($r0 <= self::REARTH * $cosf2 + $D0 / 2.0) {
                $rc |= Catalog::SE_ECL_PARTIAL | Catalog::SE_ECL_NONCENTRAL;
            } else {
                return [
                    'rc' => 0,
                    'geopos' => $geopos,
                    'dcore' => $dcore,
                    'error' => sprintf('no solar eclipse at tjd = %.6F', $tjdUt),
                ];
            }

            $distance = $s0 * $s0 + self::REARTH * self::REARTH - $dm * $dm;
            $distance = $distance > 0.0 ? sqrt($distance) : 0.0;
            $shadowDistance = $s0 - $distance;

            $xs = [
                $rm[0] + $shadowDistance * $e[0],
                $rm[1] + $shadowDistance * $e[1],
                $rm[2] + $shadowDistance * $e[2],
            ];

            $xst = $xs;
            $xst[2] *= $earthOblateness;
            $polar = Coordinates::cartpol($xst);

            if ($iteration === 0) {
                $cosLatitude = cos($polar[1]);
                $sinLatitude = sin($polar[1]);
                $flattening = 1.0 - self::EARTH_OBLATENESS;
                $cc = 1.0 / sqrt($cosLatitude * $cosLatitude + $flattening * $flattening * $sinLatitude * $sinLatitude);
                $earthOblateness = $flattening * $flattening * $cc;
                continue;
            }

            $longitude = rad2deg($polar[0] - deg2rad($sidereal));
            $longitude = self::normalizeGeographicLongitude($longitude);
            $latitude = rad2deg($polar[1]);

            $geopos[0] = $longitude;
            $geopos[1] = $latitude;

            $xst = Coordinates::polcart($polar);
            $moonToPlace = [
                $moon[0] - $xst[0],
                $moon[1] - $xst[1],
                $moon[2] - $xst[2],
            ];
            $moonSun = [
                $moon[0] - $sun[0],
                $moon[1] - $sun[1],
                $moon[2] - $sun[2],
            ];

            $moonPlaceDistance = self::vectorLength($moonToPlace);
            $moonSunDistance = self::vectorLength($moonSun);

            $dcore[0] = ($moonPlaceDistance / $moonSunDistance * (self::DSUN - self::DMOON) - self::DMOON)
                * $cosf1
                * self::AUNIT_METERS
                / 1000.0;
            $dcore[1] = ($moonPlaceDistance / $moonSunDistance * (self::DSUN + self::DMOON) + self::DMOON)
                * $cosf2
                * self::AUNIT_METERS
                / 1000.0;
            $dcore[2] = $r0 * self::AUNIT_METERS / 1000.0;
            $dcore[3] = $d0 * self::AUNIT_METERS / 1000.0;
            $dcore[4] = $D0 * self::AUNIT_METERS / 1000.0;
            $dcore[5] = $cosf1;
            $dcore[6] = $cosf2;

            if (($rc & Catalog::SE_ECL_PARTIAL) === 0) {
                $rc |= $dcore[0] > 0.0 ? Catalog::SE_ECL_ANNULAR : Catalog::SE_ECL_TOTAL;
            }

            return [
                'rc' => $rc,
                'geopos' => $geopos,
                'dcore' => $dcore,
                'error' => '',
            ];
        }

        return [
            'rc' => 0,
            'geopos' => $geopos,
            'dcore' => $dcore,
            'error' => sprintf('no solar eclipse at tjd = %.6F', $tjdUt),
        ];
    }

    /**
     * @return array{longitude:float, latitude:float, how:array{rc:int, attr:array<int, float>, dcore:array<int, float>, error:string}}
     */
    private static function solarWhereMaximum(float $tjdUt, int $flags, float $longitude, float $latitude): array
    {
        $evaluate = static function (float $candidateLongitude, float $candidateLatitude) use ($tjdUt, $flags): array {
            $how = self::solarHow(
                $tjdUt,
                new Observer(
                    self::normalizeGeographicLongitude($candidateLongitude),
                    max(-89.999, min(89.999, $candidateLatitude)),
                    0.0
                ),
                $flags
            );

            return [
                'longitude' => self::normalizeGeographicLongitude($candidateLongitude),
                'latitude' => max(-89.999, min(89.999, $candidateLatitude)),
                'score' => self::solarWhereScore($how),
                'how' => $how,
            ];
        };

        $best = $evaluate($longitude, $latitude);

        foreach ([10.0, 5.0, 2.0, 1.0, 0.5, 0.2, 0.1, 0.05] as $step) {
            do {
                $improved = false;
                $current = $best;

                foreach ([-1, 0, 1] as $longitudeDirection) {
                    foreach ([-1, 0, 1] as $latitudeDirection) {
                        if ($longitudeDirection === 0 && $latitudeDirection === 0) {
                            continue;
                        }

                        $candidate = $evaluate(
                            $current['longitude'] + $longitudeDirection * $step,
                            $current['latitude'] + $latitudeDirection * $step
                        );

                        if ($candidate['score'] > $best['score']) {
                            $best = $candidate;
                            $improved = true;
                        }
                    }
                }
            } while ($improved);
        }

        return [
            'longitude' => $best['longitude'],
            'latitude' => $best['latitude'],
            'how' => $best['how'],
        ];
    }

    /**
     * @param array{rc:int, attr:array<int, float>, dcore:array<int, float>, error:string} $how
     */
    private static function solarWhereScore(array $how): float
    {
        if ($how['rc'] === SwissDate::ERR) {
            return -INF;
        }

        return $how['attr'][2] * 1000000.0
            + $how['attr'][0] * 1000.0
            - $how['attr'][7];
    }

    private static function normalizeGeographicLongitude(float $longitude): float
    {
        $longitude = Angle::degnorm($longitude);

        return $longitude > 180.0 ? $longitude - 360.0 : $longitude;
    }

    /**
     * @param array<int, float> $tret
     */
    private static function localLunarVisibilityFlags(
        array    $tret,
        int      $flags,
        Observer $observer,
        float    $pressure,
        float    $temperature
    ): int
    {
        $visibilitySlots = [
            0 => Catalog::SE_ECL_MAX_VISIBLE,
            2 => Catalog::SE_ECL_PARTBEG_VISIBLE,
            3 => Catalog::SE_ECL_PARTEND_VISIBLE,
            4 => Catalog::SE_ECL_TOTBEG_VISIBLE,
            5 => Catalog::SE_ECL_TOTEND_VISIBLE,
            6 => Catalog::SE_ECL_PENUMBBEG_VISIBLE,
            7 => Catalog::SE_ECL_PENUMBEND_VISIBLE,
        ];

        $visibilityFlags = 0;

        foreach ($visibilitySlots as $index => $visibilityFlag) {
            if (($tret[$index] ?? 0.0) == 0.0) {
                continue;
            }

            $local = self::lunarHow($tret[$index], $flags, $observer, $pressure, $temperature);

            if ($local['rc'] !== SwissDate::ERR && $local['attr'][6] > 0.0) {
                $visibilityFlags |= Catalog::SE_ECL_VISIBLE | $visibilityFlag;
            }
        }

        return $visibilityFlags;
    }

    /**
     * @param array<int, float> $tret
     * @return array<int, float>
     */
    private static function localLunarMoonriseMoonsetTimes(
        array    $tret,
        int      $flags,
        Observer $observer
    ): array
    {
        $penumbralBegin = $tret[6] ?? 0.0;
        $penumbralEnd = $tret[7] ?? 0.0;

        if ($penumbralBegin == 0.0 || $penumbralEnd == 0.0) {
            return $tret;
        }

        $searchStart = $penumbralBegin - 0.001;
        $searchWindow = max(
            self::LOCAL_LUNAR_MOONRISE_SEARCH_MARGIN_DAYS,
            $penumbralEnd - $searchStart + self::LOCAL_LUNAR_MOONRISE_SEARCH_MARGIN_DAYS
        );

        $rise = RiseSet::riseTrans(
            $searchStart,
            Catalog::SE_MOON,
            $observer,
            Catalog::SE_CALC_RISE | Catalog::SE_BIT_DISC_BOTTOM,
            null,
            0.0,
            0.0,
            $flags,
            1.0 / 96.0,
            $searchWindow
        );

        $set = RiseSet::riseTrans(
            $searchStart,
            Catalog::SE_MOON,
            $observer,
            Catalog::SE_CALC_SET | Catalog::SE_BIT_DISC_BOTTOM,
            null,
            0.0,
            0.0,
            $flags,
            1.0 / 96.0,
            $searchWindow
        );

        if ($rise !== null && $rise['tjdUt'] > $penumbralBegin && $rise['tjdUt'] < $penumbralEnd) {
            $tret[8] = $rise['tjdUt'];
            $tret[6] = 0.0;

            for ($i = 2; $i <= 5; $i++) {
                if (($tret[$i] ?? 0.0) != 0.0 && $rise['tjdUt'] > $tret[$i]) {
                    $tret[$i] = 0.0;
                }
            }

            if ($rise['tjdUt'] > $tret[0]) {
                $tret[0] = $rise['tjdUt'];
            }
        }

        if ($set !== null && $set['tjdUt'] > $penumbralBegin && $set['tjdUt'] < $penumbralEnd) {
            $tret[9] = $set['tjdUt'];
            $tret[7] = 0.0;

            for ($i = 2; $i <= 5; $i++) {
                if (($tret[$i] ?? 0.0) != 0.0 && $set['tjdUt'] < $tret[$i]) {
                    $tret[$i] = 0.0;
                }
            }

            if ($set['tjdUt'] < $tret[0]) {
                $tret[0] = $set['tjdUt'];
            }
        }

        return $tret;
    }

    private static function refineLunarOpposition(float $left, float $right, int $flags): float
    {
        for ($i = 0; $i < self::PHASE_BISECTION_ITERATIONS; $i++) {
            $middle = ($left + $right) / 2.0;
            $leftDifference = self::lunarOppositionDifference($left, $flags);
            $rightDifference = self::lunarOppositionDifference($middle, $flags);

            if (self::crossed($leftDifference, $rightDifference)) {
                $right = $middle;
            } else {
                $left = $middle;
            }
        }

        return ($left + $right) / 2.0;
    }

    private static function lunarOppositionDifference(float $tjdUt, int $flags): float
    {
        $calcFlags = Catalog::normalizeEphemerisFlags($flags);

        $moon = Calculator::calcUt($tjdUt, Catalog::SE_MOON, $calcFlags);
        $sun = Calculator::calcUt($tjdUt, Catalog::SE_SUN, $calcFlags);

        if ($moon['rc'] === SwissDate::ERR || $sun['rc'] === SwissDate::ERR) {
            return NAN;
        }

        return Angle::difdeg2n($moon['xx'][0], $sun['xx'][0] + 180.0);
    }

    private static function crossed(float $left, float $right): bool
    {
        if (is_nan($left) || is_nan($right)) {
            return false;
        }

        return $left == 0.0
            || $left * $right <= 0.0
            || abs($left - $right) > 180.0;
    }

    /**
     * @param array<int, float> $vector
     */
    private static function vectorLength(array $vector): float
    {
        return sqrt($vector[0] * $vector[0] + $vector[1] * $vector[1] + $vector[2] * $vector[2]);
    }

    /**
     * @param array<int, float> $first
     * @param array<int, float> $second
     */
    private static function dot(array $first, array $second): float
    {
        return $first[0] * $second[0] + $first[1] * $second[1] + $first[2] * $second[2];
    }

    private static function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }

    /**
     * @param array<int, float> $first
     * @param array<int, float> $second
     */
    private static function angularSeparationDegrees(array $first, array $second): float
    {
        $firstLongitude = deg2rad($first[0]);
        $firstLatitude = deg2rad($first[1]);
        $secondLongitude = deg2rad($second[0]);
        $secondLatitude = deg2rad($second[1]);

        $cosine = sin($firstLatitude) * sin($secondLatitude)
            + cos($firstLatitude) * cos($secondLatitude) * cos($firstLongitude - $secondLongitude);

        return rad2deg(acos(self::clamp($cosine, -1.0, 1.0)));
    }

    private static function angularDiameterRatio(float $sunDistance, float $moonDistance): float
    {
        if ($sunDistance <= 0.0 || $moonDistance <= 0.0) {
            return 0.0;
        }

        return (self::DMOON / $moonDistance) / (self::DSUN / $sunDistance);
    }

    private static function angularRadiusDegrees(float $diameterAu, float $distanceAu): float
    {
        if ($diameterAu <= 0.0 || $distanceAu <= 0.0) {
            return 0.0;
        }

        return rad2deg(atan2($diameterAu / 2.0, $distanceAu));
    }

    private static function discObscuration(float $sunRadius, float $moonRadius, float $separation): float
    {
        if ($sunRadius <= 0.0 || $moonRadius <= 0.0 || $separation >= $sunRadius + $moonRadius) {
            return 0.0;
        }

        if ($separation <= abs($moonRadius - $sunRadius)) {
            $coveredRadius = min($sunRadius, $moonRadius);

            return min(1.0, ($coveredRadius * $coveredRadius) / ($sunRadius * $sunRadius));
        }

        $sunAngle = acos(self::clamp(
            ($separation * $separation + $sunRadius * $sunRadius - $moonRadius * $moonRadius)
            / (2.0 * $separation * $sunRadius),
            -1.0,
            1.0
        ));

        $moonAngle = acos(self::clamp(
            ($separation * $separation + $moonRadius * $moonRadius - $sunRadius * $sunRadius)
            / (2.0 * $separation * $moonRadius),
            -1.0,
            1.0
        ));

        $lensArea = $sunRadius * $sunRadius * $sunAngle
            + $moonRadius * $moonRadius * $moonAngle
            - 0.5 * sqrt(max(0.0,
                (-$separation + $sunRadius + $moonRadius)
                * ($separation + $sunRadius - $moonRadius)
                * ($separation - $sunRadius + $moonRadius)
                * ($separation + $sunRadius + $moonRadius)
            ));

        return max(0.0, min(1.0, $lensArea / (M_PI * $sunRadius * $sunRadius)));
    }
}