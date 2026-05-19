<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Calculator;
use SwissEph\Catalog;
use SwissEph\OrbitalElements;
use SwissEph\SwissDate;

final class OrbitDistanceResultTest extends TestCase
{
    public function testOrbitDistanceResultWrapsArrayResult(): void
    {
        $array = OrbitalElements::maxMinTrueDistance(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        $result = OrbitalElements::maxMinTrueDistanceResult(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        self::assertSame($array, $result->toArray());
        self::assertTrue($result->isOk());
        self::assertFalse($result->hasError());
        self::assertEqualsWithDelta(0.466697478547, $result->aphelionDistance(), 1e-12);
        self::assertEqualsWithDelta(0.307499322676, $result->perihelionDistance(), 1e-12);
        self::assertEqualsWithDelta(0.466471713567, $result->currentDistance(), 1e-12);
        self::assertGreaterThan(0.99, $result->relativeDistance());
        self::assertLessThan(1.0, $result->relativeDistance());
    }

    public function testOrbitDistanceUtResultWrapsArrayResult(): void
    {
        $array = OrbitalElements::maxMinTrueDistanceUt(
            2451545.0,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        $result = OrbitalElements::maxMinTrueDistanceUtResult(
            2451545.0,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        self::assertSame($array, $result->toArray());
        self::assertTrue($result->isOk());
    }

    public function testOrbitDistanceResultCanRepresentError(): void
    {
        $result = OrbitalElements::maxMinTrueDistanceResult(
            2451545.0,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED
        );

        self::assertSame(SwissDate::ERR, $result->rc);
        self::assertFalse($result->isOk());
        self::assertTrue($result->hasError());
        self::assertStringContainsString('not implemented', $result->error);
    }

    public function testCalculatorOrbitDistanceResultDelegatesToOrbitalElements(): void
    {
        $expected = OrbitalElements::maxMinTrueDistanceResult(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        $actual = Calculator::orbitMaxMinTrueDistanceResult(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        self::assertSame($expected->toArray(), $actual->toArray());
    }
}