<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Calculator;
use SwissEph\Catalog;
use SwissEph\OrbitalElements;
use SwissEph\SwissDate;

final class OrbitalElementsResultTest extends TestCase
{
    public function testOrbitalElementsResultWrapsArrayResult(): void
    {
        $array = OrbitalElements::get(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        $result = OrbitalElements::getResult(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        self::assertSame($array, $result->toArray());
        self::assertTrue($result->isOk());
        self::assertFalse($result->hasError());

        self::assertEqualsWithDelta(0.387098400612, $result->semiMajorAxis(), 1e-12);
        self::assertEqualsWithDelta(0.205630087362, $result->eccentricity(), 1e-12);
        self::assertEqualsWithDelta(7.005094775744, $result->inclination(), 1e-12);
        self::assertEqualsWithDelta(48.330747832153, $result->ascendingNodeLongitude(), 1e-12);
        self::assertEqualsWithDelta(29.125393460178, $result->argumentOfPeriapsis(), 1e-12);
        self::assertEqualsWithDelta(77.456141292331, $result->periapsisLongitude(), 1e-12);
        self::assertEqualsWithDelta(252.253083733414, $result->meanLongitude(), 1e-12);
        self::assertEqualsWithDelta(4.092343165108, $result->meanDailyMotion(), 1e-12);
        self::assertEqualsWithDelta(0.307499322676, $result->perihelionDistance(), 1e-12);
        self::assertEqualsWithDelta(0.466697478547, $result->aphelionDistance(), 1e-12);
    }

    public function testOrbitalElementsUtResultWrapsArrayResult(): void
    {
        $array = OrbitalElements::getUt(
            2451545.0,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        $result = OrbitalElements::getUtResult(
            2451545.0,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        self::assertSame($array, $result->toArray());
        self::assertTrue($result->isOk());
    }

    public function testOrbitalElementsResultCanRepresentError(): void
    {
        $result = OrbitalElements::getResult(
            2451545.0,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED
        );

        self::assertSame(SwissDate::ERR, $result->rc);
        self::assertFalse($result->isOk());
        self::assertTrue($result->hasError());
        self::assertStringContainsString('not implemented', $result->error);
    }

    public function testCalculatorOrbitalElementsResultDelegatesToOrbitalElements(): void
    {
        $expected = OrbitalElements::getResult(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        $actual = Calculator::getOrbitalElementsResult(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        self::assertSame($expected->toArray(), $actual->toArray());
    }
}