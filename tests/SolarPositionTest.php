<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Catalog;
use SwissEph\DeltaT;
use SwissEph\SolarPosition;

final class SolarPositionTest extends TestCase
{
    public function testPositionUsesMoshierEarthPosition(): void
    {
        $position = SolarPosition::position(2451545.000738760);

        self::assertEqualsWithDelta(280.378576987452021, $position[0], 1e-12);
        self::assertEqualsWithDelta(0.000231480184610, $position[1], 1e-12);
        self::assertEqualsWithDelta(0.983327644818223, $position[2], 1e-12);

        self::assertEqualsWithDelta(1.019393782669908, $position[3], 1e-10);
        self::assertEqualsWithDelta(-0.000006394499740, $position[4], 1e-12);
        self::assertEqualsWithDelta(-0.000007356974807, $position[5], 1e-12);
    }

    public function testPositionUtConvertsUtToEt(): void
    {
        $tjdUt = 2451545.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, -1);

        $et = SolarPosition::position($tjdEt);
        $ut = SolarPosition::positionUt($tjdUt);

        self::assertEqualsWithDelta($et[0], $ut[0], 1e-12);
        self::assertEqualsWithDelta($et[2], $ut[2], 1e-12);
        self::assertEqualsWithDelta($et[3], $ut[3], 1e-12);
    }

    public function testLongitudeReturnsPositionLongitude(): void
    {
        self::assertEqualsWithDelta(
            SolarPosition::position(2451545.000738760)[0],
            SolarPosition::longitude(2451545.000738760),
            1e-12
        );
    }

    public function testLongitudeUtConvertsUtToEt(): void
    {
        $tjdUt = 2451545.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, -1);

        self::assertEqualsWithDelta(
            SolarPosition::longitude($tjdEt),
            SolarPosition::longitudeUt($tjdUt),
            1e-12
        );
    }

    public function testApparentAppliesAberrationAndNutation(): void
    {
        $position = SolarPosition::apparent(2451545.000738760);

        self::assertEqualsWithDelta(280.368890021761956, $position[0], 1e-12);
        self::assertEqualsWithDelta(0.000231516499242, $position[1], 1e-12);
        self::assertEqualsWithDelta(0.983327644818224, $position[2], 1e-12);
        self::assertEqualsWithDelta(1.019396227564636, $position[3], 1e-10);
        self::assertEqualsWithDelta(-0.000006394083261, $position[4], 1e-12);
        self::assertEqualsWithDelta(-0.000007356974906, $position[5], 1e-12);
    }

    public function testApparentCanSkipNutation(): void
    {
        $position = SolarPosition::apparent(2451545.000738760, false);

        self::assertEqualsWithDelta(280.372787619035648, $position[0], 1e-12);
        self::assertEqualsWithDelta(0.000231516499242, $position[1], 1e-12);
        self::assertEqualsWithDelta(0.983327644818224, $position[2], 1e-12);
        self::assertEqualsWithDelta(1.019393734048045, $position[3], 1e-10);
        self::assertEqualsWithDelta(-0.000006394083261, $position[4], 1e-12);
        self::assertEqualsWithDelta(-0.000007356974906, $position[5], 1e-12);
    }

    public function testApparentUtConvertsUtToEt(): void
    {
        $tjdUt = 2451545.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, -1);

        $et = SolarPosition::apparent($tjdEt);
        $ut = SolarPosition::apparentUt($tjdUt);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($et[$i], $ut[$i], 1e-12);
        }
    }

    public function testApparentResultWrapsApparentPosition(): void
    {
        $position = SolarPosition::apparent(2451545.000738760);
        $result = SolarPosition::apparentResult(2451545.000738760);

        self::assertSame(Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH, $result->rc);
        self::assertSame('', $result->error);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($position[$i], $result->xx[$i], 1e-12);
        }
    }

    public function testApparentUtResultWrapsApparentUtPosition(): void
    {
        $position = SolarPosition::apparentUt(2451545.0);
        $result = SolarPosition::apparentUtResult(2451545.0);

        self::assertSame(Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH, $result->rc);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($position[$i], $result->xx[$i], 1e-12);
        }
    }
}