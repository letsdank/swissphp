<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Angle;
use SwissEph\Calculator;
use SwissEph\Catalog;
use SwissEph\Crossing;
use SwissEph\DeltaT;
use SwissEph\PlanetPosition;
use SwissEph\SwissDate;

final class CrossingTest extends TestCase
{
    public function testSolcrossFindsNextSunLongitudeCrossing(): void
    {
        $crossing = Crossing::solcross(
            0.0,
            2451545.0,
            Catalog::SEFLG_SPEED
        );

        self::assertEqualsWithDelta(2451623.8199639, $crossing, 1e-7);

        $position = Calculator::calcApparentFlags($crossing, Catalog::SE_SUN, Catalog::SEFLG_SPEED);
        self::assertEqualsWithDelta(0.0, Angle::difdeg2n($position['xx'][0], 0.0), 1e-8);
    }

    public function testSolcrossUtReturnsUtCrossing(): void
    {
        $crossingUt = Crossing::solcrossUt(
            0.0,
            2451545.0,
            Catalog::SEFLG_SPEED
        );

        $crossingEt = Crossing::solcross(
            0.0,
            2451545.0 + DeltaT::deltatEx(2451545.0, Catalog::SEFLG_SPEED),
            Catalog::SEFLG_SPEED
        );

        self::assertEqualsWithDelta(
            $crossingEt - DeltaT::deltatEx($crossingEt, Catalog::SEFLG_SPEED),
            $crossingUt,
            1e-12
        );
    }

    public function testMooncrossFindsNextMoonLongitudeCrossing(): void
    {
        $crossing = Crossing::mooncross(
            225.0,
            2451545.0,
            Catalog::SEFLG_SPEED
        );

        self::assertEqualsWithDelta(2451545.1438658, $crossing, 1e-7);

        $position = Calculator::calcApparentFlags($crossing, Catalog::SE_MOON, Catalog::SEFLG_SPEED);
        self::assertEqualsWithDelta(0.0, Angle::difdeg2n($position['xx'][0], 225.0), 1e-8);
    }

    public function testMooncrossUtReturnsUtCrossing(): void
    {
        $crossingUt = Crossing::mooncrossUt(
            225.0,
            2451545.0,
            Catalog::SEFLG_SPEED
        );

        $crossingEt = Crossing::mooncross(
            225.0,
            2451545.0 + DeltaT::deltatEx(2451545.0, Catalog::SEFLG_SPEED),
            Catalog::SEFLG_SPEED
        );

        self::assertEqualsWithDelta(
            $crossingEt - DeltaT::deltatEx($crossingEt, Catalog::SEFLG_SPEED),
            $crossingUt,
            1e-12
        );
    }

    public function testSolcrossReturnsEarlierTimeOnError(): void
    {
        $crossing = Crossing::solcross(
            0.0,
            2818001.0,
            Catalog::SEFLG_SPEED
        );

        self::assertLessThan(2818001.0, $crossing);
    }

    public function testSolcrossCanUseEquatorialRightAscension(): void
    {
        $crossing = Crossing::solcross(
            0.0,
            2451545.0,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_EQUATORIAL
        );

        self::assertEqualsWithDelta(2451623.8199935, $crossing, 1e-7);

        $position = Calculator::calcApparentFlags(
            $crossing,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_EQUATORIAL
        );

        self::assertEqualsWithDelta(0.0, Angle::difdeg2n($position['xx'][0], 0.0), 1e-8);
    }

    public function testMooncrossCanUseEquatorialRightAscension(): void
    {
        $crossing = Crossing::mooncross(
            225.0,
            2451545.0,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_EQUATORIAL
        );

        self::assertEqualsWithDelta(2451545.2230594, $crossing, 1e-7);

        $position = Calculator::calcApparentFlags(
            $crossing,
            Catalog::SE_MOON,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_EQUATORIAL
        );

        self::assertEqualsWithDelta(0.0, Angle::difdeg2n($position['xx'][0], 225.0), 1e-8);
    }

    public function testMooncrossNodeFindsNextEclipticLatitudeCrossing(): void
    {
        $crossing = Crossing::mooncrossNode(
            2451545.0,
            Catalog::SEFLG_SPEED
        );

        self::assertEqualsWithDelta(2451551.7906600, $crossing['tjd'], 1e-7);
        self::assertEqualsWithDelta(304.1151392975, $crossing['longitude'], 1e-9);
        self::assertEqualsWithDelta(0.0, $crossing['latitude'], 1e-8);
    }

    public function testMooncrossNodeUtReturnsUtCrossing(): void
    {
        $crossingUt = Crossing::mooncrossNodeUt(
            2451545.0,
            Catalog::SEFLG_SPEED
        );

        $crossingEt = Crossing::mooncrossNode(
            2451545.0 + DeltaT::deltatEx(2451545.0, Catalog::SEFLG_SPEED),
            Catalog::SEFLG_SPEED
        );

        self::assertEqualsWithDelta(
            $crossingEt['tjd'] - DeltaT::deltatEx($crossingEt['tjd'], Catalog::SEFLG_SPEED),
            $crossingUt['tjd'],
            1e-12
        );
        self::assertEqualsWithDelta($crossingEt['longitude'], $crossingUt['longitude'], 1e-9);
        self::assertEqualsWithDelta($crossingEt['latitude'], $crossingUt['latitude'], 1e-9);
    }

    public function testHelioCrossFindsForwardPlanetLongitudeCrossing(): void
    {
        $crossing = Crossing::helioCross(
            Catalog::SE_MERCURY,
            270.0,
            2451545.0,
            Catalog::SEFLG_SPEED,
            1
        );

        self::assertSame(SwissDate::OK, $crossing['rc']);
        self::assertSame('', $crossing['error']);
        self::assertEqualsWithDelta(2451550.8826084435, $crossing['tjd'], 1e-9);

        $position = PlanetPosition::heliocentric(Catalog::SE_MERCURY, $crossing['tjd']);
        self::assertEqualsWithDelta(0.0, Angle::difdeg2n($position[0], 270.0), 1e-8);
    }

    public function testHelioCrossFindsBackwardPlanetLongitudeCrossing(): void
    {
        $crossing = Crossing::helioCross(
            Catalog::SE_MERCURY,
            250.0,
            2451545.0,
            Catalog::SEFLG_SPEED,
            -1
        );

        self::assertSame(SwissDate::OK, $crossing['rc']);
        self::assertEqualsWithDelta(2451543.623327196, $crossing['tjd'], 1e-9);

        $position = PlanetPosition::heliocentric(Catalog::SE_MERCURY, $crossing['tjd']);
        self::assertEqualsWithDelta(0.0, Angle::difdeg2n($position[0], 250.0), 1e-7);
    }

    public function testHelioCrossUtReturnsUtCrossing(): void
    {
        $crossingUt = Crossing::helioCrossUt(
            Catalog::SE_MERCURY,
            270.0,
            2451545.0,
            Catalog::SEFLG_SPEED,
            1
        );

        $crossingEt = Crossing::helioCross(
            Catalog::SE_MERCURY,
            270.0,
            2451545 + DeltaT::deltatEx(2451545.0, Catalog::SEFLG_SPEED),
            Catalog::SEFLG_SPEED,
            1
        );

        self::assertSame(SwissDate::OK, $crossingUt['rc']);
        self::assertEqualsWithDelta(
            $crossingEt['tjd'] - DeltaT::deltatEx($crossingEt['tjd'], Catalog::SEFLG_SPEED),
            $crossingUt['tjd'],
            1e-12
        );
    }

    public function testHelioCrossMapsPlutoAsteroidNumber(): void
    {
        $expected = Crossing::helioCross(
            Catalog::SE_PLUTO,
            30.0,
            2451545.0,
            Catalog::SEFLG_SPEED,
            1
        );

        $actual = Crossing::helioCross(
            Catalog::SE_AST_OFFSET + 134340,
            30.0,
            2451545.0,
            Catalog::SEFLG_SPEED,
            1
        );

        self::assertSame($expected, $actual);
    }

    public function testHelioCrossRejectsUnsupportedBody(): void
    {
        $crossing = Crossing::helioCross(
            Catalog::SE_MOON,
            30.0,
            2451545.0,
            Catalog::SEFLG_SPEED,
            1
        );

        self::assertSame(SwissDate::ERR, $crossing['rc']);
        self::assertLessThan(2451545.0, $crossing['tjd']);
        self::assertStringContainsString('not possible', $crossing['error']);
    }
}