<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Catalog;
use SwissEph\DeltaT;
use SwissEph\MoshierMoon;

final class MoshierMoonTest extends TestCase
{
    public function testEarthMoonBarycenterOffsetAtJ2000(): void
    {
        $offset = MoshierMoon::earthMoonBarycenterOffset(2451545.000738760);

        self::assertEqualsWithDelta(-2.369968591051043e-5, $offset[0], 1e-17);
        self::assertEqualsWithDelta(-2.166221694985313e-5, $offset[1], 1e-17);
        self::assertEqualsWithDelta(-6.161633240169068e-6, $offset[2], 1e-17);
    }

    public function testEarthMoonBarycenterOffsetAtJ2000Noon(): void
    {
        $offset = MoshierMoon::earthMoonBarycenterOffset(2451545.0);

        self::assertEqualsWithDelta(-2.370301819019669e-5, $offset[0], 1e-17);
        self::assertEqualsWithDelta(-2.165876750188861e-5, $offset[1], 1e-17);
        self::assertEqualsWithDelta(-6.160071203048817e-6, $offset[2], 1e-17);
    }

    public function testEarthMoonBarycenterOffsetAwayFromJ2000(): void
    {
        $offset = MoshierMoon::earthMoonBarycenterOffset(2341500.0);

        self::assertEqualsWithDelta(1.629500334950008e-5, $offset[0], 1e-17);
        self::assertEqualsWithDelta(-2.621314366531142e-5, $offset[1], 1e-17);
        self::assertEqualsWithDelta(-8.270780832741049e-6, $offset[2], 1e-17);
    }

    public function testGeocentricEquatorialJ2000MatchesEmbOffsetScale(): void
    {
        $tjdEt = 2451545.000738760;
        $moon = MoshierMoon::geocentricEquatorialJ2000($tjdEt);
        $offset = MoshierMoon::earthMoonBarycenterOffset($tjdEt);
        $scale = 81.30056907419062 + 1.0;

        for ($i = 0; $i <= 2; $i++) {
            self::assertEqualsWithDelta($moon[$i], $offset[$i] * $scale, 1e-18);
        }
    }

    public function testGeocentricPositionAtJ2000(): void
    {
        $position = MoshierMoon::geocentric(2451545.000738760);

        self::assertEqualsWithDelta(223.290009157557506, $position[0], 1e-12);
        self::assertEqualsWithDelta(5.200718152103959, $position[1], 1e-12);
        self::assertEqualsWithDelta(0.002690728315236186, $position[2], 1e-15);
        self::assertEqualsWithDelta(12.007422687020153, $position[3], 1e-9);
        self::assertEqualsWithDelta(-0.180748404698505, $position[4], 1e-9);
        self::assertEqualsWithDelta(0.000018267487019146, $position[5], 1e-15);
    }

    public function testGeocentricUtConvertsUtToEt(): void
    {
        $tjdUt = 2451545.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, -1);

        $ut = MoshierMoon::geocentricUt($tjdUt);
        $et = MoshierMoon::geocentric($tjdEt);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($et[$i], $ut[$i], 1e-12);
        }
    }

    public function testApparentPositionAtJ2000(): void
    {
        $position = MoshierMoon::apparent(2451545.000738760);

        self::assertEqualsWithDelta(223.282955136715003, $position[0], 1e-12);
        self::assertEqualsWithDelta(5.200277522346328, $position[1], 1e-12);
        self::assertEqualsWithDelta(0.002690728315236, $position[2], 1e-15);
        self::assertEqualsWithDelta(12.006403060420537, $position[3], 1e-9);
        self::assertEqualsWithDelta(-0.180673425348698, $position[4], 1e-9);
        self::assertEqualsWithDelta(0.000018267486010, $position[5], 1e-15);
    }

    public function testApparentCanDisableCorrectionLayers(): void
    {
        $position = MoshierMoon::apparent(
            2451545.000738760,
            false,
            true,
            false
        );

        self::assertEqualsWithDelta(223.290009156228848, $position[0], 1e-12);
        self::assertEqualsWithDelta(5.200718152181578, $position[1], 1e-12);
        self::assertEqualsWithDelta(0.002690728315236186, $position[2], 1e-15);
    }

    public function testApparentUtConvertsUtToEt(): void
    {
        $tjdUt = 2451545.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, -1);

        $ut = MoshierMoon::apparentUt($tjdUt);
        $et = MoshierMoon::apparent($tjdEt);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($et[$i], $ut[$i], 1e-12);
        }
    }

    public function testApparentResultWrapsPosition(): void
    {
        $position = MoshierMoon::apparent(2451545.000738760);
        $result = MoshierMoon::apparentResult(2451545.000738760);

        self::assertSame(Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH, $result->rc);
        self::assertSame($position, $result->xx);
    }
}