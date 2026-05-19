<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\AzimuthAltitude;
use SwissEph\Catalog;
use SwissEph\Observer;

final class AzimuthAltitudeTest extends TestCase
{
    public function testEquatorialToHorizontalCoordinates(): void
    {
        $result = AzimuthAltitude::azalt(
            2451545.0,
            Catalog::SE_EQU2HOR,
            new Observer(13.4050, 52.5200, 34.0),
            1013.25,
            15.0,
            [120.0, 22.0, 1.0]
        );

        self::assertEqualsWithDelta(174.101078553486019, $result[0], 1e-12);
        self::assertEqualsWithDelta(-15.287805416667926, $result[1], 1e-12);
        self::assertEqualsWithDelta(-15.287805416667926, $result[2], 1e-12);
    }

    public function testEclipticToHorizontalCoordinates(): void
    {
        $result = AzimuthAltitude::azalt(
            2451545.0,
            Catalog::SE_ECL2HOR,
            new Observer(13.4050, 52.5200, 34.0),
            1013.25,
            15.0,
            [280.0, 0.0, 1.0]
        );

        self::assertEqualsWithDelta(12.277991004649607, $result[0], 1e-12);
        self::assertEqualsWithDelta(13.573988136610756, $result[1], 1e-12);
        self::assertEqualsWithDelta(13.638694495967643, $result[2], 1e-12);
    }

    public function testPressureCanBeEstimatedFromObserverAltitude(): void
    {
        $result = AzimuthAltitude::azalt(
            2451545.0,
            Catalog::SE_ECL2HOR,
            new Observer(13.4050, 52.5200, 34.0),
            0.0,
            10.0,
            [280.0, 0.0, 1.0]
        );

        self::assertEqualsWithDelta(12.277991004649607, $result[0], 1e-12);
        self::assertEqualsWithDelta(13.573988136610756, $result[1], 1e-12);
        self::assertEqualsWithDelta(13.639514196334376, $result[2], 1e-12);
    }

    public function testVisibleEquatorialObjectGetsRefractedAltitude(): void
    {
        $result = AzimuthAltitude::azalt(
            2451545.0,
            Catalog::SE_EQU2HOR,
            new Observer(13.4050, 52.5200, 34.0),
            1013.25,
            15.0,
            [0.0, 0.0, 1.0]
        );

        self::assertEqualsWithDelta(289.342666927194387, $result[0], 1e-12);
        self::assertEqualsWithDelta(14.250029830549510, $result[1], 1e-12);
        self::assertEqualsWithDelta(14.311732979226518, $result[2], 1e-12);
    }

    public function testHorizontalToEquatorialCoordinates(): void
    {
        $result = AzimuthAltitude::azaltRev(
            2451545.0,
            Catalog::SE_HOR2EQU,
            new Observer(13.4050, 52.5200, 34.0),
            [174.101078553486019, -15.287805416667926]
        );

        self::assertEqualsWithDelta(120.0, $result[0], 1e-12);
        self::assertEqualsWithDelta(22.0, $result[1], 1e-12);
    }

    public function testHorizontalToEclipticCoordinates(): void
    {
        $result = AzimuthAltitude::azaltRev(
            2451545.0,
            Catalog::SE_HOR2ECL,
            new Observer(13.4050, 52.5200, 34.0),
            [12.277991004649607, 13.573988136610756]
        );

        self::assertEqualsWithDelta(280.0, $result[0], 1e-12);
        self::assertEqualsWithDelta(0.0, $result[1], 1e-12);
    }

    public function testHorizontalRoundTripUsesTrueAltitude(): void
    {
        $observer = new Observer(13.4050, 52.5200, 34.0);

        $horizontal = AzimuthAltitude::azalt(
            2451545.0,
            Catalog::SE_EQU2HOR,
            $observer,
            1013.25,
            15.0,
            [0.0, 0.0, 1.0]
        );

        $equatorial = AzimuthAltitude::azaltRev(
            2451545.0,
            Catalog::SE_HOR2EQU,
            $observer,
            [$horizontal[0], $horizontal[1]]
        );

        self::assertEqualsWithDelta(0.0, $equatorial[0], 1e-12);
        self::assertEqualsWithDelta(0.0, $equatorial[1], 1e-12);
    }
}