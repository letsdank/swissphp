<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\DeltaT;
use SwissEph\EarthPosition;

final class EarthPositionTest extends TestCase
{
    public function testHeliocentricPositionUsesMoshierEmbCorrection(): void
    {
        $position = EarthPosition::heliocentric(2451545.000738760);

        self::assertEqualsWithDelta(100.378576987452021, $position[0], 1e-12);
        self::assertEqualsWithDelta(-0.000231480184610, $position[1], 1e-12);
        self::assertEqualsWithDelta(0.983327644818223, $position[2], 1e-12);

        self::assertEqualsWithDelta(1.019393782669908, $position[3], 1e-10);
        self::assertEqualsWithDelta(0.000006394499740, $position[4], 1e-12);
        self::assertEqualsWithDelta(-0.000007356974807, $position[5], 1e-12);
    }

    public function testHeliocentricUtConvertsUtToEt(): void
    {
        $tjdUt = 2451545.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, -1);

        $et = EarthPosition::heliocentric($tjdEt);
        $ut = EarthPosition::heliocentricUt($tjdUt);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($et[$i], $ut[$i], 1e-12);
        }
    }
}