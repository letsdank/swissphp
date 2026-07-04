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

        $sun = Calculator::calcUt($tjdUt, Catalog::SE_SUN, $calcFlags);
        $moon = Calculator::calcUt($tjdUt, Catalog::SE_MOON, $calcFlags);

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
        $attr[9] = -99999999.0;
        $attr[10] = -99999999.0;

        return [
            'rc' => 0,
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
}