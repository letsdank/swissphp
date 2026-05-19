<?php

declare(strict_types=1);

namespace SwissEph;

final class Catalog
{
    public const SE_OK = 0;
    public const SE_ERR = -1;

    public const SE_SUN = 0;
    public const SE_MOON = 1;
    public const SE_MERCURY = 2;
    public const SE_VENUS = 3;
    public const SE_MARS = 4;
    public const SE_JUPITER = 5;
    public const SE_SATURN = 6;
    public const SE_URANUS = 7;
    public const SE_NEPTUNE = 8;
    public const SE_PLUTO = 9;
    public const SE_MEAN_NODE = 10;
    public const SE_TRUE_NODE = 11;
    public const SE_MEAN_APOG = 12;
    public const SE_OSCU_APOG = 13;
    public const SE_EARTH = 14;
    public const SE_CHIRON = 15;
    public const SE_PHOLUS = 16;
    public const SE_CERES = 17;
    public const SE_PALLAS = 18;
    public const SE_JUNO = 19;
    public const SE_VESTA = 20;
    public const SE_INTP_APOG = 21;
    public const SE_INTP_PERG = 22;

    public const SE_AST_OFFSET = 10000;

    public const SEFLG_JPLEPH = 1;
    public const SEFLG_SWIEPH = 2;
    public const SEFLG_MOSEPH = 4;
    public const SEFLG_HELCTR = 8;
    public const SEFLG_TRUEPOS = 16;
    public const SEFLG_J2000 = 32;
    public const SEFLG_NONUT = 64;
    public const SEFLG_SPEED3 = 128;
    public const SEFLG_SPEED = 256;
    public const SEFLG_NOGDEFL = 512;
    public const SEFLG_NOABERR = 1024;
    public const SEFLG_ASTROMETRIC = self::SEFLG_NOABERR | self::SEFLG_NOGDEFL;
    public const SEFLG_EQUATORIAL = 2 * 1024;
    public const SEFLG_XYZ = 4 * 1024;
    public const SEFLG_RADIANS = 8 * 1024;
    public const SEFLG_BARYCTR = 16 * 1024;
    public const SEFLG_TOPOCTR = 32 * 1024;
    public const SEFLG_TROPICAL = 0;
    public const SEFLG_SIDEREAL = 64 * 1024;
    public const SEFLG_ICRS = 128 * 1024;
    public const SEFLG_DPSIDEPS_1980 = 256 * 1024;
    public const SEFLG_JPLHOR = self::SEFLG_DPSIDEPS_1980;
    public const SEFLG_JPLHOR_APPROX = 512 * 1024;
    public const SEFLG_CENTER_BODY = 1024 * 1024;
    public const SEFLG_ORBEL_AA = 2048 * 1024;

    public const SEFLG_DEFAULTEPH = self::SEFLG_SWIEPH;
    public const SEFLG_EPHMASK = self::SEFLG_JPLEPH | self::SEFLG_SWIEPH | self::SEFLG_MOSEPH;

    public const SE_TRUE_TO_APP = 0;
    public const SE_APP_TO_TRUE = 1;

    public const SE_CALC_RISE = 1;
    public const SE_CALC_SET = 2;
    public const SE_CALC_MTRANSIT = 4;
    public const SE_CALC_ITRANSIT = 8;

    public const SE_BIT_GEOCTR_NO_ECL_LAT = 128;
    public const SE_BIT_DISC_CENTER = 256;
    public const SE_BIT_NO_REFRACTION = 512;
    public const SE_BIT_CIVIL_TWILIGHT = 1024;
    public const SE_BIT_NAUTIC_TWILIGHT = 2048;
    public const SE_BIT_ASTRO_TWILIGHT = 4096;
    public const SE_BIT_DISC_BOTTOM = 8192;
    public const SE_BIT_FIXED_DISC_SIZE = 16384;
    public const SE_BIT_FORCE_SLOW_METHOD = 32768;

    public const SE_BIT_HINDU_RISING = self::SE_BIT_DISC_CENTER | self::SE_BIT_NO_REFRACTION | self::SE_BIT_GEOCTR_NO_ECL_LAT;

    public const SE_NODBIT_MEAN = 1;
    public const SE_NODBIT_OSCU = 2;
    public const SE_NODBIT_OSCU_BAR = 4;
    public const SE_NODBIT_FOPOINT = 256;

    public const SE_ECL2HOR = 0;
    public const SE_EQU2HOR = 1;

    public const SE_HOR2ECL = 0;
    public const SE_HOR2EQU = 1;

    public const SE_SIDM_FAGAN_BRADLEY = 0;
    public const SE_SIDM_LAHIRI = 1;
    public const SE_SIDM_DELUCE = 2;
    public const SE_SIDM_RAMAN = 3;
    public const SE_SIDM_USHASHASHI = 4;
    public const SE_SIDM_KRISHNAMURTI = 5;
    public const SE_SIDM_DJWHAL_KHUL = 6;
    public const SE_SIDM_YUKTESHWAR = 7;
    public const SE_SIDM_JN_BHASIN = 8;
    public const SE_SIDM_BABYL_KUGLER1 = 9;
    public const SE_SIDM_BABYL_KUGLER2 = 10;
    public const SE_SIDM_BABYL_KUGLER3 = 11;
    public const SE_SIDM_BABYL_HUBER = 12;
    public const SE_SIDM_BABYL_ETPSC = 13;
    public const SE_SIDM_ALDEBARAN_15TAU = 14;
    public const SE_SIDM_HIPPARCHOS = 15;
    public const SE_SIDM_SASSANIAN = 16;
    public const SE_SIDM_GALCENT_0SAG = 17;
    public const SE_SIDM_J2000 = 18;
    public const SE_SIDM_J1900 = 19;
    public const SE_SIDM_B1950 = 20;
    public const SE_SIDM_SURYASIDDHANTA = 21;
    public const SE_SIDM_SURYASIDDHANTA_MSUN = 22;
    public const SE_SIDM_ARYABHATA = 23;
    public const SE_SIDM_ARYABHATA_MSUN = 24;
    public const SE_SIDM_SS_REVATI = 25;
    public const SE_SIDM_SS_CITRA = 26;
    public const SE_SIDM_TRUE_CITRA = 27;
    public const SE_SIDM_TRUE_REVATI = 28;
    public const SE_SIDM_TRUE_PUSHYA = 29;
    public const SE_SIDM_GALCENT_RGILBRAND = 30;
    public const SE_SIDM_GALEQU_IAU1958 = 31;
    public const SE_SIDM_GALEQU_TRUE = 32;
    public const SE_SIDM_GALEQU_MULA = 33;
    public const SE_SIDM_GALALIGN_MARDYKS = 34;
    public const SE_SIDM_TRUE_MULA = 35;
    public const SE_SIDM_GALCENT_MULA_WILHELM = 36;
    public const SE_SIDM_ARYABHATA_522 = 37;
    public const SE_SIDM_BABYL_BRITTON = 38;
    public const SE_SIDM_TRUE_SHEORAN = 39;
    public const SE_SIDM_GALCENT_COCHRANE = 40;
    public const SE_SIDM_GALEQU_FIORENZA = 41;
    public const SE_SIDM_VALENS_MOON = 42;
    public const SE_SIDM_LAHIRI_1940 = 43;
    public const SE_SIDM_LAHIRI_VP285 = 44;
    public const SE_SIDM_KRISHNAMURTI_VP291 = 45;
    public const SE_SIDM_LAHIRI_ICRC = 46;
    public const SE_SIDM_USER = 255;

    public const SE_SIDBITS = 256;

    public const SE_SIDBIT_ECL_T0 = 256;
    public const SE_SIDBIT_SSY_PLANE = 512;
    public const SE_SIDBIT_USER_UT = 1024;
    public const SE_SIDBIT_ECL_DATE = 2048;
    public const SE_SIDBIT_NO_PREC_OFFSET = 4096;
    public const SE_SIDBIT_PREC_ORIG = 8192;

    public const SE_NSIDM_PREDEF = 47;

    public static function planetName(int $ipl): string
    {
        if ($ipl === self::SE_AST_OFFSET + 134340) {
            $ipl = self::SE_PLUTO;
        }

        $names = [
            self::SE_SUN => 'Sun',
            self::SE_MOON => 'Moon',
            self::SE_MERCURY => 'Mercury',
            self::SE_VENUS => 'Venus',
            self::SE_MARS => 'Mars',
            self::SE_JUPITER => 'Jupiter',
            self::SE_SATURN => 'Saturn',
            self::SE_URANUS => 'Uranus',
            self::SE_NEPTUNE => 'Neptune',
            self::SE_PLUTO => 'Pluto',
            self::SE_MEAN_NODE => 'mean Node',
            self::SE_TRUE_NODE => 'true Node',
            self::SE_MEAN_APOG => 'mean Apogee',
            self::SE_OSCU_APOG => 'osc. Apogee',
            self::SE_EARTH => 'Earth',
            self::SE_CHIRON => 'Chiron',
            self::SE_PHOLUS => 'Pholus',
            self::SE_CERES => 'Ceres',
            self::SE_PALLAS => 'Pallas',
            self::SE_JUNO => 'Juno',
            self::SE_VESTA => 'Vesta',
            self::SE_INTP_APOG => 'intp. Apogee',
            self::SE_INTP_PERG => 'intp. Perigee',
            self::SE_AST_OFFSET + 2060 => 'Chiron',
            self::SE_AST_OFFSET + 5145 => 'Pholus',
            self::SE_AST_OFFSET + 1 => 'Ceres',
            self::SE_AST_OFFSET + 2 => 'Pallas',
            self::SE_AST_OFFSET + 3 => 'Juno',
            self::SE_AST_OFFSET + 4 => 'Vesta',
        ];

        if (isset($names[$ipl])) {
            return $names[$ipl];
        }

        if ($ipl > self::SE_AST_OFFSET) {
            return sprintf('%d: not found (asteroid)', $ipl - self::SE_AST_OFFSET);
        }

        return (string)$ipl;
    }

    public static function hasFlag(int $flags, int $flag): bool
    {
        return ($flags & $flag) === $flag;
    }

    public static function normalizeEphemerisFlags(int $flags): int
    {
        if (($flags & self::SEFLG_EPHMASK) === 0) {
            return $flags | self::SEFLG_DEFAULTEPH;
        }

        return $flags;
    }

    public static function ephemerisFlag(int $flags): int
    {
        return self::normalizeEphemerisFlags($flags) & self::SEFLG_EPHMASK;
    }

    public static function wantsSpeed(int $flags): bool
    {
        return self::hasFlag($flags, self::SEFLG_SPEED) || self::hasFlag($flags, self::SEFLG_SPEED3);
    }

    public static function wantsSidereal(int $flags): bool
    {
        return self::hasFlag($flags, self::SEFLG_SIDEREAL);
    }

    public static function siderealMode(int $sidMode): int
    {
        $mode = $sidMode % self::SE_SIDBITS;

        return $mode < 0 ? $mode + self::SE_SIDBITS : $mode;
    }

    public static function hasSiderealModeBit(int $sidMode, int $bit): bool
    {
        return self::hasFlag($sidMode, $bit);
    }

    public static function isPredefinedSiderealMode(int $sidMode): bool
    {
        $mode = self::siderealMode($sidMode);

        return $mode >= 0 && $mode < self::SE_NSIDM_PREDEF;
    }

    public static function isUserSiderealMode(int $sidMode): bool
    {
        return self::siderealMode($sidMode) === self::SE_SIDM_USER;
    }

    public static function ayanamsaName(int $sidMode): ?string
    {
        $sidMode = self::siderealMode($sidMode);

        $names = [
            'Fagan/Bradley',
            'Lahiri',
            'De Luce',
            'Raman',
            'Usha/Shashi',
            'Krishnamurti',
            'Djwhal Khul',
            'Yukteshwar',
            'J.N. Bhasin',
            'Babylonian/Kugler 1',
            'Babylonian/Kugler 2',
            'Babylonian/Kugler 3',
            'Babylonian/Huber',
            'Babylonian/Eta Piscium',
            'Babylonian/Aldebaran = 15 Tau',
            'Hipparchos',
            'Sassanian',
            'Galact. Center = 0 Sag',
            'J2000',
            'J1900',
            'B1950',
            'Suryasiddhanta',
            'Suryasiddhanta, mean Sun',
            'Aryabhata',
            'Aryabhata, mean Sun',
            'SS Revati',
            'SS Citra',
            'True Citra',
            'True Revati',
            'True Pushya (PVRN Rao)',
            'Galactic Center (Gil Brand)',
            'Galactic Equator (IAU1958)',
            'Galactic Equator',
            'Galactic Equator mid-Mula',
            'Skydram (Mardyks)',
            'True Mula (Chandra Hari)',
            'Dhruva/Gal.Center/Mula (Wilhelm)',
            'Aryabhata 522',
            'Babylonian/Britton',
            '"Vedic"/Sheoran',
            'Cochrane (Gal.Center = 0 Cap)',
            'Galactic Equator (Fiorenza)',
            'Vettius Valens',
            'Lahiri 1940',
            'Lahiri VP285',
            'Krishnamurti-Senthilathiban',
            'Lahiri ICRC',
        ];

        return $names[$sidMode] ?? null;
    }
}