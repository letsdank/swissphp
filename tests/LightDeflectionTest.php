<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Catalog;
use SwissEph\Coordinates;
use SwissEph\EarthPosition;
use SwissEph\LightDeflection;
use SwissEph\PlanetPosition;

final class LightDeflectionTest extends TestCase
{
    public function testSolarDeflectionForMercuryRectangularPosition(): void
    {
        $tjdEt = 2451545.000738760;

        $position = self::toCartesian(PlanetPosition::geocentricLightTime(Catalog::SE_MERCURY, $tjdEt));
        $earth = self::toCartesian(EarthPosition::heliocentric($tjdEt));

        $deflected = LightDeflection::solar($position, $earth);

        self::assertEqualsWithDelta(0.046895174628007, $deflected[0], 1e-12);
        self::assertEqualsWithDelta(-1.414478914505944, $deflected[1], 1e-12);
        self::assertEqualsWithDelta(-0.024575933991884, $deflected[2], 1e-12);
        self::assertEqualsWithDelta(0.038571113913309, $deflected[3], 1e-12);
        self::assertEqualsWithDelta(-0.003298807859141, $deflected[4], 1e-12);
        self::assertEqualsWithDelta(-0.002488550038770, $deflected[5], 1e-12);
    }

    public function testSolarDeflectionForMercuryPolarPosition(): void
    {
        $tjdEt = 2451545.000738760;

        $position = self::toCartesian(PlanetPosition::geocentricLightTime(Catalog::SE_MERCURY, $tjdEt));
        $earth = self::toCartesian(EarthPosition::heliocentric($tjdEt));

        $polar = self::fromCartesian(LightDeflection::solar($position, $earth));

        self::assertEqualsWithDelta(271.898870148325443, $polar[0], 1e-12);
        self::assertEqualsWithDelta(-0.994841719380015, $polar[1], 1e-12);
        self::assertEqualsWithDelta(1.415469439273374, $polar[2], 1e-12);
        self::assertEqualsWithDelta(1.556245303687880, $polar[3], 1e-12);
        self::assertEqualsWithDelta(-0.097501703415915, $polar[4], 1e-12);
        self::assertEqualsWithDelta(0.004617585899393, $polar[5], 1e-12);
    }

    public function testSolarDeflectionWithoutSpeedLeavesSpeedUncorrected(): void
    {
        $tjdEt = 2451545.000738760;

        $position = self::toCartesian(PlanetPosition::geocentricLightTime(Catalog::SE_VENUS, $tjdEt));
        $earth = self::toCartesian(EarthPosition::heliocentric($tjdEt));

        $deflected = LightDeflection::solar($position, $earth, false);

        self::assertEqualsWithDelta(-0.541159025045841, $deflected[0], 1e-12);
        self::assertEqualsWithDelta(-0.999775526981875, $deflected[1], 1e-12);
        self::assertEqualsWithDelta(0.041019932381141, $deflected[2], 1e-12);

        self::assertSame($position[3], $deflected[3]);
        self::assertSame($position[4], $deflected[4]);
        self::assertSame($position[5], $deflected[5]);
    }

    private static function toCartesian(array $position): array
    {
        $position[0] = deg2rad($position[0]);
        $position[1] = deg2rad($position[1]);
        $position[3] = deg2rad($position[3]);
        $position[4] = deg2rad($position[4]);

        return Coordinates::polcartSp($position);
    }

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
}