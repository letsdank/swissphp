<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\EclipticNutation;

final class EclipticNutationTest extends TestCase
{
    public function testApplyNutationToSunApparentEclipticPosition(): void
    {
        $position = [
            280.372787619036,
            0.000231516499,
            0.983327644818,
            1.019393734048,
            -0.000006394083,
            -0.000007356975,
        ];

        $result = EclipticNutation::apply($position, 2451545.000738760);

        self::assertEqualsWithDelta(280.368890021762297, $result[0], 1e-12);
        self::assertEqualsWithDelta(0.000231516499, $result[1], 1e-15);
        self::assertEqualsWithDelta(0.983327644818, $result[2], 1e-15);
        self::assertEqualsWithDelta(1.019396227564591, $result[3], 1e-12);
        self::assertEqualsWithDelta(-0.000006394083, $result[4], 1e-15);
        self::assertEqualsWithDelta(-0.000007356975, $result[5], 1e-15);
    }

    public function testApplyNutationToMercuryApparentEclipticPosition(): void
    {
        $position = [
            271.893143606859724,
            -0.994826814207524,
            1.415469439273374,
            1.556221870023774,
            -0.097502946397082,
            0.004617585896809,
        ];

        $result = EclipticNutation::apply($position, 2451545.000738760);

        self::assertEqualsWithDelta(271.889246009586032, $result[0], 1e-12);
        self::assertEqualsWithDelta(-0.994826814207524, $result[1], 1e-15);
        self::assertEqualsWithDelta(1.415469439273374, $result[2], 1e-15);
        self::assertEqualsWithDelta(1.556224363540365, $result[3], 1e-12);
        self::assertEqualsWithDelta(-0.097502946397082, $result[4], 1e-15);
        self::assertEqualsWithDelta(0.004617585896809, $result[5], 1e-15);
    }

    public function testApplyWithoutSpeedLeavesSpeedUnchanged(): void
    {
        $position = [280.372787619036, 0.0, 1.0, 1.019393734048, 0.0, 0.0];

        $result = EclipticNutation::apply($position, 2451545.000738760, false);

        self::assertEqualsWithDelta(280.368890021762297, $result[0], 1e-12);
        self::assertSame(1.019393734048, $result[3]);
    }
}