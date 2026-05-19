<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\DeltaT;
use SwissEph\TrueNode;

final class TrueNodeTest extends TestCase
{
    public function testGeocentricPositionFromCurrentMoshierMoonSeries(): void
    {
        $position = TrueNode::geocentric(2451545.000738760);

        self::assertEqualsWithDelta(123.822907760507434, $position[0], 1e-12);
        self::assertSame(0.0, $position[1]);
        self::assertEqualsWithDelta(0.002441788475869266, $position[2], 1e-15);
        self::assertEqualsWithDelta(-0.396911451464843, $position[3], 1e-12);
        self::assertSame(0.0, $position[4]);
        self::assertEqualsWithDelta(0.000016632720706264, $position[5], 1e-15);
    }

    public function testApparentAppliesNutation(): void
    {
        $position = TrueNode::apparent(2451545.000738760);

        self::assertEqualsWithDelta(123.819010163233742, $position[0], 1e-12);
        self::assertSame(0.0, $position[1]);
        self::assertEqualsWithDelta(0.002441788475869266, $position[2], 1e-15);
        self::assertEqualsWithDelta(-0.396908957948252, $position[3], 1e-12);
    }

    public function testApparentCanSkipNutation(): void
    {
        $geocentric = TrueNode::geocentric(2451545.000738760);
        $apparent = TrueNode::apparent(2451545.000738760, false);

        self::assertSame($geocentric, $apparent);
    }

    public function testGeocentricUtConvertsUtToEt(): void
    {
        $tjdUt = 2451545.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, -1);

        $ut = TrueNode::geocentricUt($tjdUt);
        $et = TrueNode::geocentric($tjdEt);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($et[$i], $ut[$i], 1e-12);
        }
    }
}