<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\DeltaT;
use SwissEph\MeanApogee;

final class MeanApogeeTest extends TestCase
{
    public function testGeocentricPositionMatchesSwissEphemerisNonutFixture(): void
    {
        $position = MeanApogee::geocentric(2451545.000738760);

        self::assertEqualsWithDelta(263.468202591210172, $position[0], 1e-12);
        self::assertEqualsWithDelta(3.419715014971115, $position[1], 1e-12);
        self::assertEqualsWithDelta(0.002710625131722546, $position[2], 1e-15);
        self::assertEqualsWithDelta(0.111325757075065, $position[3], 1e-12);
        self::assertEqualsWithDelta(-0.011026672421810, $position[4], 1e-12);
        self::assertEqualsWithDelta(0.0, $position[5], 1e-15);
    }

    public function testApparentAppliesNutation(): void
    {
        $position = MeanApogee::apparent(2451545.000738760);

        self::assertEqualsWithDelta(263.464304993936480, $position[0], 1e-12);
        self::assertEqualsWithDelta(3.419715014971115, $position[1], 1e-12);
        self::assertEqualsWithDelta(0.002710625131722546, $position[2], 1e-15);
        self::assertEqualsWithDelta(0.111328250591656, $position[3], 1e-12);
    }

    public function testApparentCanSkipNutation(): void
    {
        $geocentric = MeanApogee::geocentric(2451545.000738760);
        $apparent = MeanApogee::apparent(2451545.000738760, false);

        self::assertSame($geocentric, $apparent);
    }

    public function testGeocentricUtConvertsUtToEt(): void
    {
        $tjdUt = 2451545.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, -1);

        $ut = MeanApogee::geocentricUt($tjdUt);
        $et = MeanApogee::geocentric($tjdEt);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($et[$i], $ut[$i], 1e-12);
        }
    }
}