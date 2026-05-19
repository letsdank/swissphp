<?php

declare(strict_types=1);

namespace SwissEph;

use InvalidArgumentException;

final class Houses
{
    public const HSYS_EQUAL = 'E';
    public const HSYS_EQUAL_ASC = 'A';
    public const HSYS_EQUAL_MC = 'D';
    public const HSYS_EQUAL_ARIES = 'N';
    public const HSYS_WHOLE_SIGN = 'W';
    public const HSYS_PLACIDUS = 'P';
    public const HSYS_PORPHYRY = 'O';
    public const HSYS_MORINUS = 'M';
    public const HSYS_REGIOMONTANUS = 'R';
    public const HSYS_SRIPATI = 'S';
    public const HSYS_TOPOCENTRIC = 'T';
    public const HSYS_CAMPANUS = 'C';
    public const HSYS_ALCABITIUS = 'B';
    public const HSYS_KOCH = 'K';
    public const HSYS_PULLEN_SD = 'L';
    public const HSYS_PULLEN_SR = 'Q';
    public const HSYS_CARTER = 'F';
    public const HSYS_MERIDIAN = 'X';
    public const HSYS_SAVARD_A = 'J';
    public const HSYS_KRUSINSKI = 'U';
    public const HSYS_HORIZON = 'H';
    public const HSYS_GAUQUELIN = 'G';
    public const HSYS_APC = 'Y';

    private const VERY_SMALL = 1e-10;
    private const VERY_SMALL_PLAC_ITER = 1.0 / 360000.0;
    private const PLACIDUS_MAX_ITERATIONS = 100;
    private const MILLIARCSEC = 1.0 / 3600000.0;

    public static function houseName(string|int $hsys): string
    {
        return match (self::houseSystem($hsys)) {
            'A' => 'equal',
            'B' => 'Alcabitius',
            'C' => 'Campanus',
            'D' => 'equal (MC)',
            'E' => 'equal',
            'F' => 'Carter poli-equ.',
            'G' => 'Gauquelin sectors',
            'H' => 'horizon/azimut',
            'I' => 'Sunshine',
            'i' => 'Sunshine/alt.',
            'J' => 'Savard-A',
            'K' => 'Koch',
            'L' => 'Pullen SD',
            'M' => 'Morinus',
            'N' => 'equal/1=Aries',
            'O' => 'Porphyry',
            'Q' => 'Pullen SR',
            'R' => 'Regiomontanus',
            'S' => 'Sripati',
            'T' => 'Polich/Page',
            'U' => 'Krusinski-Pisa-Goelzer',
            'V' => 'equal/Vehlow',
            'W' => 'equal/ whole sign',
            'X' => 'axial rotation system/Meridian houses',
            'Y' => 'APC houses',
            default => 'Placidus',
        };
    }

    /**
     * Equal-house cusp calculation when ASC and MC are already known.
     *
     * @return array<int, float> 1-based cusps, indexes 1..12
     */
    public static function equalCusps(float $asc, float $mc, string|int $hsys = self::HSYS_EQUAL): array
    {
        return match (self::houseSystem($hsys)) {
            'A', 'E' => self::equalFromAscendant($asc, $mc),
            'D' => self::equalFromMc($asc, $mc),
            'N' => self::equalFromAries(),
            'V' => self::equalVehlow($asc, $mc),
            'W' => self::wholeSignFromAscendant($asc, $mc),
            default => throw new InvalidArgumentException('House system is not an equal-house system.'),
        };
    }

    private static function houseSystem(string|int $hsys): string
    {
        if (is_int($hsys)) {
            $hsys = chr($hsys & 0xff);
        }

        if ($hsys === '') {
            return self::HSYS_PLACIDUS;
        }

        $h = $hsys[0];

        return $h === 'i' ? $h : strtoupper($h);
    }

    /**
     * @return array<int, float>
     */
    private static function equalFromAscendant(float $asc, float $mc): array
    {
        $asc = Angle::degnorm($asc);
        $mc = Angle::degnorm($mc);

        if (Angle::difdeg2n($asc, $mc) < 0.0) {
            $asc = Angle::degnorm($asc + 180.0);
        }

        return self::cuspsFromStart($asc);
    }

    /**
     * @return array<int, float>
     */
    private static function equalFromMc(float $asc, float $mc): array
    {
        $asc = Angle::degnorm($asc);
        $mc = Angle::degnorm($mc);

        if (Angle::difdeg2n($asc, $mc) < 0.0) {
            $asc = Angle::degnorm($asc + 180.0);
        }

        $cusps = [];

        $cusps[10] = $mc;
        $cusps[11] = Angle::degnorm($mc + 30.0);
        $cusps[12] = Angle::degnorm($mc + 60.0);

        for ($i = 1; $i <= 9; $i++) {
            $cusps[$i] = Angle::degnorm($mc + ($i + 2) * 30.0);
        }

        ksort($cusps);

        return $cusps;
    }

    /**
     * @return array<int, float>
     */
    private static function equalFromAries(): array
    {
        return self::cuspsFromStart(0.0);
    }

    /**
     * @return array<int, float>
     */
    private static function cuspsFromStart(float $start): array
    {
        $cusps = [];

        for ($i = 1; $i <= 12; $i++) {
            $cusps[$i] = Angle::degnorm($start + ($i - 1) * 30.0);
        }

        return $cusps;
    }

    /**
     * Swiss Ephemeris compatible subset of swe_houses().
     *
     * The current implementation uses the short nutation approximation from SiderealTime,
     * so exact microdegree parity with swe_houses() requires porting the full nutation model.
     *
     * @return array{cusps:array<int, float>, ascmc:array<int,float>}
     */
    public static function houses(
        float      $tjdUt,
        float      $geolat,
        float      $geolon,
        string|int $hsys,
        ?float     $sunDeclination = null,
    ): array
    {
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, -1);
        $nutation = SiderealTime::nutationApprox($tjdEt);
        $eps = SiderealTime::meanObliquity($tjdEt) + $nutation['deps'];

        $armc = Angle::degnorm(
            SiderealTime::sidtime0($tjdUt, $eps, $nutation['dpsi']) * 15.0 + $geolon
        );

        return self::housesArmc($armc, $geolat, $eps, $hsys, $sunDeclination);
    }

    public static function housesResult(
        float      $tjdUt,
        float      $geolat,
        float      $geolon,
        string|int $hsys,
        ?float     $sunDeclination = null
    ): HousesResult
    {
        return HousesResult::fromArray(self::houses($tjdUt, $geolat, $geolon, $hsys, $sunDeclination));
    }

    public static function siderealHouses(
        float      $tjdUt,
        float      $geolat,
        float      $geolon,
        string|int $hsys,
        int        $sidMode = Catalog::SE_SIDM_FAGAN_BRADLEY,
        bool       $withNutation = true,
        ?float     $sunDeclination = null
    ): array
    {
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, -1);
        $ayanamsa = Ayanamsa::ayanamsa($tjdEt, $sidMode, $withNutation);
        $hsys = self::houseSystem($hsys);

        $houses = self::houses(
            $tjdUt,
            $geolat,
            $geolon,
            $hsys === 'W' ? self::HSYS_EQUAL : $hsys,
            $sunDeclination
        );

        return self::applyAyanamsaToHouses($houses, $ayanamsa, $hsys);
    }

    public static function siderealHousesResult(
        float      $tjdUt,
        float      $geolat,
        float      $geolon,
        string|int $hsys,
        int        $sidMode = Catalog::SE_SIDM_FAGAN_BRADLEY,
        bool       $withNutation = true,
        ?float     $sunDeclination = null
    ): HousesResult
    {
        return HousesResult::fromArray(
            self::siderealHouses($tjdUt, $geolat, $geolon, $hsys, $sidMode, $withNutation, $sunDeclination)
        );
    }

    /**
     * Traditional sidereal houses with user-defined ayanamsa epoch.
     *
     * @return array{cusps:array<int, float>, ascmc:array<int, float>}
     */
    public static function customSiderealHouses(
        float      $tjdUt,
        float      $geolat,
        float      $geolon,
        string|int $hsys,
        float      $t0,
        float      $ayanT0,
        bool       $withNutation = true,
        bool       $t0IsUt = false,
        string     $model = Precession::MODEL_IAU_1976,
        ?float     $sunDeclination = null
    ): array
    {
        $ayanamsa = Ayanamsa::customAyanamsaUt($tjdUt, $t0, $ayanT0, $withNutation, $t0IsUt, $model);
        $hsys = self::houseSystem($hsys);

        $houses = self::houses(
            $tjdUt,
            $geolat,
            $geolon,
            $hsys === 'W' ? self::HSYS_EQUAL : $hsys,
            $sunDeclination
        );

        return self::applyAyanamsaToHouses($houses, $ayanamsa, $hsys);
    }

    public static function customSiderealHousesResult(
        float      $tjdUt,
        float      $geolat,
        float      $geolon,
        string|int $hsys,
        float      $t0,
        float      $ayanT0,
        bool       $withNutation = true,
        bool       $t0IsUt = false,
        string     $model = Precession::MODEL_IAU_1976,
        ?float     $sunDeclination = null
    ): HousesResult
    {
        return HousesResult::fromArray(
            self::customSiderealHouses(
                $tjdUt,
                $geolat,
                $geolon,
                $hsys,
                $t0,
                $ayanT0,
                $withNutation,
                $t0IsUt,
                $model,
                $sunDeclination
            )
        );
    }

    /**
     * Traditional sidereal houses with SE_SIDM_USER mode bits.
     *
     * @return array{cusps:array<int, float>, ascmc:array<int, float>}
     */
    public static function userSiderealHouses(
        float      $tjdUt,
        float      $geolat,
        float      $geolon,
        string|int $hsys,
        int        $sidMode,
        float      $t0,
        float      $ayanT0,
        bool       $withNutation = true,
        string     $model = Precession::MODEL_IAU_1976,
        ?float     $sunDeclination = null
    ): array
    {
        $ayanamsa = Ayanamsa::userAyanamsaUt($tjdUt, $sidMode, $t0, $ayanT0, $withNutation, $model);
        $hsys = self::houseSystem($hsys);

        $houses = self::houses(
            $tjdUt,
            $geolat,
            $geolon,
            $hsys === 'W' ? self::HSYS_EQUAL : $hsys,
            $sunDeclination
        );

        return self::applyAyanamsaToHouses($houses, $ayanamsa, $hsys);
    }

    public static function userSiderealHousesResult(
        float      $tjdUt,
        float      $geolat,
        float      $geolon,
        string|int $hsys,
        int        $sidMode,
        float      $t0,
        float      $ayanT0,
        bool       $withNutation = true,
        string     $model = Precession::MODEL_IAU_1976,
        ?float     $sunDeclination = null
    ): HousesResult
    {
        return HousesResult::fromArray(
            self::userSiderealHouses(
                $tjdUt,
                $geolat,
                $geolon,
                $hsys,
                $sidMode,
                $t0,
                $ayanT0,
                $withNutation,
                $model,
                $sunDeclination
            )
        );
    }

    /**
     * Swiss Ephemeris compatible subset of swe_houses_armc().
     * Supports equal-house systems A, D, E, N, V, W.
     *
     * @return array{cusps:array<int, float>, ascmc:array<int, float>}
     */
    public static function housesArmc(
        float      $armc,
        float      $geolat,
        float      $eps,
        string|int $hsys,
        ?float     $sunDeclination = null
    ): array
    {
        $armc = Angle::degnorm($armc);
        $axes = self::axesFromArmc($armc, $geolat, $eps);

        $hsys = self::houseSystem($hsys);
        $cusps = match ($hsys) {
            'A', 'D', 'E', 'N', 'V', 'W' => self::equalCusps($axes['asc'], $axes['mc'], $hsys),
            'B' => self::alcabitiusCusps($armc, $geolat, $eps, $axes),
            'C' => self::campanusCusps($armc, $geolat, $eps, $axes),
            'F' => self::carterCusps($eps, $axes),
            'G' => self::gauquelinCusps($armc, $geolat, $eps, $axes),
            'H' => self::horizonCusps($armc, $geolat, $eps, $axes),
            'I' => self::sunshineCusps($armc, $geolat, $eps, $axes, $sunDeclination, false),
            'i' => self::sunshineCusps($armc, $geolat, $eps, $axes, $sunDeclination, true),
            'J' => self::savardCusps($armc, $geolat, $eps, $axes),
            'K' => self::kochCusps($armc, $geolat, $eps, $axes),
            'L' => self::pullenSdCusps($axes['asc'], $axes['mc']),
            'M' => self::morinusCusps($armc, $eps),
            'O' => self::porphyryCusps($axes['asc'], $axes['mc']),
            'P' => self::placidusCusps($armc, $geolat, $eps, $axes),
            'Q' => self::pullenSrCusps($axes['asc'], $axes['mc']),
            'R' => self::regiomontanusCusps($armc, $geolat, $eps, $axes),
            'S' => self::sripatiCusps($axes['asc'], $axes['mc']),
            'T' => self::topocentricCusps($armc, $geolat, $eps, $axes),
            'U' => self::krusinskiCusps($armc, $geolat, $eps, $axes),
            'X' => self::meridianCusps($armc, $eps),
            'Y' => self::apcCusps($armc, $geolat, $eps, $axes),
            default => self::placidusCusps($armc, $geolat, $eps, $axes),
        };

        return [
            'cusps' => $cusps,
            'ascmc' => [
                0 => $axes['asc'],
                1 => $axes['mc'],
                2 => $armc,
                3 => $axes['vertex'],
                4 => $axes['equasc'],
                5 => $axes['coasc1'],
                6 => $axes['coasc2'],
                7 => $axes['polasc'],
                8 => 0.0,
                9 => 0.0,
            ],
        ];
    }

    public static function housesArmcResult(
        float      $armc,
        float      $geolat,
        float      $eps,
        string|int $hsys,
        ?float     $sunDeclination = null
    ): HousesResult
    {
        return HousesResult::fromArray(self::housesArmc($armc, $geolat, $eps, $hsys, $sunDeclination));
    }

    /**
     * Traditional sidereal variant of housesArmc().
     *
     * @return array{cusps:array<int, float>, ascmc:array<int, float>}
     */
    public static function siderealHousesArmc(
        float      $armc,
        float      $geolat,
        float      $eps,
        string|int $hsys,
        float      $ayanamsa,
        ?float     $sunDeclination = null
    ): array
    {
        $hsys = self::houseSystem($hsys);

        $houses = self::housesArmc(
            $armc,
            $geolat,
            $eps,
            $hsys === 'W' ? self::HSYS_EQUAL : $hsys,
            $sunDeclination
        );

        return self::applyAyanamsaToHouses($houses, $ayanamsa, $hsys);
    }

    public static function siderealHousesArmcResult(
        float      $armc,
        float      $geolat,
        float      $eps,
        string|int $hsys,
        float      $ayanamsa,
        ?float     $sunDeclination = null
    ): HousesResult
    {
        return HousesResult::fromArray(
            self::siderealHousesArmc($armc, $geolat, $eps, $hsys, $ayanamsa, $sunDeclination)
        );
    }

    /**
     * Traditional sidereal houses from ARMC with user-provided ayanamsa.
     *
     * @return array{cusps:array<int, float>, ascmc:array<int, float>}
     */
    public static function customSiderealHousesArmc(
        float      $armc,
        float      $geolat,
        float      $eps,
        string|int $hsys,
        float      $ayanamsa,
        ?float     $sunDeclination = null,
    ): array
    {
        return self::siderealHousesArmc(
            $armc,
            $geolat,
            $eps,
            $hsys,
            $ayanamsa,
            $sunDeclination
        );
    }


    public static function customSiderealHousesArmcResult(
        float      $armc,
        float      $geolat,
        float      $eps,
        string|int $hsys,
        float      $ayanamsa,
        ?float     $sunDeclination = null,
    ): HousesResult
    {
        return HousesResult::fromArray(
            self::customSiderealHousesArmc($armc, $geolat, $eps, $hsys, $ayanamsa, $sunDeclination)
        );
    }

    /**
     * Traditional sidereal houses from ARMC with SE_SIDM_USER mode bits.
     *
     * @return array{cusps:array<int, float>, ascmc:array<int, float>}
     */
    public static function userSiderealHousesArmc(
        float      $tjdEt,
        float      $armc,
        float      $geolat,
        float      $eps,
        string|int $hsys,
        int        $sidMode,
        float      $t0,
        float      $ayanT0,
        bool       $withNutation = true,
        string     $model = Precession::MODEL_IAU_1976,
        ?float     $sunDeclination = null
    ): array
    {
        $ayanamsa = Ayanamsa::userAyanamsa($tjdEt, $sidMode, $t0, $ayanT0, $withNutation, $model);

        return self::siderealHousesArmc(
            $armc,
            $geolat,
            $eps,
            $hsys,
            $ayanamsa,
            $sunDeclination
        );
    }

    public static function userSiderealHousesArmcResult(
        float      $tjdEt,
        float      $armc,
        float      $geolat,
        float      $eps,
        string|int $hsys,
        int        $sidMode,
        float      $t0,
        float      $ayanT0,
        bool       $withNutation = true,
        string     $model = Precession::MODEL_IAU_1976,
        ?float     $sunDeclination = null
    ): HousesResult
    {
        return HousesResult::fromArray(
            self::userSiderealHousesArmc(
                $tjdEt,
                $armc,
                $geolat,
                $eps,
                $hsys,
                $sidMode,
                $t0,
                $ayanT0,
                $withNutation,
                $model,
                $sunDeclination
            )
        );
    }

    /**
     * @return array{asc:float, mc:float, vertex:float, equasc:float, coasc1:float, coasc2:float, polasc:float}
     */
    public static function axesFromArmc(float $armc, float $geolat, float $eps): array
    {
        $armc = Angle::degnorm($armc);

        if (abs(abs($geolat) - 90.0) < self::VERY_SMALL) {
            $geolat = $geolat < 0.0 ? -90.0 + self::VERY_SMALL : 90.0 - self::VERY_SMALL;
        }

        $sine = self::sind($eps);
        $cose = self::cosd($eps);

        $mc = self::armcToMc($armc, $eps);
        $asc = self::asc1($armc + 90.0, $geolat, $sine, $cose);

        $f = $geolat >= 0.0 ? 90.0 - $geolat : -90.0 - $geolat;
        $vertex = self::asc1($armc - 90.0, $f, $sine, $cose);

        if (abs($geolat) <= $eps && Angle::difdeg2n($vertex, $mc) > 0.0) {
            $vertex = Angle::degnorm($vertex + 180.0);
        }

        return [
            'asc' => $asc,
            'mc' => $mc,
            'vertex' => $vertex,
            'equasc' => self::armcToMc($armc + 90.0, $eps),
            'coasc1' => Angle::degnorm(self::asc1($armc - 90.0, $geolat, $sine, $cose) + 180.0),
            'coasc2' => $geolat >= 0.0
                ? self::asc1($armc + 90.0, 90.0 - $geolat, $sine, $cose)
                : self::asc1($armc + 90.0, -90.0 - $geolat, $sine, $cose),
            'polasc' => self::asc1($armc - 90.0, $geolat, $sine, $cose),
        ];
    }

    /**
     * @return array<int, float>
     */
    private static function equalVehlow(float $asc, float $mc): array
    {
        $asc = self::easternAscendant($asc, $mc);

        return self::cuspsFromStart(Angle::degnorm($asc - 15.0));
    }

    /**
     * @return array<int, float>
     */
    private static function wholeSignFromAscendant(float $asc, float $mc): array
    {
        $asc = self::easternAscendant($asc, $mc);
        $start = $asc - fmod($asc, 30.0);

        return self::cuspsFromStart($start);
    }

    private static function easternAscendant(float $asc, float $mc): float
    {
        $asc = Angle::degnorm($asc);
        $mc = Angle::degnorm($mc);

        if (Angle::difdeg2n($asc, $mc) < 0.0) {
            return Angle::degnorm($asc + 180.0);
        }

        return $asc;
    }

    private static function armcToMc(float $armc, float $eps): float
    {
        $armc = Angle::degnorm($armc);
        $cose = self::cosd($eps);

        if (abs($armc - 90.0) > self::VERY_SMALL && abs($armc - 270.0) > self::VERY_SMALL) {
            $mc = self::atand(self::tand($armc) / $cose);

            if ($armc > 90.0 && $armc <= 270.0) {
                $mc = Angle::degnorm($mc + 180.0);
            }

            return Angle::degnorm($mc);
        }

        return abs($armc - 90.0) <= self::VERY_SMALL ? 90.0 : 270.0;
    }

    private static function asc1(float $x1, float $f, float $sine, float $cose): float
    {
        $x1 = Angle::degnorm($x1);
        $n = (int)($x1 / 90.0 + 1.0);

        if (abs(90.0 - $f) < self::VERY_SMALL) {
            return 180.0;
        }

        if (abs(90.0 + $f) < self::VERY_SMALL) {
            return 0.0;
        }

        if ($n === 1) {
            $asc = self::asc2($x1, $f, $sine, $cose);
        } elseif ($n === 2) {
            $asc = 180.0 - self::asc2(180.0 - $x1, -$f, $sine, $cose);
        } elseif ($n === 3) {
            $asc = 180.0 + self::asc2($x1 - 180.0, -$f, $sine, $cose);
        } else {
            $asc = 360.0 - self::asc2(360.0 - $x1, $f, $sine, $cose);
        }

        $asc = Angle::degnorm($asc);

        foreach ([90.0, 180.0, 270.0] as $exact) {
            if (abs($asc - $exact) < self::VERY_SMALL) {
                return $exact;
            }
        }

        return abs($asc - 360.0) < self::VERY_SMALL ? 0.0 : $asc;
    }

    private static function asc2(float $x, float $f, float $sine, float $cose): float
    {
        $asc = -self::tand($f) * $sine + $cose * self::cosd($x);

        if (abs($asc) < self::VERY_SMALL) {
            $asc = 0.0;
        }

        $sinx = self::sind($x);

        if (abs($sinx) < self::VERY_SMALL) {
            $sinx = 0.0;
        }

        if ($sinx === 0.0) {
            $asc = $asc < 0.0 ? -self::VERY_SMALL : self::VERY_SMALL;
        } elseif ($asc === 0.0) {
            $asc = $sinx < 0.0 ? -90.0 : 90.0;
        } else {
            $asc = self::atand($sinx / $asc);
        }

        return $asc < 0.0 ? 180.0 + $asc : $asc;
    }

    private static function sind(float $x): float
    {
        return sin(deg2rad($x));
    }

    private static function cosd(float $x): float
    {
        return cos(deg2rad($x));
    }

    private static function tand(float $x): float
    {
        return tan(deg2rad($x));
    }

    private static function atand(float $x): float
    {
        return rad2deg(atan($x));
    }

    /**
     * Porphyry houses.
     *
     * @return array<int, float>
     */
    private static function porphyryCusps(float $asc, float $mc): array
    {
        $asc = Angle::degnorm($asc);
        $mc = Angle::degnorm($mc);

        $acmc = Angle::difdeg2n($asc, $mc);

        if ($acmc < 0.0) {
            $asc = Angle::degnorm($asc + 180.0);
            $acmc = Angle::difdeg2n($asc, $mc);
        }

        $cusps = [
            1 => $asc,
            2 => Angle::degnorm($asc + (180.0 - $acmc) / 3.0),
            3 => Angle::degnorm($asc + (180.0 - $acmc) / 3.0 * 2.0),
            10 => $mc,
            11 => Angle::degnorm($mc + $acmc / 3.0),
            12 => Angle::degnorm($mc + $acmc / 3.0 * 2.0),
        ];

        return self::fillOppositeCusps($cusps);
    }

    /**
     * @param array<int, float> $cusps
     * @return array<int, float>
     */
    private static function fillOppositeCusps(array $cusps): array
    {
        $cusps[4] = Angle::degnorm($cusps[10] + 180.0);
        $cusps[5] = Angle::degnorm($cusps[11] + 180.0);
        $cusps[6] = Angle::degnorm($cusps[12] + 180.0);
        $cusps[7] = Angle::degnorm($cusps[1] + 180.0);
        $cusps[8] = Angle::degnorm($cusps[2] + 180.0);
        $cusps[9] = Angle::degnorm($cusps[3] + 180.0);

        ksort($cusps);

        return $cusps;
    }

    /**
     * Morinus houses.
     *
     * @return array<int, float>
     */
    private static function morinusCusps(float $armc, float $eps): array
    {
        $cusps = [];

        for ($i = 1; $i <= 12; $i++) {
            $j = $i + 10;

            if ($j > 12) {
                $j -= 12;
            }

            $ra = Angle::degnorm($armc + $i * 30.0);
            $ecliptic = Coordinates::cotrans([$ra, 0.0, 1.0], $eps);

            $cusps[$j] = $ecliptic[0];
        }

        ksort($cusps);

        return $cusps;
    }

    /**
     * Regiomontanus houses.
     *
     * @param array{asc:float, mc:float, vertex:float, equasc:float, coasc1:float, coasc2:float, polasc:float} $axes
     * @return array<int, float>
     */
    private static function regiomontanusCusps(float $armc, float $geolat, float $eps, array $axes): array
    {
        $sine = self::sind($eps);
        $cose = self::cosd($eps);
        $tanfi = self::tand($geolat);

        $fh1 = self::atand($tanfi * 0.5);
        $fh2 = self::atand($tanfi * self::cosd(30.0));

        $cusps = [
            1 => $axes['asc'],
            2 => self::asc1(120.0 + $armc, $fh2, $sine, $cose),
            3 => self::asc1(150.0 + $armc, $fh1, $sine, $cose),
            10 => $axes['mc'],
            11 => self::asc1(30.0 + $armc, $fh1, $sine, $cose),
            12 => self::asc1(60.0 + $armc, $fh2, $sine, $cose),
        ];

        if (abs($geolat) >= 90.0 - $eps && Angle::difdeg2n($axes['asc'], $axes['mc']) < 0.0) {
            foreach ([1, 2, 3, 10, 11, 12] as $i) {
                $cusps[$i] = Angle::degnorm($cusps[$i] + 180.0);
            }
        }

        return self::fillOppositeCusps($cusps);
    }

    /**
     * Sripati houses.
     *
     * @return array<int, float>
     */
    private static function sripatiCusps(float $asc, float $mc): array
    {
        $asc = Angle::degnorm($asc);
        $mc = Angle::degnorm($mc);

        $acmc = Angle::difdeg2n($asc, $mc);

        if ($acmc < 0.0) {
            $asc = Angle::degnorm($asc + 180.0);
            $acmc = Angle::difdeg2n($asc, $mc);
        }

        $q1 = 180.0 - $acmc;
        $s1 = $q1 / 3.0;
        $s4 = $acmc / 3.0;

        $cusps = [
            1 => Angle::degnorm($asc - $s4 * 0.5),
            2 => Angle::degnorm($asc + $s1 * 0.5),
            3 => Angle::degnorm($asc + $s1 * 1.5),
            10 => Angle::degnorm($mc - $s1 * 0.5),
            11 => Angle::degnorm($mc + $s4 * 0.5),
            12 => Angle::degnorm($mc + $s4 * 1.5),
        ];

        return self::fillOppositeCusps($cusps);
    }

    /**
     * Topocentric houses, also known as Polich/Page.
     *
     * @param array{asc:float, mc:float, vertex:float, equasc:float, coasc1:float, coasc2:float, polasc:float} $axes
     * @return array<int, float>
     */
    private static function topocentricCusps(float $armc, float $geolat, float $eps, array $axes): array
    {
        $sine = self::sind($eps);
        $cose = self::cosd($eps);
        $tanfi = self::tand($geolat);

        $fh1 = self::atand($tanfi / 3.0);
        $fh2 = self::atand($tanfi * 2.0 / 3.0);

        $cusps = [
            1 => $axes['asc'],
            2 => self::asc1(120.0 + $armc, $fh2, $sine, $cose),
            3 => self::asc1(150.0 + $armc, $fh1, $sine, $cose),
            10 => $axes['mc'],
            11 => self::asc1(30.0 + $armc, $fh1, $sine, $cose),
            12 => self::asc1(60.0 + $armc, $fh2, $sine, $cose),
        ];

        if (abs($geolat) >= 90.0 - $eps && Angle::difdeg2n($axes['asc'], $axes['mc']) < 0.0) {
            foreach ([1, 2, 3, 10, 11, 12] as $i) {
                $cusps[$i] = Angle::degnorm($cusps[$i] + 180.0);
            }
        }

        return self::fillOppositeCusps($cusps);
    }

    private static function asind(float $x): float
    {
        return rad2deg(asin($x));
    }

    private static function acosd(float $x): float
    {
        return rad2deg(acos($x));
    }

    /**
     * Campanus houses.
     *
     * @param array{asc:float, mc:float, vertex:float, equasc:float, coasc1:float, coasc2:float, polasc:float} $axes
     * @return array<int, float>
     */
    private static function campanusCusps(float $armc, float $geolat, float $eps, array $axes): array
    {
        $sine = self::sind($eps);
        $cose = self::cosd($eps);

        $fh1 = self::asind(self::sind($geolat) / 2.0);
        $fh2 = self::asind(sqrt(3.0) / 2.0 * self::sind($geolat));

        $cosfi = self::cosd($geolat);

        if ($cosfi === 0.0) {
            $xh1 = $geolat > 0.0 ? 90.0 : 270.0;
            $xh2 = $xh1;
        } else {
            $xh1 = self::atand(sqrt(3.0) / $cosfi);
            $xh2 = self::atand(1.0 / sqrt(3.0) / $cosfi);
        }

        $cusps = [
            1 => $axes['asc'],
            2 => self::asc1($armc + 90.0 + $xh2, $fh2, $sine, $cose),
            3 => self::asc1($armc + 90.0 + $xh1, $fh1, $sine, $cose),
            10 => $axes['mc'],
            11 => self::asc1($armc + 90.0 - $xh1, $fh1, $sine, $cose),
            12 => self::asc1($armc + 90.0 - $xh2, $fh2, $sine, $cose),
        ];

        if (abs($geolat) >= 90.0 - $eps && Angle::difdeg2n($axes['asc'], $axes['mc']) < 0.0) {
            foreach ([1, 2, 3, 10, 11, 12] as $i) {
                $cusps[$i] = Angle::degnorm($cusps[$i] + 180.0);
            }
        }

        return self::fillOppositeCusps($cusps);
    }

    /**
     * Alcabitius houses.
     *
     * @param array{asc:float, mc:float, vertex:float, equasc:float, coasc1:float, coasc2:float, polasc:float} $axes
     * @return array<int, float>
     */
    private static function alcabitiusCusps(float $armc, float $geolat, float $eps, array $axes): array
    {
        $sine = self::sind($eps);
        $cose = self::cosd($eps);

        $asc = $axes['asc'];
        $mc = $axes['mc'];

        $acmc = Angle::difdeg2n($asc, $mc);

        if ($acmc < 0.0) {
            $asc = Angle::degnorm($asc + 180.0);
            $acmc = Angle::difdeg2n($asc, $mc);
        }

        $dek = self::asind(self::sind($asc) * $sine);
        $r = -self::tand($geolat) * self::tand($dek);
        $r = max(-1.0, min(1.0, $r));

        $sda = self::acosd($r);
        $sna = 180.0 - $sda;

        $sd3 = $sda / 3.0;
        $sn3 = $sna / 3.0;

        $cusps = [
            1 => $asc,
            2 => self::asc1($armc + 180.0 - 2.0 * $sn3, 0.0, $sine, $cose),
            3 => self::asc1($armc + 180.0 - $sn3, 0.0, $sine, $cose),
            10 => $mc,
            11 => self::asc1($armc + $sd3, 0.0, $sine, $cose),
            12 => self::asc1($armc + 2.0 * $sd3, 0.0, $sine, $cose),
        ];

        return self::fillOppositeCusps($cusps);
    }


    /**
     * Koch houses.
     *
     * Swiss Ephemeris falls back to Porphyry inside polar circles.
     *
     * @param array{asc:float, mc:float, vertex:float, equasc:float, coasc1:float, coasc2:float, polasc:float} $axes
     * @return array<int, float>
     */
    private static function kochCusps(float $armc, float $geolat, float $eps, array $axes): array
    {
        if (abs($geolat) >= 90.0 - $eps) {
            return self::porphyryCusps($axes['asc'], $axes['mc']);
        }

        $sine = self::sind($eps);
        $cose = self::cosd($eps);

        $sina = self::sind($axes['mc']) * $sine / self::cosd($geolat);
        $sina = max(-1.0, min(1.0, $sina));

        $cosa = sqrt(1.0 - $sina * $sina);
        $c = self::atand(self::tand($geolat) / $cosa);
        $ad3 = self::asind(self::sind($c) * $sina) / 3.0;

        $cusps = [
            1 => $axes['asc'],
            2 => self::asc1($armc + 120.0 + $ad3, $geolat, $sine, $cose),
            3 => self::asc1($armc + 150.0 + 2.0 * $ad3, $geolat, $sine, $cose),
            10 => $axes['mc'],
            11 => self::asc1($armc + 30.0 - 2.0 * $ad3, $geolat, $sine, $cose),
            12 => self::asc1($armc + 60.0 - $ad3, $geolat, $sine, $cose),
        ];

        return self::fillOppositeCusps($cusps);
    }

    /**
     * Pullen SD, sinusoidal delta, formerly Neo-Porphyry.
     *
     * @return array<int, float>
     */
    private static function pullenSdCusps(float $asc, float $mc): array
    {
        $asc = Angle::degnorm($asc);
        $mc = Angle::degnorm($mc);

        $acmc = Angle::difdeg2n($asc, $mc);

        if ($acmc < 0.0) {
            $asc = Angle::degnorm($asc + 180.0);
            $acmc = Angle::difdeg2n($asc, $mc);
        }

        $q1 = 180.0 - $acmc;
        $cusps = [
            1 => $asc,
            10 => $mc,
        ];

        $d = ($acmc - 90.0) / 4.0;

        if ($acmc <= 30.0) {
            $cusps[11] = Angle::degnorm($mc + $acmc / 2.0);
            $cusps[12] = $cusps[11];
        } else {
            $cusps[11] = Angle::degnorm($mc + 30.0 + $d);
            $cusps[12] = Angle::degnorm($mc + 60.0 + 3.0 * $d);
        }

        $d = ($q1 - 90.0) / 4.0;

        if ($q1 <= 30.0) {
            $cusps[2] = Angle::degnorm($asc + $q1 / 2.0);
            $cusps[3] = $cusps[2];
        } else {
            $cusps[2] = Angle::degnorm($asc + 30.0 + $d);
            $cusps[3] = Angle::degnorm($asc + 60.0 + 3.0 * $d);
        }

        return self::fillOppositeCusps($cusps);
    }

    /**
     * Pullen SD, sinusoidal ratio.
     *
     * @return array<int, float>
     */
    private static function pullenSrCusps(float $asc, float $mc): array
    {
        $asc = Angle::degnorm($asc);
        $mc = Angle::degnorm($mc);

        $acmc = Angle::difdeg2n($asc, $mc);

        if ($acmc < 0.0) {
            $asc = Angle::degnorm($asc + 180.0);
            $acmc = Angle::difdeg2n($asc, $mc);
        }

        $q = $acmc;

        if ($q > 90.0) {
            $q = 180.0 - $q;
        }

        if ($q < 1e-30) {
            $x = 0.0;
            $xr = 0.0;
            $xr3 = 0.0;
            $xr4 = 180.0;
        } else {
            $third = 1.0 / 3.0;
            $two23 = pow(4.0, $third);

            $c = (180.0 - $q) / $q;
            $csq = $c * $c;
            $ccr = pow($csq - $c, $third);
            $cqx = sqrt($two23 * $ccr + 1.0);

            $r1 = 0.5 * $cqx;
            $r2 = 0.5 * sqrt(-2.0 * (1.0 - 2.0 * $c) / $cqx - $two23 * $ccr + 2.0);
            $r = $r1 + $r2 - 0.5;

            $x = $q / (2.0 * $r + 1.0);
            $xr = $r * $x;
            $xr3 = $xr * $r * $r;
            $xr4 = $xr3 * $r;
        }

        if ($acmc > 90.0) {
            $cusps = [
                1 => $asc,
                2 => Angle::degnorm($asc + $xr),
                3 => Angle::degnorm($asc + $xr + $x),
                10 => $mc,
                11 => Angle::degnorm($mc + $xr3),
                12 => Angle::degnorm($mc + $xr3 + $xr4),
            ];
        } else {
            $cusps = [
                1 => $asc,
                2 => Angle::degnorm($asc + $xr3),
                3 => Angle::degnorm($asc + $xr3 + $xr4),
                10 => $mc,
                11 => Angle::degnorm($mc + $xr),
                12 => Angle::degnorm($mc + $xr + $x),
            ];
        }

        return self::fillOppositeCusps($cusps);
    }

    /**
     * Carter poli-equatorial houses.
     *
     * @param array{asc:float, mc:float, vertex:float, equasc:float, coasc1:float, coasc2:float, polasc:float} $axes
     * @return array<int, float>
     */
    private static function carterCusps(float $eps, array $axes): array
    {
        $asc = $axes['asc'];
        $mc = $axes['mc'];

        if (Angle::difdeg2n($asc, $mc) < 0.0) {
            $asc = Angle::degnorm($asc + 180.0);
        }

        $equatorial = Coordinates::cotrans([$asc, 0.0, 1.0], -$eps);
        $ascRa = $equatorial[0];

        $cusps = [
            1 => $asc,
        ];

        foreach ([2, 3, 10, 11, 12] as $i) {
            $ra = Angle::degnorm($ascRa + ($i - 1) * 30.0);
            $cusps[$i] = self::armcToMc($ra, $eps);
        }

        return self::fillOppositeCusps($cusps);
    }

    /**
     * Meridian, or axial rotation, houses.
     *
     * @return array<int, float>
     */
    private static function meridianCusps(float $armc, float $eps): array
    {
        $cusps = [];

        for ($i = 1; $i <= 12; $i++) {
            $j = $i + 10;

            if ($j > 12) {
                $j -= 12;
            }

            $ra = Angle::degnorm($armc + $i * 30.0);
            $cusps[$j] = self::armcToMc($ra, $eps);
        }

        ksort($cusps);

        return $cusps;
    }

    /**
     * Savard-A houses.
     *
     * @param array{asc:float, mc:float, vertex:float, equasc:float, coasc1:float, coasc2:float, polasc:float} $axes
     * @return array<int, float>
     */
    private static function savardCusps(float $armc, float $geolat, float $eps, array $axes): array
    {
        $sine = self::sind($eps);
        $cose = self::cosd($eps);
        $sinfi = self::sind($geolat);
        $cosfi = self::cosd($geolat);

        if (abs($geolat) < self::VERY_SMALL) {
            $xs2 = 1.0 / 3.0;
            $xs1 = 1.0 / 2.0;
        } else {
            $xs2 = self::sind($geolat / 3.0) / $sinfi;
            $xs1 = self::sind(2.0 * $geolat / 3.0) / $sinfi;
        }

        $xs2 = self::asind($xs2);
        $xs1 = self::asind($xs1);

        if (abs($cosfi) < self::VERY_SMALL) {
            $xh1 = $geolat > 0.0 ? 90.0 : 270.0;
            $xh2 = $xh1;
        } else {
            $xh1 = self::atand(self::tand($xs1) / $cosfi);
            $xh2 = self::atand(self::tand($xs2) / $cosfi);
        }

        $fh1 = self::asind(self::sind($geolat) * self::sind(90.0 - $xs1));
        $fh2 = self::asind(self::sind($geolat) * self::sind(90.0 - $xs2));

        $cusps = [
            1 => $axes['asc'],
            2 => self::asc1($armc + 90.0 + $xh2, $fh2, $sine, $cose),
            3 => self::asc1($armc + 90.0 + $xh1, $fh1, $sine, $cose),
            10 => $axes['mc'],
            11 => self::asc1($armc + 90.0 - $xh1, $fh1, $sine, $cose),
            12 => self::asc1($armc + 90.0 - $xh2, $fh2, $sine, $cose),
        ];

        if (abs($geolat) >= 90.0 - $eps && Angle::difdeg2n($axes['asc'], $axes['mc']) < 0.0) {
            foreach ([1, 2, 3, 10, 11, 12] as $i) {
                $cusps[$i] = Angle::degnorm($cusps[$i] + 180.0);
            }
        }

        return self::fillOppositeCusps($cusps);
    }

    /**
     * Krusinski-Pisa-Goelzer houses.
     *
     * @param array{asc:float, mc:float, vertex:float, equasc:float, coasc1:float, coasc2:float, polasc:float} $axes
     * @return array<int, float>
     */
    private static function krusinskiCusps(float $armc, float $geolat, float $eps, array $axes): array
    {
        $asc = $axes['asc'];

        if (Angle::difdeg2n($asc, $axes['mc']) < 0.0) {
            $asc = Angle::degnorm($asc + 180.0);
        }

        $x = [$asc, 0.0, 1.0];

        $x = Coordinates::cotrans($x, -$eps);
        $x[0] -= $armc - 90.0;

        $x = Coordinates::cotrans($x, -(90.0 - $geolat));
        $horizonLon = $x[0];

        $x[0] = 0.0;
        $x = Coordinates::cotrans($x, -90.0);

        $cusps = [];

        for ($i = 0; $i < 6; $i++) {
            $x[0] = 30.0 * $i;
            $x[1] = 0.0;

            $x = Coordinates::cotrans($x, 90.0);
            $x[0] += $horizonLon;

            $x = Coordinates::cotrans($x, 90.0 - $geolat);
            $x[0] = Angle::degnorm($x[0] + $armc - 90.0);

            $cusp = self::armcToMc($x[0], $eps);

            $cusps[$i + 1] = $cusp;
            $cusps[$i + 7] = Angle::degnorm($cusp + 180.0);
        }

        ksort($cusps);

        return $cusps;
    }

    /**
     * Placidus houses.
     *
     * Swiss Ephemeris falls back to Porphyry inside polar circles or if iteration fails.
     *
     * @param array{asc:float, mc:float, vertex:float, equasc:float, coasc1:float, coasc2:float, polasc:float} $axes
     * @return array<int, float>
     */
    private static function placidusCusps(float $armc, float $geolat, float $eps, array $axes): array
    {
        if (abs($geolat) >= 90.0 - $eps) {
            return self::porphyryCusps($axes['asc'], $axes['mc']);
        }

        $sine = self::sind($eps);
        $cose = self::cosd($eps);
        $tane = self::tand($eps);
        $tanfi = self::tand($geolat);

        $a = self::asind($tanfi * $tane);
        $fh1 = self::atand(self::sind($a / 3.0) / $tane);
        $fh2 = self::atand(self::sind($a * 2.0 / 3.0) / $tane);

        $cusp11 = self::placidusIterativeCusp($armc + 30.0, $fh1, $tanfi, $sine, $cose, 3.0);
        $cusp12 = self::placidusIterativeCusp($armc + 60.0, $fh2, $tanfi, $sine, $cose, 1.5);
        $cusp2 = self::placidusIterativeCusp($armc + 120.0, $fh2, $tanfi, $sine, $cose, 1.5);
        $cusp3 = self::placidusIterativeCusp($armc + 150.0, $fh1, $tanfi, $sine, $cose, 3.0);

        if ($cusp11 === null || $cusp12 === null || $cusp2 === null || $cusp3 === null) {
            return self::porphyryCusps($axes['asc'], $axes['mc']);
        }

        return self::fillOppositeCusps([
            1 => $axes['asc'],
            2 => $cusp2,
            3 => $cusp3,
            10 => $axes['mc'],
            11 => $cusp11,
            12 => $cusp12,
        ]);
    }

    private static function placidusIterativeCusp(
        float $rectasc,
        float $initialPole,
        float $tanfi,
        float $sine,
        float $cose,
        float $divisor
    ): ?float
    {
        $rectasc = Angle::degnorm($rectasc);
        $cusp = self::asc1($rectasc, $initialPole, $sine, $cose);
        $tant = self::tand(self::asind($sine * self::sind($cusp)));

        if (abs($tant) < self::VERY_SMALL) {
            return $rectasc;
        }

        $f = self::placidusPoleHeight($tanfi, $tant, $divisor);
        $cusp = self::asc1($rectasc, $f, $sine, $cose);
        $previous = 0.0;

        for ($i = 0; $i <= self::PLACIDUS_MAX_ITERATIONS; $i++) {
            $tant = self::tand(self::asind($sine * self::sind($cusp)));

            if (abs($tant) < self::VERY_SMALL) {
                return $rectasc;
            }

            $f = self::placidusPoleHeight($tanfi, $tant, $divisor);
            $cusp = self::asc1($rectasc, $f, $sine, $cose);

            if ($i > 1 && abs(Angle::difdeg2n($cusp, $previous)) < self::VERY_SMALL_PLAC_ITER) {
                return $cusp;
            }

            $previous = $cusp;
        }

        return null;
    }

    private static function placidusPoleHeight(float $tanfi, float $tant, float $divisor): float
    {
        $x = $tanfi * $tant;
        $x = max(-1.0, min(1.0, $x));

        return self::atand(self::sind(self::asind($x) / $divisor) / $tant);
    }

    /**
     * Horizon / azimuth houses.
     *
     * @param array{asc:float, mc:float, vertex:float, equasc:float, coasc1:float, coasc2:float, polasc:float} $axes
     * @return array<int, float>
     */
    private static function horizonCusps(float $armc, float $geolat, float $eps, array $axes): array
    {
        $fi = $geolat > 0.0 ? 90.0 - $geolat : -90.0 - $geolat;

        if (abs(abs($fi) - 90.0) < self::VERY_SMALL) {
            $fi = $fi < 0.0 ? -90.0 + self::VERY_SMALL : 90.0 - self::VERY_SMALL;
        }

        $th = Angle::degnorm($armc + 180.0);
        $sine = self::sind($eps);
        $cose = self::cosd($eps);

        $fh1 = self::asind(self::sind($fi) / 2.0);
        $fh2 = self::asind(sqrt(3.0) / 2.0 * self::sind($fi));

        $cosfi = self::cosd($fi);

        if (abs($cosfi) < self::VERY_SMALL) {
            $xh1 = $fi > 0.0 ? 90.0 : 270.0;
            $xh2 = $xh1;
        } else {
            $xh1 = self::atand(sqrt(3.0) / $cosfi);
            $xh2 = self::atand(1.0 / sqrt(3.0) / $cosfi);
        }

        $cusps = [
            1 => Angle::degnorm(self::asc1($th + 90.0, $fi, $sine, $cose) + 180.0),
            2 => Angle::degnorm(self::asc1($th + 90.0 + $xh2, $fh2, $sine, $cose) + 180.0),
            3 => Angle::degnorm(self::asc1($th + 90.0 + $xh1, $fh1, $sine, $cose) + 180.0),
            10 => $axes['mc'],
            11 => Angle::degnorm(self::asc1($th + 90.0 - $xh1, $fh1, $sine, $cose) + 180.0),
            12 => Angle::degnorm(self::asc1($th + 90.0 - $xh2, $fh2, $sine, $cose) + 180.0),
        ];

        if (abs($fi) >= 90.0 - $eps && Angle::difdeg2n($axes['asc'], $axes['mc']) < 0.0) {
            foreach ([1, 2, 3, 10, 11, 12] as $i) {
                $cusps[$i] = Angle::degnorm($cusps[$i] + 180.0);
            }
        }

        return self::fillOppositeCusps($cusps);
    }

    /**
     * Gauquelin sections, counted clockwise.
     *
     * Returns sections 1..36. If calculation is impossible near polar circles,
     * Swiss Ephemeris falls back to 12 Porphyry cusps.
     *
     * @param array{asc:float, mc:float, vertex:float, equasc:float, coasc1:float, coasc2:float, polasc:float} $axes
     * @return array<int, float>
     */
    private static function gauquelinCusps(float $armc, float $geolat, float $eps, array $axes): array
    {
        if (abs($geolat) >= 90.0 - $eps) {
            return self::porphyryCusps($axes['asc'], $axes['mc']);
        }

        $sine = self::sind($eps);
        $cose = self::cosd($eps);
        $tane = self::tand($eps);
        $tanfi = self::tand($geolat);

        $a = self::asind($tanfi * $tane);
        $cusps = [];

        for ($ih = 2; $ih <= 9; $ih++) {
            $ih2 = 10 - $ih;
            $factor = $ih2 / 9.0;
            $fh1 = self::atand(self::sind($a * $factor) / $tane);
            $rectasc = Angle::degnorm(10.0 * $ih2 + $armc);

            $cusp = self::gauquelinIterativeCusp($rectasc, $fh1, $tanfi, $sine, $cose, $factor);

            if ($cusp === null) {
                return self::porphyryCusps($axes['asc'], $axes['mc']);
            }

            $cusps[$ih] = $cusp;
            $cusps[$ih + 18] = Angle::degnorm($cusp + 180.0);
        }

        for ($ih = 29; $ih <= 36; $ih++) {
            $ih2 = $ih - 28;
            $factor = $ih2 / 9.0;
            $fh1 = self::atand(self::sind($a * $factor) / $tane);
            $rectasc = Angle::degnorm(180.0 - $ih2 * 10.0 + $armc);

            $cusp = self::gauquelinIterativeCusp($rectasc, $fh1, $tanfi, $sine, $cose, $factor);

            if ($cusp === null) {
                return self::porphyryCusps($axes['asc'], $axes['mc']);
            }

            $cusps[$ih] = $cusp;
            $cusps[$ih - 18] = Angle::degnorm($cusp + 180.0);
        }

        $cusps[1] = $axes['asc'];
        $cusps[10] = $axes['mc'];
        $cusps[19] = Angle::degnorm($axes['asc'] + 180.0);
        $cusps[28] = Angle::degnorm($axes['mc'] + 180.0);

        ksort($cusps);

        return $cusps;
    }

    private static function gauquelinIterativeCusp(
        float $rectasc,
        float $initialPole,
        float $tanfi,
        float $sine,
        float $cose,
        float $factor
    ): ?float
    {
        $rectasc = Angle::degnorm($rectasc);
        $cusp = self::asc1($rectasc, $initialPole, $sine, $cose);
        $tant = self::tand(self::asind($sine * self::sind($cusp)));

        if (abs($tant) < self::VERY_SMALL) {
            return $rectasc;
        }

        $f = self::gauquelinPoleHeight($tanfi, $tant, $factor);
        $cusp = self::asc1($rectasc, $f, $sine, $cose);
        $previous = 0.0;

        for ($i = 1; $i <= self::PLACIDUS_MAX_ITERATIONS; $i++) {
            $tant = self::tand(self::asind($sine * self::sind($cusp)));

            if (abs($tant) < self::VERY_SMALL) {
                return $rectasc;
            }

            $f = self::gauquelinPoleHeight($tanfi, $tant, $factor);
            $cusp = self::asc1($rectasc, $f, $sine, $cose);

            if ($i > 1 && abs(Angle::difdeg2n($cusp, $previous)) < self::VERY_SMALL_PLAC_ITER) {
                return $cusp;
            }

            $previous = $cusp;
        }

        return null;
    }

    private static function gauquelinPoleHeight(float $tanfi, float $tant, float $factor): float
    {
        $x = $tanfi * $tant;
        $x = max(-1.0, min(1.0, $x));

        return self::atand(self::sind(self::asind($x) * $factor) / $tant);
    }

    /**
     * APC houses.
     *
     * @param array{asc:float, mc:float, vertex:float, equasc:float, coasc1:float, coasc2:float, polasc:float} $axes
     * @return array<int, float>
     */
    private static function apcCusps(float $armc, float $geolat, float $eps, array $axes): array
    {
        $cusps = [];

        for ($i = 1; $i <= 12; $i++) {
            $cusps[$i] = self::apcSector($i, deg2rad($geolat), deg2rad($eps), deg2rad($armc));
        }

        $cusps[10] = $axes['mc'];
        $cusps[4] = Angle::degnorm($axes['mc'] + 180.0);

        if (abs($geolat) >= 90.0 - $eps && Angle::difdeg2n($axes['asc'], $axes['mc']) < 0.0) {
            for ($i = 1; $i <= 12; $i++) {
                $cusps[$i] = Angle::degnorm($cusps[$i] + 180.0);
            }
        }

        ksort($cusps);

        return $cusps;
    }

    private static function apcSector(int $n, float $ph, float $e, float $az): float
    {
        if (abs(rad2deg($ph)) > 90.0 - self::VERY_SMALL) {
            $kv = 0.0;
            $dasc = 0.0;
        } else {
            $kv = atan(tan($ph) * tan($e) * cos($az) / (1.0 + tan($ph) * tan($e) * sin($az)));

            if (abs(rad2deg($ph)) < self::VERY_SMALL) {
                $dasc = deg2rad(90.0 - self::VERY_SMALL);

                if ($ph < 0.0) {
                    $dasc = -$dasc;
                }
            } else {
                $dasc = atan(sin($kv) / tan($ph));
            }
        }

        if ($n < 8) {
            $k = $n - 1;
            $a = $kv + $az + M_PI / 2.0 + $k * (M_PI / 2.0 - $kv) / 3.0;
        } else {
            $k = $n - 13;
            $a = $kv + $az + M_PI / 2.0 + $k * (M_PI / 2.0 + $kv) / 3.0;
        }

        $a = self::radnorm($a);

        $longitude = atan2(
            tan($dasc) * tan($ph) * sin($az) + sin($a),
            cos($e) * (tan($dasc) * tan($ph) * cos($az) + cos($a)) + sin($e) * tan($ph) * sin($az - $a)
        );

        return Angle::degnorm(rad2deg($longitude));
    }

    private static function radnorm(float $x): float
    {
        $twoPi = 2.0 * M_PI;
        $y = fmod($x, $twoPi);

        if (abs($y) < 1e-13) {
            return 0.0;
        }

        return $y < 0.0 ? $y + $twoPi : $y;
    }

    /**
     * Sunshine houses. $alternative=false is Treindl ('I'), true is Makransky ('i').
     *
     * @param array{asc:float, mc:float, vertex:float, equasc:float, coasc1:float, coasc2:float, polasc:float} $axes
     * @return array<int, float>
     */
    private static function sunshineCusps(
        float  $armc,
        float  $geolat,
        float  $eps,
        array  $axes,
        ?float $sunDeclination,
        bool   $alternative
    ): array
    {
        if ($sunDeclination === null) {
            throw new InvalidArgumentException('Sunshine house system needs Sun declination.');
        }

        if ($sunDeclination < -24.0 || $sunDeclination > 24.0) {
            throw new InvalidArgumentException('Sunshine house system needs valid Sun declination.');
        }

        $asc = $axes['asc'];
        $mc = $axes['mc'];

        if (Angle::difdeg2n($asc, $mc) < 0.0) {
            $asc = Angle::degnorm($asc + 180.0);

            if (!$alternative) {
                $mc = Angle::degnorm($mc + 180.0);
            }
        }

        $cusps = [
            1 => $asc,
            4 => Angle::degnorm($mc + 180.0),
            7 => Angle::degnorm($asc + 180.0),
            10 => $mc,
        ];

        $computed = $alternative
            ? self::sunshineMakranskyCusps($armc, $geolat, $eps, $sunDeclination)
            : self::sunshineTreindlCusps($armc, $geolat, $eps, $sunDeclination);

        if ($computed === null) {
            return self::porphyryCusps($asc, $mc);
        }

        foreach ($computed as $house => $longitude) {
            $cusps[$house] = $longitude;
        }

        ksort($cusps);

        return $cusps;
    }

    /**
     * @return array<int, float>|null
     */
    private static function sunshineTreindlCusps(float $armc, float $geolat, float $eps, float $sunDeclination): ?array
    {
        [$xh] = self::sunshineInit($geolat, $sunDeclination);

        $sinlat = self::sind($geolat);
        $coslat = self::cosd($geolat);
        $cosdec = self::cosd($sunDeclination);
        $tandec = self::tand($sunDeclination);
        $sinecl = self::sind($eps);
        $cosecl = self::cosd($eps);

        $mcdec = self::atand(self::sind($armc) * self::tand($eps));
        $mcUnderHorizon = abs($geolat - $mcdec) > 90.0;

        $cusps = [];

        for ($ih = 1; $ih <= 12; $ih++) {
            if (($ih - 1) % 3 === 0) {
                continue;
            }

            $xhs = 2.0 * self::asind($cosdec * self::sind($xh[$ih] / 2.0));
            $cosa = self::clampTrig($tandec * self::tand($xhs / 2.0));
            $alph = self::acosd($cosa);

            if ($ih > 7) {
                $alpha2 = 180.0 - $alph;
                $b = 90.0 - $geolat + $sunDeclination;
            } else {
                $alpha2 = $alph;
                $b = 90.0 - $geolat - $sunDeclination;
            }

            $cosc = self::cosd($xhs) * self::cosd($b) + self::sind($xhs) * self::sind($b) * self::cosd($alpha2);
            $c = self::acosd(self::clampTrig($cosc));

            if ($c < 1e-6) {
                return null;
            }

            $sinzd = self::sind($xhs) * self::sind($alpha2) / self::sind($c);
            $sinzd = self::clampTrig($sinzd);
            $zd = self::asind($sinzd);

            $rax = self::atand($coslat * self::tand($zd));
            $pole = self::asind(self::clampTrig($sinzd * $sinlat));

            if ($ih <= 6) {
                $pole = -$pole;
                $a = Angle::degnorm($rax + $armc + 180.0);
            } else {
                $a = Angle::degnorm($armc + $rax);
            }

            $cusps[$ih] = self::asc1($a, $pole, $sinecl, $cosecl);
        }

        if ($mcUnderHorizon) {
            foreach ($cusps as $ih => $longitude) {
                $cusps[$ih] = Angle::degnorm($longitude + 180.0);
            }
        }

        return $cusps;
    }

    /**
     * @return array<int, float>|null
     */
    private static function sunshineMakranskyCusps(float $armc, float $geolat, float $eps, float $sunDeclination): ?array
    {
        [$xh, $ok] = self::sunshineInit($geolat, $sunDeclination);

        if (!$ok) {
            return null;
        }

        $sinlat = self::sind($geolat);
        $coslat = self::cosd($geolat);
        $tanlat = self::tand($geolat);
        $tandec = self::tand($sunDeclination);
        $sinecl = self::sind($eps);

        $cusps = [];

        for ($ih = 1; $ih <= 12; $ih++) {
            if (($ih - 1) % 3 === 0) {
                continue;
            }

            $md = abs($xh[$ih]);

            if ($ih <= 6) {
                $rah = Angle::degnorm($armc + 180.0 + $xh[$ih]);
            } else {
                $rah = Angle::degnorm($armc + $xh[$ih]);
            }

            if ($geolat < 0.0) {
                $rah = Angle::degnorm(180.0 + $rah);
            }

            if ($md == 90.0) {
                $zd = 90.0 - self::atand($sinlat * $tandec);
            } else {
                if ($md < 90.0) {
                    $a = self::atand($coslat * self::tand($md));
                } else {
                    $a = self::atand(self::tand($md - 90.0) / $coslat);
                }

                $b = self::atand($tanlat * self::cosd($md));
                $c = $ih <= 6 ? $b + $sunDeclination : $b - $sunDeclination;
                $f = self::atand($sinlat * self::sind($md) * self::tand($c));
                $zd = $a + $f;
            }

            $pole = self::asind(self::clampTrig(self::sind($zd) * $sinlat));
            $q = self::asind(self::clampTrig($tandec * self::tand($pole)));

            if ($ih <= 3 || $ih >= 11) {
                $w = Angle::degnorm($rah - $q);
            } else {
                $w = Angle::degnorm($rah + $q);
            }

            if (abs($w - 90.0) < self::VERY_SMALL) {
                $r = self::atand(self::sind($eps) * self::tand($pole));
                $cu = ($ih <= 3 || $ih >= 11) ? 90.0 + $r : 90.0 - $r;
            } elseif (abs($w - 270.0) < self::VERY_SMALL) {
                $r = self::atand($sinecl * self::tand($pole));
                $cu = ($ih <= 3 || $ih >= 11) ? 270.0 - $r : 270.0 + $r;
            } else {
                $m = self::atand(abs(self::tand($pole) / self::cosd($w)));

                if ($ih <= 3 || $ih >= 11) {
                    $z = ($w > 90.0 && $w < 270.0) ? $m - $eps : $m + $eps;
                } else {
                    $z = ($w > 90.0 && $w < 270.0) ? $m + $eps : $m - $eps;
                }

                if (abs($z - 90.0) < self::VERY_SMALL) {
                    $cu = $w < 180.0 ? 90.0 : 270.0;
                } else {
                    $r = self::atand(abs(self::cosd($m) * self::tand($w) / self::cosd($z)));

                    if ($w < 90.0) {
                        $cu = $r;
                    } elseif ($w < 180.0) {
                        $cu = 180.0 - $r;
                    } elseif ($w < 270.0) {
                        $cu = 180.0 + $r;
                    } else {
                        $cu = 360.0 - $r;
                    }
                }

                if ($z > 90.0) {
                    if ($w < 90.0) {
                        $cu = 180.0 - $r;
                    } elseif ($w < 180.0) {
                        $cu = $r;
                    } elseif ($w < 270.0) {
                        $cu = 360.0 - $r;
                    } else {
                        $cu = 180.0 + $r;
                    }
                }

                if ($geolat < 0.0) {
                    $cu = Angle::degnorm($cu + 180.0);
                }
            }

            $cusps[$ih] = Angle::degnorm($cu);
        }

        return $cusps;
    }

    /**
     * @return array{0:array<int, float>, 1:bool}
     */
    private static function sunshineInit(float $geolat, float $sunDeclination): array
    {
        $arg = self::tand($sunDeclination) * self::tand($geolat);

        if ($arg >= 1.0) {
            $ad = 90.0 - self::VERY_SMALL;
        } elseif ($arg <= -1.0) {
            $ad = -90.0 + self::VERY_SMALL;
        } else {
            $ad = self::asind($arg);
        }

        $nsa = 90.0 - $ad;
        $dsa = 90.0 + $ad;

        return [[
            2 => -2.0 * $nsa / 3.0,
            3 => -1.0 * $nsa / 3.0,
            5 => 1.0 * $nsa / 3.0,
            6 => 2.0 * $nsa / 3.0,
            8 => -2.0 * $dsa / 3.0,
            9 => -1.0 * $dsa / 3.0,
            11 => 1.0 * $dsa / 3.0,
            12 => 2.0 * $dsa / 3.0,
        ], abs($arg) < 1.0];
    }

    private static function clampTrig(float $x): float
    {
        return max(-1.0, min(1.0, $x));
    }

    /**
     * Compatible subset of swe_house_pos().
     *
     * Supported now: A, D, E, N, V, W.
     *
     * @param array{0:float, 1?:float} $xpin ecliptic longitude and latitude in degrees
     */
    public static function housePosition(
        float      $armc,
        float      $geolat,
        float      $eps,
        string|int $hsys,
        array      $xpin,
        ?float     $sunDeclination = null
    ): float
    {
        $hsys = self::houseSystem($hsys);
        $longitude = Angle::degnorm($xpin[0]);

        return match ($hsys) {
            'N' => self::housePositionEqualAries($longitude),
            'A', 'D', 'E', 'V', 'W' => self::housePositionEqual($armc, $geolat, $eps, $hsys, $longitude),
            'O', 'S' => self::housePositionPorphyryLike($armc, $geolat, $eps, $hsys, $longitude),
            'B' => self::housePositionAlcabitius($armc, $geolat, $eps, $longitude, (float)($xpin[1] ?? 0.0)),
            'C' => self::housePositionCampanus($armc, $geolat, $eps, $longitude, (float)($xpin[1] ?? 0.0)),
            'F' => self::housePositionCarter($armc, $geolat, $eps, $longitude, (float)($xpin[1] ?? 0.0)),
            'G' => self::housePositionPlacidusOrGauquelin($armc, $geolat, $eps, $longitude, (float)($xpin[1] ?? 0.0), true),
            'H' => self::housePositionHorizon($armc, $geolat, $eps, $longitude, (float)($xpin[1] ?? 0.0)),
            'I', 'i' => self::housePositionSunshine($armc, $geolat, $eps, $longitude, (float)($xpin[1] ?? 0.0), $sunDeclination),
            'J' => self::housePositionSavard($armc, $geolat, $eps, $longitude, (float)($xpin[1] ?? 0.0)),
            'K' => self::housePositionKoch($armc, $geolat, $eps, $longitude, (float)($xpin[1] ?? 0.0)),
            'L', 'Q' => self::housePositionFromHouseCusps($armc, $geolat, $eps, $hsys, $longitude),
            'M' => self::housePositionMorinus($armc, $eps, $longitude),
            'P' => self::housePositionPlacidusOrGauquelin($armc, $geolat, $eps, $longitude, (float)($xpin[1] ?? 0.0), false),
            'R' => self::housePositionRegiomontanus($armc, $geolat, $eps, $longitude, (float)($xpin[1] ?? 0.0)),
            'T' => self::housePositionTopocentric($armc, $geolat, $eps, $longitude, (float)($xpin[1] ?? 0.0)),
            'U' => self::housePositionKrusinski($armc, $geolat, $eps, $longitude, (float)($xpin[1] ?? 0.0)),
            'X' => self::housePositionMeridian($armc, $eps, $longitude, (float)($xpin[1] ?? 0.0)),
            'Y' => self::housePositionApc($armc, $geolat, $eps, $longitude, (float)($xpin[1] ?? 0.0)),
            default => throw new InvalidArgumentException('House position is not implemented for this house system yet.'),
        };
    }

    private static function housePositionEqualAries(float $longitude): float
    {
        return Angle::degnorm($longitude) / 30.0 + 1.0;
    }

    private static function housePositionEqual(
        float  $armc,
        float  $geolat,
        float  $eps,
        string $hsys,
        float  $longitude
    ): float
    {
        $axes = self::axesFromArmc($armc, $geolat, $eps);
        $asc = self::fixAscPolar($axes['asc'], $armc, $eps, $geolat);
        $mc = $axes['mc'];

        $distance = match ($hsys) {
            'D' => Angle::degnorm($longitude - $mc - 90.0),
            default => Angle::degnorm($longitude - $asc),
        };

        if ($hsys === 'V') {
            $distance = Angle::degnorm($distance + 15.0);
        }

        if ($hsys === 'W') {
            $distance = Angle::degnorm($distance + fmod($asc, 30.0));
        }

        $distance = Angle::degnorm($distance + self::MILLIARCSEC);

        $hpos = $distance / 30.0 + 1.0;

        return $hpos >= 13.0 ? $hpos - 12.0 : $hpos;
    }

    private static function fixAscPolar(float $asc, float $armc, float $eps, float $geolat): float
    {
        $demc = self::atand(self::sind($armc) * self::tand($eps));

        if ($geolat >= 0.0 && 90.0 - $geolat + $demc < 0.0) {
            return Angle::degnorm($asc + 180.0);
        }

        if ($geolat < 0.0 && -90.0 - $geolat + $demc > 0.0) {
            return Angle::degnorm($asc + 180.0);
        }

        return Angle::degnorm($asc);
    }

    private static function housePositionPorphyryLike(
        float  $armc,
        float  $geolat,
        float  $eps,
        string $hsys,
        float  $longitude
    ): float
    {
        $axes = self::axesFromArmc($armc, $geolat, $eps);
        $asc = self::fixAscPolar($axes['asc'], $armc, $eps, $geolat);
        $mc = $axes['mc'];

        $distance = Angle::degnorm($longitude - $asc + self::MILLIARCSEC);

        if ($distance < 180.0) {
            $hpos = 1.0;
        } else {
            $hpos = 7.0;
            $distance -= 180.0;
        }

        $acmc = Angle::difdeg2n($asc, $mc);

        if ($distance < 180.0 - $acmc) {
            $hpos += $distance * 3.0 / (180.0 - $acmc);
        } else {
            $hpos += 3.0 + ($distance - 180.0 + $acmc) * 3.0 / $acmc;
        }

        if ($hsys === 'S') {
            $hpos += 0.5;

            if ($hpos > 12.0) {
                return 1.0;
            }
        }

        return $hpos;
    }

    private static function housePositionAlcabitius(
        float $armc,
        float $geolat,
        float $eps,
        float $longitude,
        float $latitude
    ): float
    {
        $sine = self::sind($eps);
        $axes = self::axesFromArmc($armc, $geolat, $eps);
        $asc = self::fixAscPolar($axes['asc'], $armc, $eps, $geolat);

        $equatorial = Coordinates::cotrans([$longitude, $latitude, 1.0], -$eps);
        $ra = $equatorial[0];

        $mdd = Angle::degnorm($ra - $armc);

        if ($mdd >= 180.0) {
            $mdd -= 360.0;
        }

        $dek = self::asind(self::sind($asc) * $sine);
        $r = -self::tand($geolat) * self::tand($dek);
        $r = self::clampTrig($r);

        $sda = self::acosd($r);
        $sna = 180.0 - $sda;

        if ($mdd > 0.0) {
            if ($mdd < $sda) {
                $position = $mdd * 90.0 / $sda;
            } else {
                $position = 90.0 + ($mdd - $sda) * 90.0 / $sna;
            }
        } else {
            if ($mdd > -$sna) {
                $position = 360.0 + $mdd * 90.0 / $sna;
            } else {
                $position = 270.0 + ($mdd + $sna) * 90.0 / $sda;
            }
        }

        $hpos = Angle::degnorm($position - 90.0) / 30.0 + 1.0;

        return $hpos >= 13.0 ? $hpos - 12.0 : $hpos;
    }

    private static function housePositionMeridian(float $armc, float $eps, float $longitude, float $latitude): float
    {
        $equatorial = Coordinates::cotrans([$longitude, $latitude, 1.0], -$eps);
        $ra = $equatorial[0];

        $mdd = Angle::degnorm($ra - $armc);

        if ($mdd >= 180.0) {
            $mdd -= 360.0;
        }

        return Angle::degnorm($mdd - 90.0) / 30.0 + 1.0;
    }

    private static function housePositionCarter(
        float $armc,
        float $geolat,
        float $eps,
        float $longitude,
        float $latitude
    ): float
    {
        $equatorial = Coordinates::cotrans([$longitude, $latitude, 1.0], -$eps);
        $ra = $equatorial[0];

        $axes = self::axesFromArmc($armc, $geolat, $eps);
        $asc = self::fixAscPolar($axes['asc'], $armc, $eps, $geolat);
        $ascEquatorial = Coordinates::cotrans([$asc, 0.0, 1.0], -$eps);

        return Angle::degnorm($ra - $ascEquatorial[0]) / 30.0 + 1.0;
    }

    private static function housePositionMorinus(float $armc, float $eps, float $longitude): float
    {
        $longitude = Angle::degnorm($longitude);

        if (abs($longitude - 90.0) > self::VERY_SMALL && abs($longitude - 270.0) > self::VERY_SMALL) {
            $position = self::atand(self::tand($longitude) / self::cosd($eps));

            if ($longitude > 90.0 && $longitude <= 270.0) {
                $position = Angle::degnorm($position + 180.0);
            }
        } else {
            $position = abs($longitude - 90.0) <= self::VERY_SMALL ? 90.0 : 270.0;
        }

        return Angle::degnorm($position - $armc - 90.0) / 30.0 + 1.0;
    }

    private static function housePositionCampanus(
        float $armc,
        float $geolat,
        float $eps,
        float $longitude,
        float $latitude,
    ): float
    {
        $equatorial = Coordinates::cotrans([$longitude, $latitude, 1.0], -$eps);
        $ra = $equatorial[0];
        $de = $equatorial[1];

        $mdd = Angle::degnorm($ra - $armc);

        if ($mdd >= 180.0) {
            $mdd -= 360.0;
        }

        $primeVertical = Coordinates::cotrans([Angle::degnorm($mdd - 90.0), $de, 1.0], -$geolat);
        $distance = Angle::degnorm($primeVertical[0] + self::MILLIARCSEC);

        return $distance / 30.0 + 1.0;
    }

    private static function housePositionHorizon(
        float $armc,
        float $geolat,
        float $eps,
        float $longitude,
        float $latitude
    ): float
    {
        $equatorial = Coordinates::cotrans([$longitude, $latitude, 1.0], -$eps);
        $ra = $equatorial[0];
        $de = $equatorial[1];

        $mdd = Angle::degnorm($ra - $armc);

        if ($mdd >= 180.0) {
            $mdd -= 360.0;
        }

        $horizon = Coordinates::cotrans([Angle::degnorm($mdd - 90.0), $de, 1.0], 90.0 - $geolat);
        $distance = Angle::degnorm($horizon[0] + self::MILLIARCSEC);

        return $distance / 30.0 + 1.0;
    }

    private static function housePositionRegiomontanus(
        float $armc,
        float $geolat,
        float $eps,
        float $longitude,
        float $latitude
    ): float
    {
        $equatorial = Coordinates::cotrans([$longitude, $latitude, 1.0], -$eps);
        $ra = $equatorial[0];
        $de = $equatorial[1];

        $mdd = Angle::degnorm($ra - $armc);

        if ($mdd >= 180.0) {
            $mdd -= 360.0;
        }

        if (abs($mdd) < self::VERY_SMALL) {
            $distance = 270.0;
        } elseif (180.0 - abs($mdd) < self::VERY_SMALL) {
            $distance = 90.0;
        } else {
            if (90.0 - abs($geolat) < self::VERY_SMALL) {
                $geolat = $geolat > 0.0 ? 90.0 - self::VERY_SMALL : -90.0 + self::VERY_SMALL;
            }

            if (90.0 - abs($de) < self::VERY_SMALL) {
                $de = $de > 0.0 ? 90.0 - self::VERY_SMALL : -90.0 + self::VERY_SMALL;
            }

            $a = self::tand($geolat) * self::tand($de) + self::cosd($mdd);
            $distance = Angle::degnorm(self::atand(-$a / self::sind($mdd)));

            if ($mdd < 0.0) {
                $distance += 180.0;
            }

            $distance = Angle::degnorm($distance + self::MILLIARCSEC);
        }

        return $distance / 30.0 + 1.0;
    }

    private static function housePositionSavard(
        float $armc,
        float $geolat,
        float $eps,
        float $longitude,
        float $latitude
    ): float
    {
        $equatorial = Coordinates::cotrans([$longitude, $latitude, 1.0], -$eps);
        $ra = $equatorial[0];
        $de = $equatorial[1];

        $mdd = Angle::degnorm($ra - $armc);

        if ($mdd >= 180.0) {
            $mdd -= 360.0;
        }

        $sinfi = self::sind($geolat);

        if (abs($geolat) < self::VERY_SMALL) {
            $xs2 = 1.0 / 3.0;
            $xs1 = 2.0 / 3.0;
        } else {
            $xs2 = self::sind($geolat / 3.0) / $sinfi;
            $xs1 = self::sind(2.0 * $geolat / 3.0) / $sinfi;
        }

        $xs2 = self::asind($xs2);
        $xs1 = self::asind($xs1);

        $hcusp = [
            1 => 0.0,
            2 => $xs2,
            3 => $xs1,
            4 => 90.0,
            5 => 180.0 - $xs1,
            6 => 180.0 - $xs2,
            7 => 180.0,
            8 => 180.0 + $xs2,
            9 => 180.0 + $xs1,
            10 => 270.0,
            11 => 360.0 - $xs1,
            12 => 360.0 - $xs2,
        ];

        $primeVertical = Coordinates::cotrans([Angle::degnorm($mdd - 90.0), $de, 1.0], -$geolat);

        return self::housePositionFromCustomCusps($hcusp, $primeVertical[0]);
    }

    /**
     * @param array<int, float> $hcusp
     */
    private static function housePositionFromCustomCusps(array $hcusp, float $position): float
    {
        if (Angle::difdeg2n($hcusp[6], $hcusp[1]) > 0.0) {
            $d = Angle::degnorm($position - $hcusp[1]);

            for ($i = 1; $i <= 12; $i++) {
                $j = $i + 1;
                $c2 = $j > 12 ? 360.0 : Angle::degnorm($hcusp[$j] - $hcusp[1]);

                if ($d < $c2) {
                    break;
                }
            }

            $c1 = Angle::degnorm($hcusp[$i] - $hcusp[1]);
        } else {
            $d = Angle::degnorm($hcusp[1] - $position);

            for ($i = 1; $i <= 12; $i++) {
                $j = $i + 1;
                $c2 = $j > 12 ? 360.0 : Angle::degnorm($hcusp[1] - $hcusp[$j]);

                if ($d < $c2) {
                    break;
                }
            }

            $c1 = Angle::degnorm($hcusp[1] - $hcusp[$i]);
        }

        $hsize = $c2 - $c1;

        return $hsize == 0.0 ? (float)$i : $i + ($d - $c1) / $hsize;
    }

    private static function housePositionKrusinski(
        float $armc,
        float $geolat,
        float $eps,
        float $longitude,
        float $latitude
    ): float
    {
        if (abs($geolat) < self::VERY_SMALL) {
            $geolat = $geolat >= 0.0 ? self::VERY_SMALL : -self::VERY_SMALL;
        }

        $sine = self::sind($eps);
        $cose = self::cosd($eps);

        $equatorial = Coordinates::cotrans([$longitude, $latitude, 1.0], -$eps);
        $ra = $equatorial[0];
        $de = $equatorial[1];

        $asc = self::asc1(Angle::degnorm($armc + 90.0), $geolat, $sine, $cose);
        $asc = self::fixAscPolar($asc, $armc, $eps, $geolat);

        $ascEquatorial = Coordinates::cotrans([$asc, 0.0, 1.0], -$eps);
        $raep = Angle::degnorm($armc + 90.0);

        $x = [Angle::degnorm($raep - $ascEquatorial[0]), $ascEquatorial[1], 1.0];
        $x = Coordinates::cotrans($x, -(90.0 - $geolat));

        $tanx = self::tand($x[0]);

        if ($geolat == 0.0) {
            $xtemp = $tanx >= 0.0 ? 90.0 : -90.0;
        } else {
            $xtemp = self::atand($tanx / self::cosd(90.0 - $geolat));
        }

        if ($x[0] > 90.0 && $x[0] <= 270.0) {
            $xtemp = Angle::degnorm($xtemp + 180.0);
        }

        $raaz = Angle::degnorm($raep - Angle::degnorm($xtemp));

        $x = [Angle::degnorm($raep - $raaz), 0.0, 1.0];
        $x = Coordinates::cotrans($x, -(90.0 - $geolat));
        $x[1] += 90.0;
        $x = Coordinates::cotrans($x, 90.0 - $geolat);
        $oblaz = $x[1];

        $xasc = Coordinates::cotrans([$asc, 0.0, 1.0], -$eps);
        $xasc[0] = Angle::degnorm($xasc[0] - $raaz);
        $xtemp = self::atand(self::tand($xasc[0]) / self::cosd($oblaz));

        if ($xasc[0] > 90.0 && $xasc[0] <= 270.0) {
            $xtemp = Angle::degnorm($xtemp + 180.0);
        }

        $xascLongitude = Angle::degnorm($xtemp);

        $xp0 = Angle::degnorm($ra - $raaz);
        $xtemp = self::atand(self::tand($xp0) / self::cosd($oblaz));

        if ($xp0 > 90.0 && $xp0 <= 270.0) {
            $xtemp = Angle::degnorm($xtemp + 180.0);
        }

        $distance = Angle::degnorm(Angle::degnorm($xtemp) - $xascLongitude + self::MILLIARCSEC);

        return $distance / 30.0 + 1.0;
    }

    private static function housePositionPlacidusOrGauquelin(
        float $armc,
        float $geolat,
        float $eps,
        float $longitude,
        float $latitude,
        bool  $gauquelin
    ): float
    {
        $equatorial = Coordinates::cotrans([$longitude, $latitude, 1.0], -$eps);
        $ra = $equatorial[0];
        $de = $equatorial[1];

        $mdd = Angle::degnorm($ra - $armc);
        $mdn = Angle::degnorm($mdd + 180.0);

        if ($mdd >= 180.0) {
            $mdd -= 360.0;
        }

        if ($mdn >= 180.0) {
            $mdn -= 360.0;
        }

        if (90.0 - abs($de) <= abs($geolat)) {
            if ($de * $geolat < 0.0) {
                $distance = Angle::degnorm(90.0 + $mdn / 2.0);
            } else {
                $distance = Angle::degnorm(270.0 + $mdd / 2.0);
            }
        } else {
            $sinad = self::tand($de) * self::tand($geolat);
            $ad = self::asind(self::clampTrig($sinad));

            $a = $sinad + self::cosd($mdd);
            $isAboveHorizon = $a >= 0.0;

            $sad = 90.0 + $ad;
            $san = 90.0 - $ad;

            if ($isAboveHorizon) {
                $distance = ($mdd / $sad + 3.0) * 90.0;
            } else {
                $distance = ($mdn / $san + 1.0) * 90.0;
            }

            $distance = Angle::degnorm($distance + self::MILLIARCSEC);
        }

        if ($gauquelin) {
            return (360.0 - $distance) / 10.0 + 1.0;
        }

        return $distance / 30.0 + 1.0;
    }

    private static function housePositionKoch(
        float $armc,
        float $geolat,
        float $eps,
        float $longitude,
        float $latitude
    ): float
    {
        $equatorial = Coordinates::cotrans([$longitude, $latitude, 1.0], -$eps);
        $ra = $equatorial[0];
        $de = $equatorial[1];

        $mdd = Angle::degnorm($ra - $armc);

        if ($mdd >= 180.0) {
            $mdd -= 360.0;
        }

        if (90.0 - $geolat < $de || -90.0 - $geolat > $de) {
            $adp = 90.0;
        } elseif ($geolat - 90.0 > $de || $geolat + 90.0 < $de) {
            $adp = -90.0;
        } else {
            $adp = self::asind(self::clampTrig(self::tand($geolat) * self::tand($de)));
        }

        $admc = self::tand($eps) * self::tand($geolat) * self::sind($armc);
        $admc = self::asind(self::clampTrig($admc));
        $samc = 90.0 + $admc;

        if ($samc == 0.0) {
            return 0.0;
        }

        if ($mdd >= 0.0) {
            $dfac = ($mdd - $adp + $admc) / $samc;

            if ($dfac > 2.0 || $dfac < 0.0) {
                return 0.0;
            }

            $distance = Angle::degnorm(($dfac - 1.0) * 90.0 + self::MILLIARCSEC);
        } else {
            $dfac = ($mdd + 180.0 + $adp + $admc) / $samc;

            if ($dfac > 2.0 || $dfac < 0.0) {
                return 0.0;
            }

            $distance = Angle::degnorm(($dfac + 1.0) * 90.0 + self::MILLIARCSEC);
        }

        return $distance / 30.0 + 1.0;
    }

    private static function housePositionTopocentric(
        float $armc,
        float $geolat,
        float $eps,
        float $longitude,
        float $latitude
    ): float
    {
        $equatorial = Coordinates::cotrans([$longitude, $latitude, 1.0], -$eps);
        $ra = $equatorial[0];
        $de = $equatorial[1];

        $fh = max(-89.999, min(89.999, $geolat));
        $mdd = Angle::degnorm($ra - $armc);

        if ($de > 90.0 - self::VERY_SMALL) {
            $de = 90.0 - self::VERY_SMALL;
        }

        if ($de < -90.0 + self::VERY_SMALL) {
            $de = -90.0 + self::VERY_SMALL;
        }

        $sinad = self::clampTrig(self::tand($de) * self::tand($fh));
        $a = $sinad + self::cosd($mdd);
        $isAboveHorizon = $a >= 0.0;

        if (!$isAboveHorizon) {
            $ra = Angle::degnorm($ra + 180.0);
            $de = -$de;
            $mdd = Angle::degnorm($mdd + 180.0);
        }

        $isWesternHalf = $mdd > 180.0;

        if ($isWesternHalf) {
            $ra = Angle::degnorm($armc - $mdd);
        }

        $tanfi = self::tand($fh);
        $ra0 = Angle::degnorm($armc + 90.0);
        $xpLat = 1.0;
        $fac = 2.0;
        $nloop = 0;

        while (abs($xpLat) > 0.000001 && $nloop < 1000) {
            if ($xpLat > 0.0) {
                $fh = self::atand(self::tand($fh) - $tanfi / $fac);
                $ra0 -= 90.0 / $fac;
            } else {
                $fh = self::atand(self::tand($fh) + $tanfi / $fac);
                $ra0 += 90.0 / $fac;
            }

            $transformed = Coordinates::cotrans([Angle::degnorm($ra - $ra0), $de, 1.0], 90.0 - $fh);
            $xpLat = $transformed[1];

            $fac *= 2.0;
            $nloop++;
        }

        $hpos = Angle::degnorm($ra0 - $armc);

        if ($isWesternHalf) {
            $hpos = Angle::degnorm(-$hpos);
        }

        if (!$isAboveHorizon) {
            $hpos = Angle::degnorm($hpos + 180.0);
        }

        return Angle::degnorm($hpos - 90.0) / 30.0 + 1.0;
    }

    private static function housePositionFromHouseCusps(
        float  $armc,
        float  $geolat,
        float  $eps,
        string $hsys,
        float  $longitude
    ): float
    {
        $result = self::housesArmc($armc, $geolat, $eps, $hsys);
        $cusps = $result['cusps'];

        return self::housePositionFromCustomCusps($cusps, $longitude);
    }

    private static function housePositionApc(
        float $armc,
        float $geolat,
        float $eps,
        float $longitude,
        float $latitude
    ): float
    {
        $axes = self::axesFromArmc($armc, $geolat, $eps);
        $ascEquatorial = Coordinates::cotrans([$axes['asc'], 0.0, 1.0], -$eps);
        $ascDeclination = $ascEquatorial[1];

        return self::housePositionSunshineLike($armc, $geolat, $eps, $longitude, $latitude, $ascDeclination);
    }

    private static function housePositionSunshineLike(
        float $armc,
        float $geolat,
        float $eps,
        float $longitude,
        float $latitude,
        float $arcDeclination
    ): float
    {
        if ($geolat > 90.0 - self::MILLIARCSEC) {
            $geolat = 90.0 - self::MILLIARCSEC;
        }

        if ($geolat < -90.0 + self::MILLIARCSEC) {
            $geolat = -90.0 + self::MILLIARCSEC;
        }

        $equatorial = Coordinates::cotrans([$longitude, $latitude, 1.0], -$eps);
        $ra = $equatorial[0];
        $de = $equatorial[1];

        $mdd = Angle::degnorm($ra - $armc);

        if ($mdd >= 180.0) {
            $mdd -= 360.0;
        }

        if (90.0 - abs($de) < self::VERY_SMALL) {
            $de = $de > 0.0 ? 90.0 - self::VERY_SMALL : -90.0 + self::VERY_SMALL;
        }

        $a = self::tand($geolat) * self::tand($de) + self::cosd($mdd);
        $distance = Angle::degnorm(self::atand(-$a / self::sind($mdd)));

        if ($mdd < 0.0) {
            $distance += 180.0;
        }

        $distance = Angle::degnorm($distance);
        $isAboveHorizon = self::tand($de) * self::tand($geolat) + self::cosd($mdd) >= 0.0;

        $harmc = $geolat < 0.0 ? 90.0 + $geolat : 90.0 - $geolat;

        $darmc = Angle::degnorm($distance - 270.0);
        $isWesternHalf = false;

        if ($darmc > 180.0) {
            $isWesternHalf = true;
            $darmc = 360.0 - $darmc;
        }

        $sinad = self::tand($arcDeclination) * self::tand($geolat);

        if ($sinad >= 1.0) {
            $ad = 90.0;
        } elseif ($sinad <= -1.0) {
            $ad = -90.0;
        } else {
            $ad = self::asind($sinad);
        }

        $sad = 90.0 + $ad;
        $san = 90.0 - $ad;

        if ($sad == 0.0 && $isAboveHorizon) {
            $distance = 270.0;
        } elseif ($san == 0.0 && !$isAboveHorizon) {
            $distance = 90.0;
        } else {
            $sa = $sad;

            if (!$isAboveHorizon) {
                $arcDeclination = -$arcDeclination;
                $sa = $san;
                $darmc = 180.0 - $darmc;
                $isWesternHalf = !$isWesternHalf;
            }

            $a = self::acosd(self::clampTrig(self::cosd($harmc) * self::cosd($darmc)));

            if ($a < self::VERY_SMALL) {
                $a = self::VERY_SMALL;
            }

            $sinpsi = self::sind($harmc) / self::sind($a);
            $sinpsi = self::clampTrig($sinpsi);

            $y = self::sind($arcDeclination) / $sinpsi;

            if ($y > 1.0) {
                $y = 90.0 - self::VERY_SMALL;
            } elseif ($y < -1.0) {
                $y = -(90.0 - self::VERY_SMALL);
            } else {
                $y = self::asind($y);
            }

            $d = self::acosd(self::clampTrig(self::cosd($y) / self::cosd($arcDeclination)));

            if ($arcDeclination < 0.0) {
                $d = -$d;
            }

            if ($geolat < 0.0) {
                $d = -$d;
            }

            $darmc += $d;

            if ($isWesternHalf) {
                $distance = 270.0 - ($darmc / $sa) * 90.0;
            } else {
                $distance = 270.0 + ($darmc / $sa) * 90.0;
            }

            if (!$isAboveHorizon) {
                $distance = Angle::degnorm($distance + 180.0);
            }
        }

        $distance = Angle::degnorm($distance + self::MILLIARCSEC);

        return $distance / 30.0 + 1.0;
    }

    private static function housePositionSunshine(
        float  $armc,
        float  $geolat,
        float  $eps,
        float  $longitude,
        float  $latitude,
        ?float $sunDeclination
    ): float
    {
        if ($sunDeclination === null) {
            throw new InvalidArgumentException('Sunshine house position needs Sun declination.');
        }

        if ($sunDeclination < -24.0 || $sunDeclination > 24.0) {
            throw new InvalidArgumentException('Sunshine house position needs valid Sun declination.');
        }

        return self::housePositionSunshineLike($armc, $geolat, $eps, $longitude, $latitude, $sunDeclination);
    }

    /**
     * @param array{cusps:array<int, float>, ascmc:array<int, float>} $houses
     * @return array{cusps:array<int, float>, ascmc:array<int, float>}
     */
    public static function applyAyanamsaToHouses(array $houses, float $ayanamsa, string|int $hsys): array
    {
        $hsys = self::houseSystem($hsys);

        $cusps = [];

        foreach ($houses['cusps'] as $index => $cusp) {
            if ($hsys === 'N') {
                $cusps[$index] = ($index - 1) * 30.0;
                continue;
            }

            $sidereal = Angle::degnorm($cusp - $ayanamsa);

            if ($hsys === 'W') {
                $sidereal -= fmod($sidereal, 30.0);
            }

            $cusps[$index] = $sidereal;
        }

        $ascmc = [];

        foreach ($houses['ascmc'] as $index => $value) {
            $ascmc[$index] = $index === 2
                ? $value
                : Angle::degnorm($value - $ayanamsa);
        }

        return [
            'cusps' => $cusps,
            'ascmc' => $ascmc,
        ];
    }

    /**
     * @param array{cusps:array<int, float>, ascmc:array<int, float>} $houses
     * @return array{cusps:array<int, float>, ascmc:array<int, float>}
     */
    public static function removeAyanamsaFromHouses(array $houses, float $ayanamsa): array
    {
        $cusps = [];

        foreach ($houses['cusps'] as $index => $cusp) {
            $cusps[$index] = Angle::degnorm($cusp + $ayanamsa);
        }

        $ascmc = [];

        foreach ($houses['ascmc'] as $index => $value) {
            $ascmc[$index] = $index === 2
                ? $value
                : Angle::degnorm($value + $ayanamsa);
        }

        return [
            'cusps' => $cusps,
            'ascmc' => $ascmc,
        ];
    }
}