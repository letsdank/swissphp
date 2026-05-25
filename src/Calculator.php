<?php

declare(strict_types=1);

namespace SwissEph;

final class Calculator
{
    public static function julday(
        int   $year,
        int   $month,
        int   $day,
        float $hour,
        int   $gregflag
    ): float
    {
        return SwissDate::julday($year, $month, $day, $hour, $gregflag);
    }

    /**
     * @return array{year:int, month:int, day:int, hour:float}
     */
    public static function revjul(float $jd, int $gregflag): array
    {
        return SwissDate::revjul($jd, $gregflag);
    }

    /**
     * @return array{rc:int, jd:float}
     */
    public static function dateConversion(
        int    $year,
        int    $month,
        int    $day,
        float  $uttime,
        string $calendar
    ): array
    {
        return SwissDate::dateConversion($year, $month, $day, $uttime, $calendar);
    }

    /**
     * @return array{year:int, month:int, day:int, hour:int, minute:int, second:float}
     */
    public static function utcTimeZone(
        int   $year,
        int   $month,
        int   $day,
        int   $hour,
        int   $minute,
        float $second,
        float $timezone
    ): array
    {
        return SwissDate::utcTimeZone($year, $month, $day, $hour, $minute, $second, $timezone);
    }

    /**
     * Swiss Ephemeris compatible subset of swe_utc_to_jd().
     *
     * @return array{rc:int, tt:float, ut1:float, error:string}
     */
    public static function utcToJd(
        int   $year,
        int   $month,
        int   $day,
        int   $hour,
        int   $minute,
        float $second,
        int   $gregflag
    ): array
    {
        return UtcTime::utcToJd($year, $month, $day, $hour, $minute, $second, $gregflag);
    }

    /**
     * Swiss Ephemeris compatible subset of swe_jdet_to_utc().
     *
     * @return array{year:int, month:int, day:int, hour:int, minute:int, second:float}
     */
    public static function jdetToUtc(float $tjdEt, int $gregflag): array
    {
        return UtcTime::jdetToUtc($tjdEt, $gregflag);
    }

    /**
     * Swiss Ephemeris compatible subset of swe_jdut1_to_utc().
     *
     * @return array{year:int, month:int, day:int, hour:int, minute:int, second:float}
     */
    public static function jdut1ToUtc(float $tjdUt, int $gregflag): array
    {
        return UtcTime::jdut1ToUtc($tjdUt, $gregflag);
    }

    /**
     * Swiss Ephemeris compatible subset of swe_calc().
     *
     * Currently supports SE_SUN with ecliptic polar coordinates.
     *
     * @return array{rc:int, xx:array<int, float>, error:string}
     */
    public static function calc(
        float $tjdEt,
        int   $ipl,
        int   $iflag = Catalog::SEFLG_DEFAULTEPH,
        int   $sidMode = Catalog::SE_SIDM_FAGAN_BRADLEY
    ): array
    {
        $iflag = Catalog::normalizeEphemerisFlags($iflag);
        $iflag = self::normalizeCalculationFlags($iflag);

        $withSpeed = Catalog::wantsSpeed($iflag);

        if (self::usesMoshierMoonRange($ipl) && !MoshierMoon::isInRange($tjdEt)) {
            return [
                'rc' => SwissDate::ERR,
                'xx' => [0.0, 0.0, 0.0, 0.0, 0.0, 0.0],
                'error' => MoshierMoon::rangeError($tjdEt),
            ];
        }

        if (self::usesMoshierPlanetRange($ipl) && !Moshier::isInPlanetRange($tjdEt)) {
            return [
                'rc' => SwissDate::ERR,
                'xx' => [0.0, 0.0, 0.0, 0.0, 0.0, 0.0],
                'error' => Moshier::planetRangeError($tjdEt),
            ];
        }

        $xx = self::basePosition($tjdEt, $ipl, $iflag);

        if ($xx === null) {
            return [
                'rc' => SwissDate::ERR,
                'xx' => [0.0, 0.0, 0.0, 0.0, 0.0, 0.0],
                'error' => 'Unsupported planet or flag combination.',
            ];
        }

        $xx = self::applySidereal($xx, $tjdEt, $iflag, $sidMode);
        $xx = self::finalizePosition($xx, $tjdEt, $iflag, $withSpeed);

        return [
            'rc' => $iflag,
            'xx' => $xx,
            'error' => '',
        ];
    }

    /**
     * Swiss Ephemeris compatible subset of swe_calc_ut().
     *
     * @return array{rc:int, xx:array<int, float>, error:string}
     */
    public static function calcUt(
        float $tjdUt,
        int   $ipl,
        int   $iflag = Catalog::SEFLG_DEFAULTEPH,
        int   $sidMode = Catalog::SE_SIDM_FAGAN_BRADLEY
    ): array
    {
        return self::calc(
            $tjdUt + DeltaT::deltatEx($tjdUt, $iflag),
            $ipl,
            $iflag,
            $sidMode
        );
    }

    /**
     * Geocentric subset of swe_lun_eclipse_how().
     *
     * @return array{rc:int, attr:array<int, float>, dcore:array<int, float>, error:string}
     */
    public static function lunEclipseHow(
        float     $tjdUt,
        int       $flags = Catalog::SEFLG_DEFAULTEPH,
        ?Observer $observer = null,
        float     $pressure = 0.0,
        float     $temperature = 10.0,
    ): array
    {
        return Eclipse::lunarHow($tjdUt, $flags, $observer, $pressure, $temperature);
    }

    public static function lunEclipseHowResult(
        float     $tjdUt,
        int       $flags = Catalog::SEFLG_DEFAULTEPH,
        ?Observer $observer = null,
        float     $pressure = 0.0,
        float     $temperature = 10.0,
    ): EclipseResult
    {
        return Eclipse::lunarHowResult($tjdUt, $flags, $observer, $pressure, $temperature);
    }

    /**
     * Swiss Ephemeris compatible subset of swe_lun_eclipse_when().
     *
     * @return array{rc:int, tret:array<int, float>, attr:array<int, float>, dcore:array<int, float>, error:string}
     */
    public static function lunEclipseWhen(
        float $tjdUt,
        int   $flags = Catalog::SEFLG_DEFAULTEPH,
        int   $eclipseTypes = Catalog::SE_ECL_ALLTYPES_LUNAR,
        bool  $backward = false,
    ): array
    {
        return Eclipse::lunarWhen($tjdUt, $flags, $eclipseTypes, $backward);
    }

    public static function lunEclipseWhenResult(
        float $tjdUt,
        int   $flags = Catalog::SEFLG_DEFAULTEPH,
        int   $eclipseTypes = Catalog::SE_ECL_ALLTYPES_LUNAR,
        bool  $backward = false,
    ): EclipseWhenResult
    {
        return Eclipse::lunarWhenResult($tjdUt, $flags, $eclipseTypes, $backward);
    }

    /**
     * Apparent geocentric ecliptic position for Sun and Moshier planets.
     *
     * This is an explicit apparent API and does not change swe_calc()-style calc().
     *
     * @return array{rc:int, xx:array<int, float>, error:string}
     */
    public static function calcApparent(
        float $tjdEt,
        int   $ipl,
        bool  $withNutation = true,
        bool  $withDeflection = true,
        bool  $withAberration = true
    ): array
    {
        if ($ipl === Catalog::SE_SUN) {
            return [
                'rc' => Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH,
                'xx' => SolarPosition::apparent($tjdEt, $withNutation),
                'error' => '',
            ];
        }

        if ($ipl === Catalog::SE_MOON) {
            return [
                'rc' => Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH,
                'xx' => MoshierMoon::apparent(
                    $tjdEt,
                    $withNutation,
                    $withDeflection,
                    $withAberration
                ),
                'error' => '',
            ];
        }

        if ($ipl === Catalog::SE_MEAN_NODE) {
            return [
                'rc' => Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH,
                'xx' => MeanNode::apparent($tjdEt, $withNutation),
                'error' => '',
            ];
        }

        if ($ipl === Catalog::SE_TRUE_NODE) {
            return [
                'rc' => Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH,
                'xx' => TrueNode::apparent($tjdEt, $withNutation),
                'error' => '',
            ];
        }

        if ($ipl === Catalog::SE_OSCU_APOG) {
            return [
                'rc' => Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH,
                'xx' => OsculatingApogee::apparent($tjdEt, $withNutation),
                'error' => '',
            ];
        }

        if ($ipl === Catalog::SE_MEAN_APOG) {
            return [
                'rc' => Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH,
                'xx' => MeanApogee::apparent($tjdEt, $withNutation),
                'error' => '',
            ];
        }

        if (PlanetPosition::isSupported($ipl)) {
            return [
                'rc' => Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH,
                'xx' => PlanetPosition::apparent(
                    $ipl,
                    $tjdEt,
                    $withNutation,
                    $withDeflection,
                    $withAberration
                ),
                'error' => '',
            ];
        }

        return [
            'rc' => SwissDate::ERR,
            'xx' => [0.0, 0.0, 0.0, 0.0, 0.0, 0.0],
            'error' => 'Unsupported planet or flag combination.',
        ];
    }

    /**
     * @return array{rc:int, xx:array<int, float>, error:string}
     */
    public static function calcApparentUt(
        float $tjdUt,
        int   $ipl,
        bool  $withNutation = true,
        bool  $withDeflection = true,
        bool  $withAberration = true
    ): array
    {
        return self::calcApparent(
            $tjdUt + DeltaT::deltatEx($tjdUt, -1),
            $ipl,
            $withNutation,
            $withDeflection,
            $withAberration
        );
    }

    /**
     * Apparent geocentric ecliptic position using Swiss Ephemeris flags
     * to enable or disable correction layers.
     *
     * Supported flags here:
     * - SEFLG_NONUT disabled ecliptic nutation
     * - SEFLG_NOGDEFL disables solar gravitational deflection
     * - SEFLG_NOABERR disables annual aberration
     * - SEFLG_ASTROMETRIC disabled deflection and aberration
     * - SEFLG_TRUEPOS returns geometric position without light-time/corrections
     *
     * @return array{rc:int, xx:array<int, float>, error:string}
     */
    public static function calcApparentFlags(
        float $tjdEt,
        int   $ipl,
        int   $iflag,
        int   $sidMode = Catalog::SE_SIDM_FAGAN_BRADLEY
    ): array
    {
        $iflag = Catalog::normalizeEphemerisFlags($iflag);
        $iflag = self::normalizeCalculationFlags($iflag);
        $withSpeed = Catalog::wantsSpeed($iflag);

        if ($ipl === Catalog::SE_SUN) {
            if (Catalog::hasFlag($iflag, Catalog::SEFLG_TRUEPOS)) {
                $xx = SolarPosition::position($tjdEt);
            } else {
                $xx = SolarPosition::apparent(
                    $tjdEt,
                    !Catalog::hasFlag($iflag, Catalog::SEFLG_NONUT)
                );
            }

            $xx = self::applySidereal($xx, $tjdEt, $iflag, $sidMode);

            return [
                'rc' => $iflag,
                'xx' => self::finalizePosition($xx, $tjdEt, $iflag, $withSpeed),
                'error' => '',
            ];
        }

        if ($ipl === Catalog::SE_MOON) {
            if (Catalog::hasFlag($iflag, Catalog::SEFLG_TRUEPOS)) {
                $xx = MoshierMoon::geocentric($tjdEt);
            } else {
                $xx = MoshierMoon::apparent(
                    $tjdEt,
                    !Catalog::hasFlag($iflag, Catalog::SEFLG_NONUT),
                    !Catalog::hasFlag($iflag, Catalog::SEFLG_NOGDEFL),
                    !Catalog::hasFlag($iflag, Catalog::SEFLG_NOABERR)
                );
            }

            $xx = self::applySidereal($xx, $tjdEt, $iflag, $sidMode);

            return [
                'rc' => $iflag,
                'xx' => self::finalizePosition($xx, $tjdEt, $iflag, $withSpeed),
                'error' => '',
            ];
        }

        if ($ipl === Catalog::SE_MEAN_NODE) {
            if (Catalog::hasFlag($iflag, Catalog::SEFLG_TRUEPOS)) {
                $xx = MeanNode::geocentric($tjdEt);
            } else {
                $xx = MeanNode::apparent(
                    $tjdEt,
                    !Catalog::hasFlag($iflag, Catalog::SEFLG_NONUT)
                );
            }

            $xx = self::applySidereal($xx, $tjdEt, $iflag, $sidMode);

            return [
                'rc' => $iflag,
                'xx' => self::finalizePosition($xx, $tjdEt, $iflag, $withSpeed),
                'error' => '',
            ];
        }

        if ($ipl === Catalog::SE_TRUE_NODE) {
            if (Catalog::hasFlag($iflag, Catalog::SEFLG_TRUEPOS)) {
                $xx = TrueNode::geocentric($tjdEt);
            } else {
                $xx = TrueNode::apparent(
                    $tjdEt,
                    !Catalog::hasFlag($iflag, Catalog::SEFLG_NONUT)
                );
            }

            $xx = self::applySidereal($xx, $tjdEt, $iflag, $sidMode);

            return [
                'rc' => $iflag,
                'xx' => self::finalizePosition($xx, $tjdEt, $iflag, $withSpeed),
                'error' => '',
            ];
        }

        if ($ipl === Catalog::SE_OSCU_APOG) {
            if (Catalog::hasFlag($iflag, Catalog::SEFLG_TRUEPOS)) {
                $xx = OsculatingApogee::geocentric($tjdEt);
            } else {
                $xx = OsculatingApogee::apparent(
                    $tjdEt,
                    !Catalog::hasFlag($iflag, Catalog::SEFLG_NONUT)
                );
            }

            $xx = self::applySidereal($xx, $tjdEt, $iflag, $sidMode);

            return [
                'rc' => $iflag,
                'xx' => self::finalizePosition($xx, $tjdEt, $iflag, $withSpeed),
                'error' => '',
            ];
        }

        if ($ipl === Catalog::SE_MEAN_APOG) {
            if (Catalog::hasFlag($iflag, Catalog::SEFLG_TRUEPOS)) {
                $xx = MeanApogee::geocentric($tjdEt);
            } else {
                $xx = MeanApogee::apparent(
                    $tjdEt,
                    !Catalog::hasFlag($iflag, Catalog::SEFLG_NONUT)
                );
            }

            $xx = self::applySidereal($xx, $tjdEt, $iflag, $sidMode);

            return [
                'rc' => $iflag,
                'xx' => self::finalizePosition($xx, $tjdEt, $iflag, $withSpeed),
                'error' => '',
            ];
        }

        if (PlanetPosition::isSupported($ipl)) {
            if (Catalog::hasFlag($iflag, Catalog::SEFLG_TRUEPOS)) {
                $xx = PlanetPosition::geocentric($ipl, $tjdEt);
            } else {
                $xx = PlanetPosition::apparent(
                    $ipl,
                    $tjdEt,
                    !Catalog::hasFlag($iflag, Catalog::SEFLG_NONUT),
                    !Catalog::hasFlag($iflag, Catalog::SEFLG_NOGDEFL),
                    !Catalog::hasFlag($iflag, Catalog::SEFLG_NOABERR)
                );
            }

            $xx = self::applySidereal($xx, $tjdEt, $iflag, $sidMode);

            return [
                'rc' => $iflag,
                'xx' => self::finalizePosition($xx, $tjdEt, $iflag, $withSpeed),
                'error' => '',
            ];
        }

        return [
            'rc' => SwissDate::ERR,
            'xx' => [0.0, 0.0, 0.0, 0.0, 0.0, 0.0],
            'error' => 'Unsupported planet or flag combination.',
        ];
    }

    /**
     * @return array{rc:int, xx:array<int, float>, error:string}
     */
    public static function calcApparentFlagsUt(
        float $tjdUt,
        int   $ipl,
        int   $iflag,
        int   $sidMode = Catalog::SE_SIDM_FAGAN_BRADLEY
    ): array
    {
        return self::calcApparentFlags(
            $tjdUt + DeltaT::deltatEx($tjdUt, $iflag),
            $ipl,
            $iflag,
            $sidMode
        );
    }

    public static function calcResult(
        float $tjdEt,
        int   $ipl,
        int   $iflag = Catalog::SEFLG_DEFAULTEPH,
        int   $sidMode = Catalog::SE_SIDM_FAGAN_BRADLEY
    ): CalculationResult
    {
        return CalculationResult::fromArray(self::calc($tjdEt, $ipl, $iflag, $sidMode));
    }

    public static function calcUtResult(
        float $tjdUt,
        int   $ipl,
        int   $iflag = Catalog::SEFLG_DEFAULTEPH,
        int   $sidMode = Catalog::SE_SIDM_FAGAN_BRADLEY
    ): CalculationResult
    {
        return CalculationResult::fromArray(self::calcUt($tjdUt, $ipl, $iflag, $sidMode));
    }

    public static function calcApparentResult(
        float $tjdEt,
        int   $ipl,
        bool  $withNutation = true,
        bool  $withDeflection = true,
        bool  $withAberration = true
    ): CalculationResult
    {
        return CalculationResult::fromArray(
            self::calcApparent($tjdEt, $ipl, $withNutation, $withDeflection, $withAberration)
        );
    }

    public static function calcApparentUtResult(
        float $tjdEt,
        int   $ipl,
        bool  $withNutation = true,
        bool  $withDeflection = true,
        bool  $withAberration = true
    ): CalculationResult
    {
        return CalculationResult::fromArray(
            self::calcApparentUt($tjdEt, $ipl, $withNutation, $withDeflection, $withAberration)
        );
    }

    public static function calcApparentFlagsResult(
        float $tjdEt,
        int   $ipl,
        int   $iflag,
        int   $sidMode = Catalog::SE_SIDM_FAGAN_BRADLEY
    ): CalculationResult
    {
        return CalculationResult::fromArray(self::calcApparentFlags($tjdEt, $ipl, $iflag, $sidMode));
    }

    public static function calcApparentFlagsUtResult(
        float $tjdUt,
        int   $ipl,
        int   $iflag,
        int   $sidMode = Catalog::SE_SIDM_FAGAN_BRADLEY
    ): CalculationResult
    {
        return CalculationResult::fromArray(self::calcApparentFlagsUt($tjdUt, $ipl, $iflag, $sidMode));
    }

    /**
     * Apparent swe_calc() variant for SE_SIDM_USER without global sidereal state.
     *
     * @return array{rc:int, xx:array<int, float>, error:string}
     */
    public static function calcApparentUserFlags(
        float  $tjdEt,
        int    $ipl,
        int    $iflag,
        int    $sidMode,
        float  $t0,
        float  $ayanT0,
        string $model = Precession::MODEL_IAU_1976
    ): array
    {
        $iflag = Catalog::normalizeEphemerisFlags($iflag);
        $iflag = self::normalizeCalculationFlags($iflag);
        $baseFlags = $iflag
            & ~Catalog::SEFLG_SIDEREAL
            & ~Catalog::SEFLG_RADIANS
            & ~Catalog::SEFLG_EQUATORIAL
            & ~Catalog::SEFLG_XYZ
            & ~Catalog::SEFLG_J2000;

        $result = self::calcApparentFlags($tjdEt, $ipl, $baseFlags);

        if ($result['rc'] === SwissDate::ERR) {
            return $result;
        }

        $xx = $result['xx'];
        $xx = self::applyUserSidereal($xx, $tjdEt, $iflag, $sidMode, $t0, $ayanT0, $model);
        $xx = self::finalizePosition($xx, $tjdEt, $iflag, Catalog::wantsSpeed($iflag));

        return [
            'rc' => $iflag,
            'xx' => $xx,
            'error' => '',
        ];
    }

    /**
     * Apparent swe_calc_ut() variant for SE_SIDM_USER without global sidereal state.
     *
     * @return array{rc:int, xx:array<int, float>, error:string}
     */
    public static function calcApparentUserFlagsUt(
        float  $tjdUt,
        int    $ipl,
        int    $iflag,
        int    $sidMode,
        float  $t0,
        float  $ayanT0,
        string $model = Precession::MODEL_IAU_1976
    ): array
    {
        return self::calcApparentUserFlags(
            $tjdUt + DeltaT::deltatEx($tjdUt, $iflag),
            $ipl,
            $iflag,
            $sidMode,
            $t0,
            $ayanT0,
            $model
        );
    }

    public static function calcApparentUserFlagsResult(
        float  $tjdEt,
        int    $ipl,
        int    $iflag,
        int    $sidMode,
        float  $t0,
        float  $ayanT0,
        string $model = Precession::MODEL_IAU_1976
    ): CalculationResult
    {
        return CalculationResult::fromArray(
            self::calcApparentUserFlags($tjdEt, $ipl, $iflag, $sidMode, $t0, $ayanT0, $model)
        );
    }

    public static function calcApparentUserFlagsUtResult(
        float  $tjdUt,
        int    $ipl,
        int    $iflag,
        int    $sidMode,
        float  $t0,
        float  $ayanT0,
        string $model = Precession::MODEL_IAU_1976
    ): CalculationResult
    {
        return CalculationResult::fromArray(
            self::calcApparentUserFlagsUt($tjdUt, $ipl, $iflag, $sidMode, $t0, $ayanT0, $model)
        );
    }


    /**
     * swe_calc() variant for SE_SIDM_USER without global sidereal state.
     *
     * @return array{rc:int, xx:array<int, float>, error:string}
     */
    public static function calcUser(
        float  $tjdEt,
        int    $ipl,
        int    $iflag,
        int    $sidMode,
        float  $t0,
        float  $ayanT0,
        string $model = Precession::MODEL_IAU_1976
    ): array
    {
        $iflag = Catalog::normalizeEphemerisFlags($iflag);
        $iflag = self::normalizeCalculationFlags($iflag);
        $baseFlags = $iflag
            & ~Catalog::SEFLG_SIDEREAL
            & ~Catalog::SEFLG_RADIANS
            & ~Catalog::SEFLG_EQUATORIAL
            & ~Catalog::SEFLG_XYZ
            & ~Catalog::SEFLG_J2000;

        $result = self::calc($tjdEt, $ipl, $baseFlags);

        if ($result['rc'] === SwissDate::ERR) {
            return $result;
        }

        $xx = $result['xx'];
        $xx = self::applyUserSidereal($xx, $tjdEt, $iflag, $sidMode, $t0, $ayanT0, $model);
        $xx = self::finalizePosition($xx, $tjdEt, $iflag, Catalog::wantsSpeed($iflag));

        return [
            'rc' => $iflag,
            'xx' => $xx,
            'error' => '',
        ];
    }

    /**
     * swe_calc_ut() variant for SE_SIDM_USER without global sidereal state.
     *
     * @return array{rc:int, xx:array<int, float>, error:string}
     */
    public static function calcUserUt(
        float  $tjdUt,
        int    $ipl,
        int    $iflag,
        int    $sidMode,
        float  $t0,
        float  $ayanT0,
        string $model = Precession::MODEL_IAU_1976
    ): array
    {
        return self::calcUser(
            $tjdUt + DeltaT::deltatEx($tjdUt, $iflag),
            $ipl,
            $iflag,
            $sidMode,
            $t0,
            $ayanT0,
            $model
        );
    }

    public static function calcUserResult(
        float  $tjdEt,
        int    $ipl,
        int    $iflag,
        int    $sidMode,
        float  $t0,
        float  $ayanT0,
        string $model = Precession::MODEL_IAU_1976
    ): CalculationResult
    {
        return CalculationResult::fromArray(
            self::calcUser($tjdEt, $ipl, $iflag, $sidMode, $t0, $ayanT0, $model)
        );
    }

    public static function calcUserUtResult(
        float  $tjdUt,
        int    $ipl,
        int    $iflag,
        int    $sidMode,
        float  $t0,
        float  $ayanT0,
        string $model = Precession::MODEL_IAU_1976
    ): CalculationResult
    {
        return CalculationResult::fromArray(
            self::calcUserUt($tjdUt, $ipl, $iflag, $sidMode, $t0, $ayanT0, $model)
        );
    }

    /**
     * swe_calc() variant with an explicit observer object.
     *
     * Topocentric corrections are not applied yet; the observer is accepted
     * to keep the API stateless and ready for SEFLG_TOPOCTR support.
     *
     * @return array{rc:int, xx:array<int, float>, error:string}
     */
    public static function calcTopo(
        float    $tjdEt,
        int      $ipl,
        int      $iflag,
        Observer $observer,
        int      $sidMode = Catalog::SE_SIDM_FAGAN_BRADLEY
    ): array
    {
        $iflag = Catalog::normalizeEphemerisFlags($iflag | Catalog::SEFLG_TOPOCTR);
        $iflag = self::normalizeCalculationFlags($iflag);
        $baseFlags = $iflag
            & ~Catalog::SEFLG_TOPOCTR
            & ~Catalog::SEFLG_SIDEREAL
            & ~Catalog::SEFLG_RADIANS
            & ~Catalog::SEFLG_EQUATORIAL
            & ~Catalog::SEFLG_XYZ
            & ~Catalog::SEFLG_J2000;

        $result = self::calc($tjdEt, $ipl, $baseFlags);

        if ($result['rc'] === SwissDate::ERR) {
            return $result;
        }

        $xx = self::applyTopocentricCorrection(
            $result['xx'],
            $tjdEt,
            $iflag,
            $observer
        );

        $xx = self::applySidereal($xx, $tjdEt, $iflag, $sidMode);
        $xx = self::finalizePosition($xx, $tjdEt, $iflag, Catalog::wantsSpeed($iflag));

        return [
            'rc' => $iflag,
            'xx' => $xx,
            'error' => '',
        ];
    }

    /**
     * @return array{rc:int, xx:array<int, float>, error:string}
     */
    public static function calcTopoUt(
        float    $tjdUt,
        int      $ipl,
        int      $iflag,
        Observer $observer,
        int      $sidMode = Catalog::SE_SIDM_FAGAN_BRADLEY
    ): array
    {
        return self::calcTopo(
            $tjdUt + DeltaT::deltatEx($tjdUt, $iflag | Catalog::SEFLG_TOPOCTR),
            $ipl,
            $iflag,
            $observer,
            $sidMode
        );
    }

    public static function calcTopoResult(
        float    $tjdEt,
        int      $ipl,
        int      $iflag,
        Observer $observer,
        int      $sidMode = Catalog::SE_SIDM_FAGAN_BRADLEY
    ): CalculationResult
    {
        return CalculationResult::fromArray(
            self::calcTopo($tjdEt, $ipl, $iflag, $observer, $sidMode)
        );
    }

    public static function calcTopoUtResult(
        float    $tjdUt,
        int      $ipl,
        int      $iflag,
        Observer $observer,
        int      $sidMode = Catalog::SE_SIDM_FAGAN_BRADLEY
    ): CalculationResult
    {
        return CalculationResult::fromArray(
            self::calcTopoUt($tjdUt, $ipl, $iflag, $observer, $sidMode)
        );
    }

    /**
     * Topocentric swe_calc() variant for SE_SIDM_USER without global sidereal state.
     *
     * @return array{rc:int, xx:array<int, float>, error:string}
     */
    public static function calcUserTopo(
        float    $tjdEt,
        int      $ipl,
        int      $iflag,
        int      $sidMode,
        float    $t0,
        float    $ayanT0,
        Observer $observer,
        string   $model = Precession::MODEL_IAU_1976
    ): array
    {
        $iflag = Catalog::normalizeEphemerisFlags($iflag | Catalog::SEFLG_TOPOCTR);
        $iflag = self::normalizeCalculationFlags($iflag);
        $baseFlags = $iflag
            & ~Catalog::SEFLG_SIDEREAL
            & ~Catalog::SEFLG_RADIANS
            & ~Catalog::SEFLG_EQUATORIAL
            & ~Catalog::SEFLG_XYZ
            & ~Catalog::SEFLG_J2000;

        $result = self::calcTopo($tjdEt, $ipl, $baseFlags, $observer);

        if ($result['rc'] === SwissDate::ERR) {
            return $result;
        }

        $xx = $result['xx'];
        $xx = self::applyUserSidereal($xx, $tjdEt, $iflag, $sidMode, $t0, $ayanT0, $model);
        $xx = self::finalizePosition($xx, $tjdEt, $iflag, Catalog::wantsSpeed($iflag));

        return [
            'rc' => $iflag,
            'xx' => $xx,
            'error' => '',
        ];
    }

    /**
     * @return array{rc:int, xx:array<int, float>, error:string}
     */
    public static function calcUserTopoUt(
        float    $tjdUt,
        int      $ipl,
        int      $iflag,
        int      $sidMode,
        float    $t0,
        float    $ayanT0,
        Observer $observer,
        string   $model = Precession::MODEL_IAU_1976
    ): array
    {
        return self::calcUserTopo(
            $tjdUt + DeltaT::deltatEx($tjdUt, $iflag | Catalog::SEFLG_TOPOCTR),
            $ipl,
            $iflag,
            $sidMode,
            $t0,
            $ayanT0,
            $observer,
            $model
        );
    }

    public static function calcUserTopoResult(
        float    $tjdEt,
        int      $ipl,
        int      $iflag,
        int      $sidMode,
        float    $t0,
        float    $ayanT0,
        Observer $observer,
        string   $model = Precession::MODEL_IAU_1976
    ): CalculationResult
    {
        return CalculationResult::fromArray(
            self::calcUserTopo($tjdEt, $ipl, $iflag, $sidMode, $t0, $ayanT0, $observer, $model)
        );
    }

    public static function calcUserTopoUtResult(
        float    $tjdUt,
        int      $ipl,
        int      $iflag,
        int      $sidMode,
        float    $t0,
        float    $ayanT0,
        Observer $observer,
        string   $model = Precession::MODEL_IAU_1976
    ): CalculationResult
    {
        return CalculationResult::fromArray(
            self::calcUserTopoUt($tjdUt, $ipl, $iflag, $sidMode, $t0, $ayanT0, $observer, $model)
        );
    }

    /**
     * Swiss Ephemeris compatible subset of swe_pheno().
     *
     * @return array{rc:int, attr:array<int, float>, error:string}
     */
    public static function pheno(
        float $tjdEt,
        int   $ipl,
        int   $iflag = Catalog::SEFLG_DEFAULTEPH
    ): array
    {
        return Phenomena::pheno($tjdEt, $ipl, $iflag);
    }

    /**
     * Swiss Ephemeris compatible subset of swe_pheno_ut().
     *
     * @return array{rc:int, attr:array<int, float>, error:string}
     */
    public static function phenoUt(
        float $tjdUt,
        int   $ipl,
        int   $iflag = Catalog::SEFLG_DEFAULTEPH
    ): array
    {
        return Phenomena::phenoUt($tjdUt, $ipl, $iflag);
    }

    public static function phenoResult(
        float $tjdEt,
        int   $ipl,
        int   $iflag = Catalog::SEFLG_DEFAULTEPH
    ): PhenomenaResult
    {
        return Phenomena::phenoResult($tjdEt, $ipl, $iflag);
    }

    public static function phenoUtResult(
        float $tjdUt,
        int   $ipl,
        int   $iflag = Catalog::SEFLG_DEFAULTEPH
    ): PhenomenaResult
    {
        return Phenomena::phenoUtResult($tjdUt, $ipl, $iflag);
    }

    public static function solcross(
        float $longitude,
        float $tjdEt,
        int   $iflag = Catalog::SEFLG_DEFAULTEPH
    ): float
    {
        return Crossing::solcross($longitude, $tjdEt, $iflag);
    }

    public static function solcrossUt(
        float $longitude,
        float $tjdUt,
        int   $iflag = Catalog::SEFLG_DEFAULTEPH
    ): float
    {
        return Crossing::solcrossUt($longitude, $tjdUt, $iflag);
    }

    public static function mooncross(
        float $longitude,
        float $tjdEt,
        int   $iflag = Catalog::SEFLG_DEFAULTEPH
    ): float
    {
        return Crossing::mooncross($longitude, $tjdEt, $iflag);
    }

    public static function mooncrossUt(
        float $longitude,
        float $tjdUt,
        int   $iflag = Catalog::SEFLG_DEFAULTEPH
    ): float
    {
        return Crossing::mooncrossUt($longitude, $tjdUt, $iflag);
    }

    /**
     * @return array{tjd:float, longitude:float, latitude:float}
     */
    public static function mooncrossNode(
        float $tjdEt,
        int   $iflag = Catalog::SEFLG_DEFAULTEPH
    ): array
    {
        return Crossing::mooncrossNode($tjdEt, $iflag);
    }

    /**
     * @return array{tjd:float, longitude:float, latitude:float}
     */
    public static function mooncrossNodeUt(
        float $tjdUt,
        int   $iflag = Catalog::SEFLG_DEFAULTEPH
    ): array
    {
        return Crossing::mooncrossNodeUt($tjdUt, $iflag);
    }

    /**
     * @return array{rc:int, tjd:float, error:string}
     */
    public static function helioCross(
        int   $ipl,
        float $longitude,
        float $tjdEt,
        int   $iflag = Catalog::SEFLG_DEFAULTEPH,
        int   $direction = 1
    ): array
    {
        return Crossing::helioCross($ipl, $longitude, $tjdEt, $iflag, $direction);
    }

    /**
     * @return array{rc:int, tjd:float, error:string}
     */
    public static function helioCrossUt(
        int   $ipl,
        float $longitude,
        float $tjdUt,
        int   $iflag = Catalog::SEFLG_DEFAULTEPH,
        int   $direction = 1
    ): array
    {
        return Crossing::helioCrossUt($ipl, $longitude, $tjdUt, $iflag, $direction);
    }

    public static function helioCrossResult(
        int   $ipl,
        float $longitude,
        float $tjdEt,
        int   $iflag = Catalog::SEFLG_DEFAULTEPH,
        int   $direction = 1
    ): CrossingResult
    {
        return Crossing::helioCrossResult($ipl, $longitude, $tjdEt, $iflag, $direction);
    }

    public static function helioCrossUtResult(
        int   $ipl,
        float $longitude,
        float $tjdUt,
        int   $iflag = Catalog::SEFLG_DEFAULTEPH,
        int   $direction = 1
    ): CrossingResult
    {
        return Crossing::helioCrossUtResult($ipl, $longitude, $tjdUt, $iflag, $direction);
    }

    /**
     * Swiss Ephemeris compatible subset of swe_nod_aps().
     *
     * @return array{
     *     rc:int,
     *     ascNode:array<int, float>,
     *     descNode:array<int, float>,
     *     perihelion:array<int, float>,
     *     aphelion:array<int, float>,
     *     error:string
     * }
     */
    public static function nodAps(
        float $tjdEt,
        int   $ipl,
        int   $iflag = Catalog::SEFLG_DEFAULTEPH,
        int   $method = Catalog::SE_NODBIT_MEAN
    ): array
    {
        return NodesApsides::nodAps($tjdEt, $ipl, $iflag, $method);
    }

    /**
     * Swiss Ephemeris compatible subset of swe_nod_aps_ut().
     *
     * @return array{
     *     rc:int,
     *     ascNode:array<int, float>,
     *     descNode:array<int, float>,
     *     perihelion:array<int, float>,
     *     aphelion:array<int, float>,
     *     error:string
     * }
     */
    public static function nodApsUt(
        float $tjdUt,
        int   $ipl,
        int   $iflag = Catalog::SEFLG_DEFAULTEPH,
        int   $method = Catalog::SE_NODBIT_MEAN
    ): array
    {
        return NodesApsides::nodApsUt($tjdUt, $ipl, $iflag, $method);
    }

    public static function nodApsResult(
        float $tjdEt,
        int   $ipl,
        int   $iflag = Catalog::SEFLG_DEFAULTEPH,
        int   $method = Catalog::SE_NODBIT_MEAN
    ): NodesApsidesResult
    {
        return NodesApsides::nodApsResult($tjdEt, $ipl, $iflag, $method);
    }

    public static function nodApsUtResult(
        float $tjdUt,
        int   $ipl,
        int   $iflag = Catalog::SEFLG_DEFAULTEPH,
        int   $method = Catalog::SE_NODBIT_MEAN
    ): NodesApsidesResult
    {
        return NodesApsides::nodApsUtResult($tjdUt, $iflag, $iflag, $method);
    }

    /**
     * Swiss Ephemeris compatible subset of swe_get_orbital_elements().
     *
     * @return array{rc:int, dret:array<int, float>, error:string}
     */
    public static function getOrbitalElements(
        float $tjdEt,
        int   $ipl,
        int   $iflag = Catalog::SEFLG_DEFAULTEPH
    ): array
    {
        return OrbitalElements::get($tjdEt, $ipl, $iflag);
    }

    /**
     * @return array{rc:int, dret:array<int, float>, error:string}
     */
    public static function getOrbitalElementsUt(
        float $tjdUt,
        int   $ipl,
        int   $iflag = Catalog::SEFLG_DEFAULTEPH
    ): array
    {
        return OrbitalElements::getUt($tjdUt, $ipl, $iflag);
    }

    public static function getOrbitalElementsResult(
        float $tjdEt,
        int   $ipl,
        int   $iflag = Catalog::SEFLG_DEFAULTEPH
    ): OrbitalElementsResult
    {
        return OrbitalElements::getResult($tjdEt, $ipl, $iflag);
    }

    public static function getOrbitalElementsUtResult(
        float $tjdUt,
        int   $ipl,
        int   $iflag = Catalog::SEFLG_DEFAULTEPH
    ): OrbitalElementsResult
    {
        return OrbitalElements::getUtResult($tjdUt, $ipl, $iflag);
    }

    /**
     * Swiss Ephemeris compatible subset of swe_orbit_max_min_true_distance().
     *
     * @return array{rc:int, max:float, min:float, true:float, error:string}
     */
    public static function orbitMaxMinTrueDistance(
        float $tjdEt,
        int   $ipl,
        int   $iflag = Catalog::SEFLG_DEFAULTEPH
    ): array
    {
        return OrbitalElements::maxMinTrueDistance($tjdEt, $ipl, $iflag);
    }

    /**
     * @return array{rc:int, max:float, min:float, true:float, error:string}
     */
    public static function orbitMaxMinTrueDistanceUt(
        float $tjdUt,
        int   $ipl,
        int   $iflag = Catalog::SEFLG_DEFAULTEPH
    ): array
    {
        return OrbitalElements::maxMinTrueDistanceUt($tjdUt, $ipl, $iflag);
    }

    public static function orbitMaxMinTrueDistanceResult(
        float $tjdEt,
        int   $ipl,
        int   $iflag = Catalog::SEFLG_DEFAULTEPH
    ): OrbitDistanceResult
    {
        return OrbitalElements::maxMinTrueDistanceResult($tjdEt, $ipl, $iflag);
    }

    public static function orbitMaxMinTrueDistanceUtResult(
        float $tjdUt,
        int   $ipl,
        int   $iflag = Catalog::SEFLG_DEFAULTEPH
    ): OrbitDistanceResult
    {
        return OrbitalElements::maxMinTrueDistanceUtResult($tjdUt, $ipl, $iflag);
    }

    /**
     * Swiss Ephemeris compatible subset of swe_fixstar().
     *
     * @return array{rc:int, xx:array<int, float>, star:string, error:string}
     */
    public static function fixstar(
        string $name,
        float  $tjdEt,
        int    $iflag = Catalog::SEFLG_DEFAULTEPH
    ): array
    {
        return FixedStars::fixstar($name, $tjdEt, $iflag);
    }

    /**
     * Swiss Ephemeris compatible subset of swe_fixstar_ut().
     *
     * @return array{rc:int, xx:array<int, float>, star:string, error:string}
     */
    public static function fixstarUt(
        string $name,
        float  $tjdUt,
        int    $iflag = Catalog::SEFLG_DEFAULTEPH
    ): array
    {
        return FixedStars::fixstarUt($name, $tjdUt, $iflag);
    }

    public static function fixstarResult(
        string $name,
        float  $tjdEt,
        int    $iflag = Catalog::SEFLG_DEFAULTEPH
    ): FixedStarResult
    {
        return FixedStars::fixstarResult($name, $tjdEt, $iflag);
    }

    public static function fixstarUtResult(
        string $name,
        float  $tjdUt,
        int    $iflag = Catalog::SEFLG_DEFAULTEPH
    ): FixedStarResult
    {
        return FixedStars::fixstarUtResult($name, $tjdUt, $iflag);
    }

    /**
     * Swiss Ephemeris compatible subset of swe_fixstar_mag().
     *
     * @return array{rc:int, mag:float, star:string, error:string}
     */
    public static function fixstarMagnitude(string $name): array
    {
        return FixedStars::fixstarMagnitude($name);
    }

    public static function fixstarMagnitudeResult(string $name): FixedStarMagnitudeResult
    {
        return FixedStars::fixstarMagnitudeResult($name);
    }

    /**
     * @return array<int, string>
     */
    public static function fixedStarNames(): array
    {
        return FixedStars::names();
    }

    public static function fixedStarExists(string $name): bool
    {
        return FixedStars::exists($name);
    }

    /**
     * @param array<int, float> $position
     * @return array<int, float>
     */
    private static function applySidereal(
        array $position,
        float $tjdEt,
        int   $iflag,
        int   $sidMode
    ): array
    {
        if (self::isZeroVector($position)) {
            return $position;
        }

        if (!Catalog::wantsSidereal($iflag)) {
            return $position;
        }

        $withNutation = !Catalog::hasFlag($iflag, Catalog::SEFLG_NONUT);

        if (Catalog::wantsSpeed($iflag)) {
            return Ayanamsa::siderealPosition($position, $tjdEt, $sidMode, $withNutation);
        }

        $converted = Ayanamsa::siderealPosition(
            [$position[0], $position[1], $position[2]],
            $tjdEt,
            $sidMode,
            $withNutation
        );

        $position[0] = $converted[0];
        $position[1] = $converted[1];
        $position[2] = $converted[2];

        return $position;
    }

    /**
     * @param array<int, float> $position
     * @return array<int, float>
     */
    private static function applyUserSidereal(
        array  $position,
        float  $tjdEt,
        int    $iflag,
        int    $sidMode,
        float  $t0,
        float  $ayanT0,
        string $model
    ): array
    {
        if (self::isZeroVector($position)) {
            return $position;
        }

        if (!Catalog::wantsSidereal($iflag)) {
            return $position;
        }

        $withNutation = !Catalog::hasFlag($iflag, Catalog::SEFLG_NONUT);

        if (Catalog::wantsSpeed($iflag)) {
            return Ayanamsa::userSiderealPosition(
                $position,
                $tjdEt,
                $sidMode,
                $t0,
                $ayanT0,
                $withNutation,
                $model
            );
        }

        $converted = Ayanamsa::userSiderealPosition(
            [$position[0], $position[1], $position[2]],
            $tjdEt,
            $sidMode,
            $t0,
            $ayanT0,
            $withNutation,
            $model
        );

        $position[0] = $converted[0];
        $position[1] = $converted[1];
        $position[2] = $converted[2];

        return $position;
    }

    private static function normalizeCalculationFlags(int $iflag): int
    {
        return $iflag & ~Catalog::SEFLG_CENTER_BODY;
    }

    private static function wantsCenterRelative(int $iflag): bool
    {
        return Catalog::hasFlag($iflag, Catalog::SEFLG_HELCTR)
            || Catalog::hasFlag($iflag, Catalog::SEFLG_BARYCTR);
    }

    /**
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}|null
     */
    private static function basePosition(float $tjdEt, int $ipl, int $iflag): ?array
    {
        if ($ipl === Catalog::SE_SUN && self::wantsCenterRelative($iflag)) {
            return [0.0, 0.0, 0.0, 0.0, 0.0, 0.0];
        }

        if ($ipl === Catalog::SE_SUN && !self::wantsCenterRelative($iflag)) {
            return SolarPosition::position($tjdEt);
        }

        if ($ipl === Catalog::SE_MOON && !self::wantsCenterRelative($iflag)) {
            return MoshierMoon::geocentric($tjdEt);
        }

        if ($ipl === Catalog::SE_MOON && self::wantsCenterRelative($iflag)) {
            return null;
        }

        if ($ipl === Catalog::SE_MEAN_NODE && !self::wantsCenterRelative($iflag)) {
            return MeanNode::geocentric($tjdEt);
        }

        if ($ipl == Catalog::SE_MEAN_NODE && self::wantsCenterRelative($iflag)) {
            return null;
        }

        if ($ipl === Catalog::SE_TRUE_NODE && !self::wantsCenterRelative($iflag)) {
            return TrueNode::geocentric($tjdEt);
        }

        if ($ipl === Catalog::SE_TRUE_NODE && self::wantsCenterRelative($iflag)) {
            return null;
        }

        if ($ipl === Catalog::SE_OSCU_APOG && !self::wantsCenterRelative($iflag)) {
            return OsculatingApogee::geocentric($tjdEt);
        }

        if ($ipl === Catalog::SE_OSCU_APOG && self::wantsCenterRelative($iflag)) {
            return null;
        }

        if ($ipl === Catalog::SE_MEAN_APOG && !self::wantsCenterRelative($iflag)) {
            return MeanApogee::geocentric($tjdEt);
        }

        if ($ipl == Catalog::SE_MEAN_APOG && self::wantsCenterRelative($iflag)) {
            return null;
        }

        if ($ipl === Catalog::SE_EARTH && !self::wantsCenterRelative($iflag)) {
            return [0.0, 0.0, 0.0, 0.0, 0.0, 0.0];
        }

        if ($ipl === Catalog::SE_EARTH && self::wantsCenterRelative($iflag)) {
            return EarthPosition::heliocentric($tjdEt);
        }

        if (PlanetPosition::isSupported($ipl)) {
            if (self::wantsCenterRelative($iflag)) {
                return PlanetPosition::heliocentric($ipl, $tjdEt);
            }

            if (Catalog::hasFlag($iflag, Catalog::SEFLG_TRUEPOS)) {
                return PlanetPosition::geocentric($ipl, $tjdEt);
            }

            $position = PlanetPosition::geocentricLightTime($ipl, $tjdEt);

            if (
                Catalog::hasFlag($iflag, Catalog::SEFLG_NOGDEFL)
                && Catalog::hasFlag($iflag, Catalog::SEFLG_NOABERR)
            ) {
                return $position;
            }

            $earth = self::toCartesian(EarthPosition::heliocentric($tjdEt), true);
            $cartesian = self::toCartesian($position, true);

            if (!Catalog::hasFlag($iflag, Catalog::SEFLG_NOGDEFL)) {
                $cartesian = LightDeflection::solar($cartesian, $earth, Catalog::wantsSpeed($iflag));
            }

            if (!Catalog::hasFlag($iflag, Catalog::SEFLG_NOABERR)) {
                $cartesian = Aberration::annual($cartesian, $earth, Catalog::wantsSpeed($iflag));
            }

            return self::fromCartesian($cartesian);
        }

        return null;
    }

    /**
     * @param array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float} $position
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    private static function applyAnnualAberration(array $position, float $tjdEt, bool $withSpeed): array
    {
        $cartesian = self::toCartesian($position, true);
        $earth = self::toCartesian(EarthPosition::heliocentric($tjdEt), true);

        $cartesian = Aberration::annual($cartesian, $earth, $withSpeed);
        $polar = Coordinates::cartpolSp($cartesian);

        return [
            rad2deg($polar[0]),
            rad2deg($polar[1]),
            $polar[2],
            rad2deg($polar[3]),
            rad2deg($polar[4]),
            $polar[5],
        ];
    }

    /**
     * @param array<int, float> $position
     * @return array<int, float>
     */
    private static function applyTopocentricCorrection(
        array    $position,
        float    $tjdEt,
        int      $iflag,
        Observer $observer
    ): array
    {
        $cartesian = $position;
        $cartesian[0] = deg2rad($cartesian[0]);
        $cartesian[1] = deg2rad($cartesian[1]);
        $cartesian[3] = deg2rad($cartesian[3]);
        $cartesian[4] = deg2rad($cartesian[4]);
        $cartesian = Coordinates::polcartSp($cartesian);

        $observerVector = $observer->geocentricVector(
            $tjdEt,
            !Catalog::hasFlag($iflag, Catalog::SEFLG_NONUT)
        );

        for ($i = 0; $i <= 5; $i++) {
            $cartesian[$i] -= $observerVector[$i];
        }

        $polar = Coordinates::cartpolSp($cartesian);

        return [
            rad2deg($polar[0]),
            rad2deg($polar[1]),
            $polar[2],
            rad2deg($polar[3]),
            rad2deg($polar[4]),
            $polar[5],
        ];
    }

    /**
     * @param array<int, float> $position
     * @return array<int, float>
     */
    private static function finalizePosition(array $position, float $tjdEt, int $iflag, bool $withSpeed): array
    {
        $xx = $position;

        if (Catalog::hasFlag($iflag, Catalog::SEFLG_J2000)) {
            $xx = self::toJ2000($xx, $tjdEt);
        }

        if (!$withSpeed) {
            $xx[3] = 0.0;
            $xx[4] = 0.0;
            $xx[5] = 0.0;
        }

        if (Catalog::hasFlag($iflag, Catalog::SEFLG_EQUATORIAL)) {
            $xx = self::toEquatorial($xx, $tjdEt, $iflag);
        }

        if (Catalog::hasFlag($iflag, Catalog::SEFLG_XYZ)) {
            $xx = self::toCartesian($xx, $withSpeed);
        }

        if (Catalog::hasFlag($iflag, Catalog::SEFLG_RADIANS) && !Catalog::hasFlag($iflag, Catalog::SEFLG_XYZ)) {
            $xx[0] = deg2rad($xx[0]);
            $xx[1] = deg2rad($xx[1]);
            $xx[3] = deg2rad($xx[3]);
            $xx[4] = deg2rad($xx[4]);
        }

        return $xx;
    }

    /**
     * @param array<int, float> $position
     * @return array<int, float>
     */
    private static function toEquatorial(array $position, float $tjdEt, int $iflag): array
    {
        $eps = SiderealTime::meanObliquity($tjdEt);

        if (!Catalog::hasFlag($iflag, Catalog::SEFLG_NONUT)) {
            $nutation = SiderealTime::nutationApprox($tjdEt);
            $eps += $nutation['deps'];
        }

        return Coordinates::cotransSp($position, -$eps);
    }

    /**
     * @param array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float} $position
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    private static function toCartesian(array $position, bool $withSpeed): array
    {
        $position[0] = deg2rad($position[0]);
        $position[1] = deg2rad($position[1]);
        $position[3] = $withSpeed ? deg2rad($position[3]) : 0.0;
        $position[4] = $withSpeed ? deg2rad($position[4]) : 0.0;
        $position[5] = $withSpeed ? $position[5] : 0.0;

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

    /**
     * @param array<int, float> $position
     * @return array<int, float>
     */
    private static function toJ2000(array $position, float $tjdEt): array
    {
        $polar = $position;
        $polar[0] = deg2rad($polar[0]);
        $polar[1] = deg2rad($polar[1]);
        $polar[3] = deg2rad($polar[3]);
        $polar[4] = deg2rad($polar[4]);

        $cartesian = Coordinates::polcartSp($polar);

        $precessedPosition = Precession::precess(
            [$cartesian[0], $cartesian[1], $cartesian[2]],
            $tjdEt,
            Precession::DIRECTION_TO_J2000,
            Precession::MODEL_IAU_1976
        );

        $precessedSpeed = Precession::precess(
            [$cartesian[3], $cartesian[4], $cartesian[5]],
            $tjdEt,
            Precession::DIRECTION_TO_J2000,
            Precession::MODEL_IAU_1976
        );

        $result = Coordinates::cartpolSp([
            $precessedPosition[0],
            $precessedPosition[1],
            $precessedPosition[2],
            $precessedSpeed[0],
            $precessedSpeed[1],
            $precessedSpeed[2],
        ]);

        return [
            rad2deg($result[0]),
            rad2deg($result[1]),
            $result[2],
            rad2deg($result[3]),
            rad2deg($result[4]),
            $result[5],
        ];
    }

    /**
     * @param array<int, float> $position
     */
    private static function isZeroVector(array $position): bool
    {
        for ($i = 0; $i <= 5; $i++) {
            if (($position[$i] ?? 0.0) != 0.0) {
                return false;
            }
        }

        return true;
    }

    private static function usesMoshierPlanetRange(int $ipl): bool
    {
        return $ipl === Catalog::SE_SUN
            || $ipl === Catalog::SE_EARTH
            || PlanetPosition::isSupported($ipl);
    }

    private static function usesMoshierMoonRange(int $ipl): bool
    {
        return $ipl === Catalog::SE_MOON
            || $ipl === Catalog::SE_MEAN_NODE
            || $ipl === Catalog::SE_MEAN_APOG
            || $ipl === Catalog::SE_TRUE_NODE
            || $ipl === Catalog::SE_OSCU_APOG;
    }
}