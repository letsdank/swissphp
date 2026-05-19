<?php

namespace SwissEph\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SwissEph\Angle;
use SwissEph\Ayanamsa;
use SwissEph\Catalog;
use SwissEph\DeltaT;

final class AyanamsaTest extends TestCase
{
    public function testMeanAyanamsaMatchesSwissEphemerisOriginalPrecessionFixtures(): void
    {
        $tjdEt = 2451545.000738760;

        self::assertEqualsWithDelta(
            24.740300000000,
            Ayanamsa::ayanamsa($tjdEt, Catalog::SE_SIDM_FAGAN_BRADLEY, false),
            2e-8
        );

        self::assertEqualsWithDelta(
            23.857092361111,
            Ayanamsa::ayanamsa($tjdEt, Catalog::SE_SIDM_LAHIRI, false),
            2e-8
        );

        self::assertEqualsWithDelta(
            23.760240027778,
            Ayanamsa::ayanamsa($tjdEt, Catalog::SE_SIDM_KRISHNAMURTI, false),
            2e-8
        );
    }

    public function testApparentAyanamsaMatchesSwissEphemerisFixturesWithApproximateNutation(): void
    {
        $tjdEt = 2451545.000738760;

        self::assertEqualsWithDelta(
            24.736390833333,
            Ayanamsa::ayanamsa($tjdEt, Catalog::SE_SIDM_FAGAN_BRADLEY),
            5e-5
        );

        self::assertEqualsWithDelta(
            23.853224750000,
            Ayanamsa::ayanamsa($tjdEt, Catalog::SE_SIDM_LAHIRI),
            5e-5
        );

        self::assertEqualsWithDelta(
            23.756330861111,
            Ayanamsa::ayanamsa($tjdEt, Catalog::SE_SIDM_KRISHNAMURTI),
            5e-5
        );
    }

    public function testAyanamsaUtConvertsUtToEt(): void
    {
        $tjdUt = 2451545.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, -1);

        self::assertEqualsWithDelta(
            Ayanamsa::ayanamsa($tjdEt, Catalog::SE_SIDM_LAHIRI, false),
            Ayanamsa::ayanamsaUt($tjdUt, Catalog::SE_SIDM_LAHIRI, false),
            1e-12
        );
    }

    public function testUnsupportedAyanamsaModeThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ayanamsa::ayanamsa(2451545.0, Catalog::SE_SIDM_USER);
    }

    public function testApplyAyanamsaNormalizesLongitude(): void
    {
        self::assertEqualsWithDelta(336.14677525, Ayanamsa::applyAyanamsa(0.0, 23.85322475), 1e-12);
        self::assertEqualsWithDelta(10.0, Ayanamsa::applyAyanamsa(370.0, 0.0), 1e-12);
    }

    public function testTropicalLongitudeRestoresSiderealLongitude(): void
    {
        $ayanamsa = 23.85322475;
        $tropical = 280.0;

        $sidereal = Ayanamsa::applyAyanamsa($tropical, $ayanamsa);

        self::assertEqualsWithDelta(
            $tropical,
            Ayanamsa::tropicalLongitude($sidereal, $ayanamsa),
            1e-12
        );
    }

    public function testSiderealLongitudeUsesComputedAyanamsa(): void
    {
        $tjdEt = 2451545.000738760;

        self::assertEqualsWithDelta(
            256.14677525,
            Ayanamsa::siderealLongitude(280.0, $tjdEt, Catalog::SE_SIDM_LAHIRI),
            5e-5
        );
    }

    public function testSiderealLongitudeUtUsesComputedAyanamsaUt(): void
    {
        self::assertEqualsWithDelta(
            Ayanamsa::siderealLongitude(280.0, 2451545.000738760, Catalog::SE_SIDM_LAHIRI, false),
            Ayanamsa::siderealLongitudeUt(280.0, 2451545.0, Catalog::SE_SIDM_LAHIRI, false),
            1e-12
        );
    }

    public function testCustomAyanamsaReturnsInitialValueAtReferenceEpoch(): void
    {
        self::assertEqualsWithDelta(
            30.0,
            Ayanamsa::customAyanamsa(2451545.0, 2451545.0, 30.0, false),
            1e-12
        );
    }

    public function testCustomAyanamsaUtMatchesSwissEphemerisFixture(): void
    {
        self::assertEqualsWithDelta(
            28.732168472222,
            Ayanamsa::customAyanamsaUt(2341500.0, 2374717.0, 30.0),
            5e-5
        );
    }

    public function testCustomAyanamsaUtConvertsUtToEt(): void
    {
        $tjdUt = 2341500.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, -1);

        self::assertEqualsWithDelta(
            Ayanamsa::customAyanamsa($tjdEt, 2374717.0, 30.0, false),
            Ayanamsa::customAyanamsaUt($tjdUt, 2374717.0, 30.0, false),
            1e-12
        );
    }

    public function testCustomSiderealLongitudeUsesCustomAyanamsa(): void
    {
        self::assertEqualsWithDelta(
            90.0,
            Ayanamsa::customSiderealLongitude(120.0, 2451545.0, 2451545.0, 30.0, false),
            1e-12
        );
    }

    public function testCustomSiderealLongitudeUtMatchesSwissEphemerisFixture(): void
    {
        self::assertEqualsWithDelta(
            91.267831527778,
            Ayanamsa::customSiderealLongitudeUt(120.0, 2341500.0, 2374717.0, 30.0),
            5e-5
        );
    }

    public function testCustomSiderealLongitudeUtConvertsUtToEt(): void
    {
        $tjdUt = 2341500.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, -1);

        self::assertEqualsWithDelta(
            Ayanamsa::customSiderealLongitude(120.0, $tjdEt, 2374717.0, 30.0, false),
            Ayanamsa::customSiderealLongitudeUt(120.0, $tjdUt, 2374717.0, 30.0, false),
            1e-12
        );
    }

    public function testStandardEquinoxAyanamsasMatchSwissEphemerisFixtures(): void
    {
        $tjdEt = 2451545.000738760;

        self::assertEqualsWithDelta(
            0.000000027778,
            Ayanamsa::ayanamsa($tjdEt, Catalog::SE_SIDM_J2000, false),
            1e-8
        );

        self::assertEqualsWithDelta(
            1.396581027778,
            Ayanamsa::ayanamsa($tjdEt, Catalog::SE_SIDM_J1900, false),
            1e-4
        );

        self::assertEqualsWithDelta(
            0.698370166667,
            Ayanamsa::ayanamsa($tjdEt, Catalog::SE_SIDM_B1950, false),
            1e-4
        );
    }

    public function testStandardEquinoxSiderealLongitude(): void
    {
        $tjdEt = 2451545.000738760;

        self::assertEqualsWithDelta(
            278.603418972222,
            Ayanamsa::siderealLongitude(280.0, $tjdEt, Catalog::SE_SIDM_J1900, false),
            1e-4
        );
    }

    public function testAyanamsaWithSpeedUsesSwissEphemerisFiniteDifferenceInterval(): void
    {
        $tjdEt = 2451545.000738760;

        $result = Ayanamsa::ayanamsaWithSpeed($tjdEt, Catalog::SE_SIDM_LAHIRI, false);

        $expectedSpeed = (
                Ayanamsa::ayanamsa($tjdEt, Catalog::SE_SIDM_LAHIRI, false)
                - Ayanamsa::ayanamsa($tjdEt - 0.001, Catalog::SE_SIDM_LAHIRI, false)
            ) / 0.001;

        self::assertEqualsWithDelta(23.857092361111, $result[0], 2e-8);
        self::assertEqualsWithDelta($expectedSpeed, $result[1], 1e-12);
    }

    public function testAyanamsaUtWithSpeedConvertsUtToEt(): void
    {
        $tjdUt = 2451545.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, -1);

        self::assertEqualsWithDelta(
            Ayanamsa::ayanamsaWithSpeed($tjdEt, Catalog::SE_SIDM_LAHIRI, false)[0],
            Ayanamsa::ayanamsaUtWithSpeed($tjdUt, Catalog::SE_SIDM_LAHIRI, false)[0],
            1e-12
        );

        self::assertEqualsWithDelta(
            Ayanamsa::ayanamsaWithSpeed($tjdEt, Catalog::SE_SIDM_LAHIRI, false)[1],
            Ayanamsa::ayanamsaUtWithSpeed($tjdUt, Catalog::SE_SIDM_LAHIRI, false)[1],
            1e-12
        );
    }

    public function testCustomAyanamsaWithSpeedUsesFiniteDifferenceInterval(): void
    {
        $tjdEt = 2341500.0 + DeltaT::deltatEx(2341500.0, -1);

        $result = Ayanamsa::customAyanamsaWithSpeed($tjdEt, 2374717.0, 30.0, false);

        $expectedSpeed = (
                Ayanamsa::customAyanamsa($tjdEt, 2374717.0, 30.0, false)
                - Ayanamsa::customAyanamsa($tjdEt - 0.001, 2374717.0, 30.0, false)
            ) / 0.001;

        self::assertEqualsWithDelta($expectedSpeed, $result[1], 1e-12);
    }

    public function testCustomAyanamsaUtWithSpeedConvertsUtToEt(): void
    {
        $tjdUt = 2341500.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, -1);

        self::assertEqualsWithDelta(
            Ayanamsa::customAyanamsaWithSpeed($tjdEt, 2374717.0, 30.0, false)[0],
            Ayanamsa::customAyanamsaUtWithSpeed($tjdUt, 2374717.0, 30.0, false)[0],
            1e-12
        );

        self::assertEqualsWithDelta(
            Ayanamsa::customAyanamsaWithSpeed($tjdEt, 2374717.0, 30.0, false)[1],
            Ayanamsa::customAyanamsaUtWithSpeed($tjdUt, 2374717.0, 30.0, false)[1],
            1e-12
        );
    }

    public function testApplyAyanamsaToPositionConvertsLongitudeAndSpeed(): void
    {
        $position = [280.0, 1.5, 0.9, 0.985, -0.001, 0.0002];

        $sidereal = Ayanamsa::applyAyanamsaToPosition($position, 23.85322475, 0.000038);

        self::assertEqualsWithDelta(256.14677525, $sidereal[0], 1e-12);
        self::assertSame($position[1], $sidereal[1]);
        self::assertSame($position[2], $sidereal[2]);
        self::assertEqualsWithDelta(0.984962, $sidereal[3], 1e-12);
        self::assertSame($position[4], $sidereal[4]);
        self::assertSame($position[5], $sidereal[5]);
    }

    public function testRemoveAyanamsaFromPositionRestoresLongitudeAndSpeed(): void
    {
        $position = [280.0, 1.5, 0.9, 0.985, -0.001, 0.0002];

        $sidereal = Ayanamsa::applyAyanamsaToPosition($position, 23.85322475, 0.000038);
        $restored = Ayanamsa::removeAyanamsaFromPosition($sidereal, 23.85322475, 0.000038);

        self::assertEqualsWithDelta($position[0], $restored[0], 1e-12);
        self::assertEqualsWithDelta($position[3], $restored[3], 1e-12);
    }

    public function testSiderealPositionUsesAyanamsaAndSpeed(): void
    {
        $tjdEt = 2451545.000738760;
        $position = [280.0, 1.5, 0.9, 0.985, -0.001, 0.0002];

        $sidereal = Ayanamsa::siderealPosition($position, $tjdEt, Catalog::SE_SIDM_LAHIRI, false);
        [$ayanamsa, $ayanamsaSpeed] = Ayanamsa::ayanamsaWithSpeed($tjdEt, Catalog::SE_SIDM_LAHIRI, false);

        self::assertEqualsWithDelta(Angle::degnorm($position[0] - $ayanamsa), $sidereal[0], 1e-12);
        self::assertEqualsWithDelta($position[3] - $ayanamsaSpeed, $sidereal[3], 1e-12);
    }

    public function testSiderealPositionUtConvertsUtToEt(): void
    {
        $position = [280.0, 1.5, 0.9, 0.985, -0.001, 0.0002];

        self::assertEqualsWithDelta(
            Ayanamsa::siderealPosition($position, 2451545.000738760, Catalog::SE_SIDM_LAHIRI, false)[0],
            Ayanamsa::siderealPositionUt($position, 2451545.0, Catalog::SE_SIDM_LAHIRI, false)[0],
            1e-12
        );
    }

    public function testCustomSiderealPositionUsesCustomAyanamsaAndSpeed(): void
    {
        $tjdUt = 2341500.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, -1);
        $position = [120.0, 2.0, 37.0, 0.02, 0.003, -0.01];

        $sidereal = Ayanamsa::customSiderealPosition($position, $tjdEt, 2374717.0, 30.0, false);
        [$ayanamsa, $ayanamsaSpeed] = Ayanamsa::customAyanamsaWithSpeed($tjdEt, 2374717.0, 30.0, false);

        self::assertEqualsWithDelta(Angle::degnorm($position[0] - $ayanamsa), $sidereal[0], 1e-12);
        self::assertEqualsWithDelta($position[3] - $ayanamsaSpeed, $sidereal[3], 1e-12);
    }

    public function testAyanamsaAcceptsSiderealModeBits(): void
    {
        $tjdEt = 2451545.000738760;

        self::assertEqualsWithDelta(
            Ayanamsa::ayanamsa($tjdEt, Catalog::SE_SIDM_LAHIRI, false),
            Ayanamsa::ayanamsa($tjdEt, Catalog::SE_SIDM_LAHIRI + Catalog::SE_SIDBIT_PREC_ORIG, false),
            1e-12
        );
    }

    public function testUserAyanamsaUsesUserUtBit(): void
    {
        $tjdUt = 2341500.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, -1);
        $sidMode = Catalog::SE_SIDM_USER + Catalog::SE_SIDBIT_USER_UT;

        self::assertEqualsWithDelta(
            Ayanamsa::customAyanamsa($tjdEt, 2374717.0, 30.0, false, true),
            Ayanamsa::userAyanamsa($tjdEt, $sidMode, 2374717.0, 30.0, false),
            1e-12
        );
    }

    public function testUserAyanamsaWithoutUserUtBitTreatsReferenceEpochAsEt(): void
    {
        $tjdUt = 2341500.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, -1);
        $sidMode = Catalog::SE_SIDM_USER;

        self::assertEqualsWithDelta(
            Ayanamsa::customAyanamsa($tjdEt, 2374717.0, 30.0, false, false),
            Ayanamsa::userAyanamsa($tjdEt, $sidMode, 2374717.0, 30.0, false),
            1e-12
        );
    }

    public function testUserAyanamsaUtConvertsUtToEt(): void
    {
        $tjdUt = 2341500.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, -1);
        $sidMode = Catalog::SE_SIDM_USER + Catalog::SE_SIDBIT_USER_UT;

        self::assertEqualsWithDelta(
            Ayanamsa::userAyanamsa($tjdEt, $sidMode, 2374717.0, 30.0, false),
            Ayanamsa::userAyanamsaUt($tjdUt, $sidMode, 2374717.0, 30.0, false),
            1e-12
        );
    }

    public function testUserAyanamsaWithSpeedUsesUserUtBit(): void
    {
        $tjdUt = 2341500.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, -1);
        $sidMode = Catalog::SE_SIDM_USER + Catalog::SE_SIDBIT_USER_UT;

        $custom = Ayanamsa::customAyanamsaWithSpeed($tjdEt, 2374717.0, 30.0, false, true);
        $user = Ayanamsa::userAyanamsaWithSpeed($tjdEt, $sidMode, 2374717.0, 30.0, false);

        self::assertEqualsWithDelta($custom[0], $user[0], 1e-12);
        self::assertEqualsWithDelta($custom[1], $user[1], 1e-12);
    }

    public function testUserAyanamsaUtWithSpeedConvertsUtToEt(): void
    {
        $tjdUt = 2341500.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, -1);
        $sidMode = Catalog::SE_SIDM_USER + Catalog::SE_SIDBIT_USER_UT;

        $et = Ayanamsa::userAyanamsaWithSpeed($tjdEt, $sidMode, 2374717.0, 30.0, false);
        $ut = Ayanamsa::userAyanamsaUtWithSpeed($tjdUt, $sidMode, 2374717.0, 30.0, false);

        self::assertEqualsWithDelta($et[0], $ut[0], 1e-12);
        self::assertEqualsWithDelta($et[1], $ut[1], 1e-12);
    }

    public function testUserSiderealLongitudeUsesUserUtBit(): void
    {
        $tjdUt = 2341500.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, -1);
        $sidMode = Catalog::SE_SIDM_USER + Catalog::SE_SIDBIT_USER_UT;

        self::assertEqualsWithDelta(
            Ayanamsa::customSiderealLongitude(120.0, $tjdEt, 2374717.0, 30.0, false, true),
            Ayanamsa::userSiderealLongitude(120.0, $tjdEt, $sidMode, 2374717.0, 30.0, false),
            1e-12
        );
    }

    public function testUserSiderealLongitudeUtConvertsUtToEt(): void
    {
        $tjdUt = 2341500.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, -1);
        $sidMode = Catalog::SE_SIDM_USER + Catalog::SE_SIDBIT_USER_UT;

        self::assertEqualsWithDelta(
            Ayanamsa::userSiderealLongitude(120.0, $tjdEt, $sidMode, 2374717.0, 30.0, false),
            Ayanamsa::userSiderealLongitudeUt(120.0, $tjdUt, $sidMode, 2374717.0, 30.0, false),
            1e-12
        );
    }

    public function testUserSiderealPositionUsesUserAyanamsaAndSpeed(): void
    {
        $tjdUt = 2341500.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, -1);
        $sidMode = Catalog::SE_SIDM_USER + Catalog::SE_SIDBIT_USER_UT;
        $position = [120.0, 2.0, 37.0, 0.02, 0.003, -0.01];

        $custom = Ayanamsa::customSiderealPosition($position, $tjdEt, 2374717.0, 30.0, false, true);
        $user = Ayanamsa::userSiderealPosition($position, $tjdEt, $sidMode, 2374717.0, 30.0, false);

        self::assertEqualsWithDelta($custom[0], $user[0], 1e-12);
        self::assertEqualsWithDelta($custom[3], $user[3], 1e-12);
    }

    public function testUserSiderealPositionUtConvertUtToEt(): void
    {
        $tjdUt = 2341500.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, -1);
        $sidMode = Catalog::SE_SIDM_USER + Catalog::SE_SIDBIT_USER_UT;
        $position = [120.0, 2.0, 37.0, 0.02, 0.003, -0.01];

        $et = Ayanamsa::userSiderealPosition($position, $tjdEt, $sidMode, 2374717.0, 30.0, false);
        $ut = Ayanamsa::userSiderealPositionUt($position, $tjdUt, $sidMode, 2374717.0, 30.0, false);

        self::assertEqualsWithDelta($et[0], $ut[0], 1e-12);
        self::assertEqualsWithDelta($et[3], $ut[3], 1e-12);
    }
}