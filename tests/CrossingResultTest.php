<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Calculator;
use SwissEph\Catalog;
use SwissEph\Crossing;
use SwissEph\SwissDate;

final class CrossingResultTest extends TestCase
{
    public function testHelioCrossResultWrapsArrayResult(): void
    {
        $array = Crossing::helioCross(
            Catalog::SE_MERCURY,
            270.0,
            2451545.0,
            Catalog::SEFLG_SPEED,
            1
        );

        $result = Crossing::helioCrossResult(
            Catalog::SE_MERCURY,
            270.0,
            2451545.0,
            Catalog::SEFLG_SPEED,
            1
        );

        self::assertSame($array, $result->toArray());
        self::assertTrue($result->isOk());
        self::assertFalse($result->hasError());
        self::assertEqualsWithDelta(2451550.8826084435, $result->tjd, 1e-9);
    }

    public function testHelioCrossUtResultWrapsArrayResult(): void
    {
        $array = Crossing::helioCrossUt(
            Catalog::SE_MERCURY,
            270.0,
            2451545.0,
            Catalog::SEFLG_SPEED,
            1
        );

        $result = Crossing::helioCrossUtResult(
            Catalog::SE_MERCURY,
            270.0,
            2451545.0,
            Catalog::SEFLG_SPEED,
            1
        );

        self::assertSame($array, $result->toArray());
        self::assertTrue($result->isOk());
    }

    public function testHelioCrossResultCanRepresentError(): void
    {
        $result = Crossing::helioCrossResult(
            Catalog::SE_MOON,
            30.0,
            2451545.0,
            Catalog::SEFLG_SPEED,
            1
        );

        self::assertSame(SwissDate::ERR, $result->rc);
        self::assertLessThan(2451545.0, $result->tjd);
        self::assertFalse($result->isOk());
        self::assertTrue($result->hasError());
        self::assertStringContainsString('not possible', $result->error);
    }

    public function testCalculatorHelioCrossResultDelegatesToCrossing(): void
    {
        $expected = Crossing::helioCrossResult(
            Catalog::SE_MERCURY,
            270.0,
            2451545.0,
            Catalog::SEFLG_SPEED,
            1
        );

        $actual = Calculator::helioCrossResult(
            Catalog::SE_MERCURY,
            270.0,
            2451545.0,
            Catalog::SEFLG_SPEED,
            1
        );

        self::assertSame($expected->toArray(), $actual->toArray());
    }
}