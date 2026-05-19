<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\DeltaT;
use SwissEph\MeanNode;

final class MeanNodeTest extends TestCase
{
    public function testGeocentricPositionMatchesSwissEphemerisNonutFixture(): void
    {
        $position = MeanNode::geocentric(2451545.000738760);

        self::assertEqualsWithDelta(125.044515924320507, $position[0], 1e-12);
        self::assertSame(0.0, $position[1]);
        self::assertEqualsWithDelta(0.002569555289799990, $position[2], 1e-15);
        self::assertEqualsWithDelta(-0.052953775025344, $position[3], 1e-12);
        self::assertSame(0.0, $position[4]);
        self::assertSame(0.0, $position[5]);
    }

    public function testApparentAppliesNutation(): void
    {
        $position = MeanNode::apparent(2451545.000738760);

        self::assertEqualsWithDelta(125.040618327046815, $position[0], 1e-12);
        self::assertSame(0.0, $position[1]);
        self::assertEqualsWithDelta(0.002569555289799990, $position[2], 1e-15);
        self::assertEqualsWithDelta(-0.052951281508753, $position[3], 1e-12);
    }

    public function testApparentCanSkipNutation(): void
    {
        $geocentric = MeanNode::geocentric(2451545.000738760);
        $apparent = MeanNode::apparent(2451545.000738760, false);

        self::assertSame($geocentric, $apparent);
    }

    public function testGeocentricUtConvertsUtToEt(): void
    {
        $tjdUt = 2451545.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, -1);

        $ut = MeanNode::geocentricUt($tjdUt);
        $et = MeanNode::geocentric($tjdEt);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($et[$i], $ut[$i], 1e-12);
        }
    }
}