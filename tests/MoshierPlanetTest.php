<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\DeltaT;
use SwissEph\MoshierPlanet;
use SwissEph\MoshierPlanetTables;

final class MoshierPlanetTest extends TestCase
{
    public function testEarthHeliocentricPositionMatchesGeneratedMoshierTable(): void
    {
        $position = MoshierPlanet::heliocentric(MoshierPlanetTables::EARTH, 2451545.000738760);

        self::assertEqualsWithDelta(100.380169688532177, $position[0], 1e-12);
        self::assertEqualsWithDelta(-0.000058803823730, $position[1], 1e-12);
        self::assertEqualsWithDelta(0.983309954340838, $position[2], 1e-12);

        self::assertEqualsWithDelta(1.019207417911616, $position[3], 1e-10);
        self::assertEqualsWithDelta(0.000001583029429, $position[4], 1e-12);
        self::assertEqualsWithDelta(-0.000012724543863, $position[5], 1e-12);
    }

    public function testHeliocentricWithoutSpeedReturnsFirstThreeValues(): void
    {
        $position = MoshierPlanet::heliocentric(MoshierPlanetTables::EARTH, 2451545.000738760);
        $withoutSpeed = MoshierPlanet::heliocentricWithoutSpeed(MoshierPlanetTables::EARTH, 2451545.000738760);

        self::assertSame($position[0], $withoutSpeed[0]);
        self::assertSame($position[1], $withoutSpeed[1]);
        self::assertSame($position[2], $withoutSpeed[2]);
    }

    public function testHeliocentricUtConvertsUtToEt(): void
    {
        $tjdUt = 2451545.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, -1);

        $et = MoshierPlanet::heliocentric(MoshierPlanetTables::EARTH, $tjdEt);
        $ut = MoshierPlanet::heliocentricUt(MoshierPlanetTables::EARTH, $tjdUt);

        self::assertEqualsWithDelta($et[0], $ut[0], 1e-12);
        self::assertEqualsWithDelta($et[1], $ut[1], 1e-12);
        self::assertEqualsWithDelta($et[2], $ut[2], 1e-12);
        self::assertEqualsWithDelta($et[3], $ut[3], 1e-12);
        self::assertEqualsWithDelta($et[4], $ut[4], 1e-12);
        self::assertEqualsWithDelta($et[5], $ut[5], 1e-12);
    }

    public function testOtherPlanetTablesReturnUsablePositions(): void
    {
        $mercury = MoshierPlanet::heliocentric(MoshierPlanetTables::MERCURY, 2451545.000738760);
        $venus = MoshierPlanet::heliocentric(MoshierPlanetTables::VENUS, 2451545.000738760);
        $mars = MoshierPlanet::heliocentric(MoshierPlanetTables::MARS, 2451545.000738760);
        $pluto = MoshierPlanet::heliocentric(MoshierPlanetTables::PLUTO, 2451545.000738760);

        self::assertEqualsWithDelta(253.784949016482, $mercury[0], 1e-12);
        self::assertEqualsWithDelta(0.466471713567, $mercury[2], 1e-12);

        self::assertEqualsWithDelta(182.604114272314, $venus[0], 1e-12);
        self::assertEqualsWithDelta(0.720212900185, $venus[2], 1e-12);

        self::assertEqualsWithDelta(359.447765387009, $mars[0], 1e-12);
        self::assertEqualsWithDelta(1.391207792261, $mars[2], 1e-12);

        self::assertEqualsWithDelta(250.546157204385, $pluto[0], 1e-12);
        self::assertEqualsWithDelta(30.223275152012, $pluto[2], 1e-12);
    }

    public function testUnknownPlanetThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        MoshierPlanet::heliocentric(999, 2451545.0);
    }

    public function testHeliocentricThrowsOutsideMoshierPlanetRange(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('jd 624999.000000 outside Moshier planet range 625000.50 .. 2818000.50');

        MoshierPlanet::heliocentric(MoshierPlanetTables::EARTH, 624999.0);
    }
}