<?php

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Precession;

final class PrecessionTest extends TestCase
{
    public function testIau1976PrecessesFromJ2000ToB1950LikeSwissEphemeris(): void
    {
        $x = Precession::precess(
            [1.0, 0.0, 0.0],
            2433282.42345905,
            Precession::DIRECTION_FROM_J2000,
            Precession::MODEL_IAU_1976
        );

        self::assertEqualsWithDelta(0.999925707952363, $x[0], 1e-14);
        self::assertEqualsWithDelta(-0.011178938137770, $x[1], 1e-14);
        self::assertEqualsWithDelta(-0.004859003815359, $x[2], 1e-14);
    }

    public function testNewcombPrecessesFromJ2000ToJ1900LikeSwissEphemeris(): void
    {
        $x = Precession::precess(
            [1.0, 0.0, 0.0],
            2415020.0,
            Precession::DIRECTION_FROM_J2000,
            Precession::MODEL_NEWCOMB
        );

        self::assertEqualsWithDelta(0.999703043572829, $x[0], 1e-14);
        self::assertEqualsWithDelta(-0.022347723503148, $x[1], 1e-14);
        self::assertEqualsWithDelta(-0.009716168249323, $x[2], 1e-14);
    }

    public function testPrecessionRoundTrip(): void
    {
        $date = 2469807.5;
        $original = [0.3, -0.4, 0.8660254037844386];

        $precessed = Precession::precess(
            $original,
            $date,
            Precession::DIRECTION_FROM_J2000,
            Precession::MODEL_IAU_1976
        );

        $back = Precession::precess(
            $precessed,
            $date,
            Precession::DIRECTION_TO_J2000,
            Precession::MODEL_IAU_1976
        );

        self::assertEqualsWithDelta($original[0], $back[0], 1e-14);
        self::assertEqualsWithDelta($original[1], $back[1], 1e-14);
        self::assertEqualsWithDelta($original[2], $back[2], 1e-14);
    }
}