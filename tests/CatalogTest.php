<?php

namespace SwissEph\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SwissEph\Catalog;

final class CatalogTest extends TestCase
{
    #[DataProvider('planetNameProvider')]
    public function testPlanetNameMatchesSwissEphemerisFixtures(int $ipl, string $expected): void
    {
        self::assertSame($expected, Catalog::planetName($ipl));
    }

    #[DataProvider('ayanamsaNameProvider')]
    public function testAyanamsaNameMatchesSwissEphemerisFixtures(int $sidMode, string $expected): void
    {
        self::assertSame($expected, Catalog::ayanamsaName($sidMode));
    }

    public function testPlutoAsteroidNumberIsMappedToPluto(): void
    {
        self::assertSame('Pluto', Catalog::planetName(Catalog::SE_AST_OFFSET + 134340));
    }

    public function testUnknownAsteroidName(): void
    {
        self::assertSame('999999: not found (asteroid)', Catalog::planetName(Catalog::SE_AST_OFFSET + 999999));
    }

    public function testUnknownAyanamsaNameReturnsNull(): void
    {
        self::assertNull(Catalog::ayanamsaName(Catalog::SE_SIDM_USER));
    }

    public function testSiderealModeConstantsMatchSwissEphemeris(): void
    {
        self::assertSame(10, Catalog::SE_SIDM_BABYL_KUGLER2);
        self::assertSame(14, Catalog::SE_SIDM_ALDEBARAN_15TAU);
        self::assertSame(18, Catalog::SE_SIDM_J2000);
        self::assertSame(34, Catalog::SE_SIDM_GALALIGN_MARDYKS);
        self::assertSame(46, Catalog::SE_SIDM_LAHIRI_ICRC);
        self::assertSame(255, Catalog::SE_SIDM_USER);
    }

    public function testCalculationFlagConstantsMatchSwissEphemeris(): void
    {
        self::assertSame(1, Catalog::SEFLG_JPLEPH);
        self::assertSame(2, Catalog::SEFLG_SWIEPH);
        self::assertSame(4, Catalog::SEFLG_MOSEPH);
        self::assertSame(256, Catalog::SEFLG_SPEED);
        self::assertSame(64 * 1024, Catalog::SEFLG_SIDEREAL);
        self::assertSame(Catalog::SEFLG_SWIEPH, Catalog::SEFLG_DEFAULTEPH);
        self::assertSame(7, Catalog::SEFLG_EPHMASK);
    }

    public function testOrbitalElementsAstronomicalAlmanacFlagMatchesSwissEphemeris(): void
    {
        self::assertSame(2097152, Catalog::SEFLG_ORBEL_AA);
    }

    public function testRiseSetFlagConstantsMatchSwissEphemeris(): void
    {
        self::assertSame(1, Catalog::SE_CALC_RISE);
        self::assertSame(2, Catalog::SE_CALC_SET);
        self::assertSame(4, Catalog::SE_CALC_MTRANSIT);
        self::assertSame(8, Catalog::SE_CALC_ITRANSIT);

        self::assertSame(128, Catalog::SE_BIT_GEOCTR_NO_ECL_LAT);
        self::assertSame(256, Catalog::SE_BIT_DISC_CENTER);
        self::assertSame(512, Catalog::SE_BIT_NO_REFRACTION);
        self::assertSame(1024, Catalog::SE_BIT_CIVIL_TWILIGHT);
        self::assertSame(2048, Catalog::SE_BIT_NAUTIC_TWILIGHT);
        self::assertSame(4096, Catalog::SE_BIT_ASTRO_TWILIGHT);
        self::assertSame(8192, Catalog::SE_BIT_DISC_BOTTOM);
        self::assertSame(16384, Catalog::SE_BIT_FIXED_DISC_SIZE);
        self::assertSame(32768, Catalog::SE_BIT_FORCE_SLOW_METHOD);

        self::assertSame(
            Catalog::SE_BIT_DISC_CENTER | Catalog::SE_BIT_NO_REFRACTION | Catalog::SE_BIT_GEOCTR_NO_ECL_LAT,
            Catalog::SE_BIT_HINDU_RISING
        );
    }

    public function testNormalizeEphemerisFlagsAddsDefaultEphemeris(): void
    {
        self::assertSame(
            Catalog::SEFLG_SWIEPH,
            Catalog::normalizeEphemerisFlags(0)
        );

        self::assertSame(
            Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH,
            Catalog::normalizeEphemerisFlags(Catalog::SEFLG_SPEED)
        );
    }

    public function testNormalizeEphemerisFlagsPreservesExplicitEphemeris(): void
    {
        self::assertSame(
            Catalog::SEFLG_MOSEPH | Catalog::SEFLG_SPEED,
            Catalog::normalizeEphemerisFlags(Catalog::SEFLG_MOSEPH | Catalog::SEFLG_SPEED)
        );
    }

    public function testEphemerisFlagExtractsEphemerisMask(): void
    {
        self::assertSame(
            Catalog::SEFLG_MOSEPH,
            Catalog::ephemerisFlag(Catalog::SEFLG_MOSEPH | Catalog::SEFLG_SPEED | Catalog::SEFLG_SIDEREAL)
        );

        self::assertSame(
            Catalog::SEFLG_SWIEPH,
            Catalog::ephemerisFlag(Catalog::SEFLG_SPEED)
        );
    }

    public function testFlagHelpers(): void
    {
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_SIDEREAL;

        self::assertTrue(Catalog::hasFlag($flags, Catalog::SEFLG_SPEED));
        self::assertTrue(Catalog::wantsSpeed($flags));
        self::assertTrue(Catalog::wantsSidereal($flags));
        self::assertFalse(Catalog::hasFlag($flags, Catalog::SEFLG_MOSEPH));
    }

    public function testSiderealBitConstantsMatchSwissEphemeris(): void
    {
        self::assertSame(256, Catalog::SE_SIDBITS);
        self::assertSame(256, Catalog::SE_SIDBIT_ECL_T0);
        self::assertSame(512, Catalog::SE_SIDBIT_SSY_PLANE);
        self::assertSame(1024, Catalog::SE_SIDBIT_USER_UT);
        self::assertSame(2048, Catalog::SE_SIDBIT_ECL_DATE);
        self::assertSame(4096, Catalog::SE_SIDBIT_NO_PREC_OFFSET);
        self::assertSame(8192, Catalog::SE_SIDBIT_PREC_ORIG);
        self::assertSame(47, Catalog::SE_NSIDM_PREDEF);
    }

    public function testSiderealModeStripsSiderealBits(): void
    {
        self::assertSame(
            Catalog::SE_SIDM_LAHIRI,
            Catalog::siderealMode(Catalog::SE_SIDM_LAHIRI + Catalog::SE_SIDBIT_PREC_ORIG)
        );

        self::assertSame(
            Catalog::SE_SIDM_USER,
            Catalog::siderealMode(Catalog::SE_SIDM_USER + Catalog::SE_SIDBIT_USER_UT)
        );
    }

    public function testSiderealModeNormalizesNegativeValues(): void
    {
        self::assertSame(255, Catalog::siderealMode(-1));
    }

    public function testSiderealModeBitHelpers(): void
    {
        $sidMode = Catalog::SE_SIDM_LAHIRI + Catalog::SE_SIDBIT_PREC_ORIG;

        self::assertTrue(Catalog::hasSiderealModeBit($sidMode, Catalog::SE_SIDBIT_PREC_ORIG));
        self::assertTrue(Catalog::isPredefinedSiderealMode($sidMode));
        self::assertFalse(Catalog::isUserSiderealMode($sidMode));

        self::assertTrue(Catalog::isUserSiderealMode(Catalog::SE_SIDM_USER + Catalog::SE_SIDBIT_USER_UT));
    }

    public function testAyanamsaNameAcceptsSiderealBits(): void
    {
        self::assertSame(
            'Lahiri',
            Catalog::ayanamsaName(Catalog::SE_SIDM_LAHIRI + Catalog::SE_SIDBIT_PREC_ORIG)
        );
    }

    public function testEclipseConstantsMatchSwissEphemeris(): void
    {
        self::assertSame(1, Catalog::SE_ECL_CENTRAL);
        self::assertSame(2, Catalog::SE_ECL_NONCENTRAL);
        self::assertSame(4, Catalog::SE_ECL_TOTAL);
        self::assertSame(8, Catalog::SE_ECL_ANNULAR);
        self::assertSame(16, Catalog::SE_ECL_PARTIAL);
        self::assertSame(32, Catalog::SE_ECL_ANNULAR_TOTAL);
        self::assertSame(32, Catalog::SE_ECL_HYBRID);
        self::assertSame(64, Catalog::SE_ECL_PENUMBRAL);

        self::assertSame(63, Catalog::SE_ECL_ALLTYPES_SOLAR);
        self::assertSame(84, Catalog::SE_ECL_ALLTYPES_LUNAR);

        self::assertSame(128, Catalog::SE_ECL_VISIBLE);
        self::assertSame(256, Catalog::SE_ECL_MAX_VISIBLE);
        self::assertSame(512, Catalog::SE_ECL_1ST_VISIBLE);
        self::assertSame(512, Catalog::SE_ECL_PARTBEG_VISIBLE);
        self::assertSame(1024, Catalog::SE_ECL_2ND_VISIBLE);
        self::assertSame(1024, Catalog::SE_ECL_TOTBEG_VISIBLE);
        self::assertSame(2048, Catalog::SE_ECL_3RD_VISIBLE);
        self::assertSame(2048, Catalog::SE_ECL_TOTEND_VISIBLE);
        self::assertSame(4096, Catalog::SE_ECL_4TH_VISIBLE);
        self::assertSame(4096, Catalog::SE_ECL_PARTEND_VISIBLE);
        self::assertSame(8192, Catalog::SE_ECL_PENUMBBEG_VISIBLE);
        self::assertSame(16384, Catalog::SE_ECL_PENUMBEND_VISIBLE);
        self::assertSame(32768, Catalog::SE_ECL_ONE_TRY);
    }

    /**
     * @return iterable<string, array{int, string}>
     */
    public static function planetNameProvider(): iterable
    {
        yield 'Sun' => [0, 'Sun'];
        yield 'Moon' => [1, 'Moon'];
        yield 'Mercury' => [2, 'Mercury'];
        yield 'Venus' => [3, 'Venus'];
        yield 'Mars' => [4, 'Mars'];
        yield 'Jupiter' => [5, 'Jupiter'];
        yield 'Saturn' => [6, 'Saturn'];
        yield 'Uranus' => [7, 'Uranus'];
        yield 'Neptune' => [8, 'Neptune'];
        yield 'Pluto' => [9, 'Pluto'];
        yield 'mean Node' => [10, 'mean Node'];
        yield 'true Node' => [11, 'true Node'];
        yield 'mean Apogee' => [12, 'mean Apogee'];
        yield 'osc. Apogee' => [13, 'osc. Apogee'];
        yield 'Earth' => [14, 'Earth'];
        yield 'Chiron' => [15, 'Chiron'];
        yield 'Pholus' => [16, 'Pholus'];
        yield 'Ceres' => [17, 'Ceres'];
        yield 'Pallas' => [18, 'Pallas'];
        yield 'Juno' => [19, 'Juno'];
        yield 'Vesta' => [20, 'Vesta'];
        yield 'intp. Apogee' => [21, 'intp. Apogee'];
        yield 'intp. Perigee' => [22, 'intp. Perigee'];
    }

    /**
     * @return iterable<string, array{int, string}>
     */
    public static function ayanamsaNameProvider(): iterable
    {
        yield 'Krishnamurti' => [5, 'Krishnamurti'];
        yield 'Djwhal Khul' => [6, 'Djwhal Khul'];
        yield 'Babylonian/Kugler 1' => [9, 'Babylonian/Kugler 1'];
        yield 'Lahiri' => [1, 'Lahiri'];
        yield 'Lahiri ICRC' => [46, 'Lahiri ICRC'];
    }
}