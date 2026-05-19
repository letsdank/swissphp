<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Calculator;
use SwissEph\Catalog;
use SwissEph\FixedStars;
use SwissEph\SwissDate;

final class FixedStarResultTest extends TestCase
{
    public function testFixedStarResultWrapsArrayResult(): void
    {
        $array = FixedStars::fixstar(
            'Sirius',
            2451545.0,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_J2000
        );

        $result = FixedStars::fixstarResult(
            'Sirius',
            2451545.0,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_J2000
        );

        self::assertSame($array, $result->toArray());
        self::assertTrue($result->isOk());
        self::assertFalse($result->hasError());
        self::assertSame('Sirius', $result->star);
        self::assertEqualsWithDelta(104.081662153609, $result->longitude(), 1e-12);
        self::assertEqualsWithDelta(-39.605237221305, $result->latitude(), 1e-12);
        self::assertEqualsWithDelta(543932.929635558161, $result->distance(), 1e-6);
    }

    public function testFixedStarUtResultWrapsArrayResult(): void
    {
        $array = FixedStars::fixstarUt(
            'Sirius',
            2451545.0,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_J2000
        );

        $result = FixedStars::fixstarUtResult(
            'Sirius',
            2451545.0,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_J2000
        );

        self::assertSame($array, $result->toArray());
        self::assertTrue($result->isOk());
    }

    public function testFixedStarResultCanRepresentError(): void
    {
        $result = FixedStars::fixstarResult(
            'Unknown Star',
            2451545.0,
            Catalog::SEFLG_SPEED
        );

        self::assertSame(SwissDate::ERR, $result->rc);
        self::assertFalse($result->isOk());
        self::assertTrue($result->hasError());
        self::assertStringContainsString('not found', $result->error);
    }

    public function testFixedStarMagnitudeResultWrapsArrayResult(): void
    {
        $array = FixedStars::fixstarMagnitude('Sirius');
        $result = FixedStars::fixstarMagnitudeResult('Sirius');

        self::assertSame($array, $result->toArray());
        self::assertTrue($result->isOk());
        self::assertFalse($result->hasError());
        self::assertSame('Sirius', $result->star);
        self::assertEqualsWithDelta(-1.46, $result->mag, 1e-12);
    }

    public function testFixedStarMagnitudeResultCanRepresentError(): void
    {
        $result = FixedStars::fixstarMagnitudeResult('Unknown Star');

        self::assertSame(SwissDate::ERR, $result->rc);
        self::assertFalse($result->isOk());
        self::assertTrue($result->hasError());
        self::assertStringContainsString('not found', $result->error);
    }

    public function testCalculatorFixedStarResultDelegatesToFixedStars(): void
    {
        $expected = FixedStars::fixstarResult(
            'Sirius',
            2451545.0,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_J2000
        );

        $actual = Calculator::fixstarResult(
            'Sirius',
            2451545.0,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_J2000
        );

        self::assertSame($expected->toArray(), $actual->toArray());
    }

    public function testCalculatorFixedStarMagnitudeResultDelegatesToFixedStars(): void
    {
        $expected = FixedStars::fixstarMagnitudeResult('Sirius');
        $actual = Calculator::fixstarMagnitudeResult('Sirius');

        self::assertSame($expected->toArray(), $actual->toArray());
    }
}