<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\DeltaT;
use SwissEph\OsculatingApogee;

final class OsculatingApogeeTest extends TestCase
{
    public function testGeocentricPositionFromCurrentMoshierMoonSeries(): void
    {
        $position = OsculatingApogee::geocentric(2451545.000738760);

        self::assertEqualsWithDelta(251.818034823137253, $position[0], 1e-12);
        self::assertEqualsWithDelta(4.159213477865179, $position[1], 1e-12);
        self::assertEqualsWithDelta(0.002713098318416487, $position[2], 1e-15);
        self::assertEqualsWithDelta(0.826077889936902, $position[3], 1e-12);
        self::assertEqualsWithDelta(-0.064433880842601, $position[4], 1e-12);
        self::assertEqualsWithDelta(-0.000000534722706, $position[5], 1e-15);
    }

    public function testApparentAppliesNutation(): void
    {
        $position = OsculatingApogee::apparent(2451545.000738760);

        self::assertEqualsWithDelta(251.814137225863561, $position[0], 1e-12);
        self::assertEqualsWithDelta(4.159213477865179, $position[1], 1e-12);
        self::assertEqualsWithDelta(0.002713098318416487, $position[2], 1e-15);
        self::assertEqualsWithDelta(0.826080383453493, $position[3], 1e-12);
    }

    public function testApparentCanSkipNutation(): void
    {
        $geocentric = OsculatingApogee::geocentric(2451545.000738760);
        $apparent = OsculatingApogee::apparent(2451545.000738760, false);

        self::assertSame($geocentric, $apparent);
    }

    public function testGeocentricUtConvertsUtToEt(): void
    {
        $tjdUt = 2451545.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, -1);

        $ut = OsculatingApogee::geocentricUt($tjdUt);
        $et = OsculatingApogee::geocentric($tjdEt);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($et[$i], $ut[$i], 1e-12);
        }
    }
}