<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Calculator;
use SwissEph\Catalog;
use SwissEph\NodesApsides;
use SwissEph\SwissDate;

final class NodesApsidesResultTest extends TestCase
{
    public function testNodApsResultWrapsArrayResult(): void
    {
        $array = NodesApsides::nodAps(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_HELCTR | Catalog::SEFLG_TRUEPOS | Catalog::SEFLG_NONUT,
            Catalog::SE_NODBIT_MEAN
        );

        $result = NodesApsides::nodApsResult(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_HELCTR | Catalog::SEFLG_TRUEPOS | Catalog::SEFLG_NONUT,
            Catalog::SE_NODBIT_MEAN
        );

        self::assertSame($array, $result->toArray());
        self::assertTrue($result->isOk());
        self::assertFalse($result->hasError());
        self::assertEqualsWithDelta(48.330893023992, $result->ascendingNodeLongitude(), 1e-12);
        self::assertEqualsWithDelta(228.330893023992, $result->descendingNodeLongitude(), 1e-12);
        self::assertEqualsWithDelta(77.273956506887, $result->perihelionLongitude(), 1e-12);
        self::assertEqualsWithDelta(257.273956506887, $result->aphelionLongitude(), 1e-12);
    }

    public function testNodApsUtResultWrapsArrayResult(): void
    {
        $array = NodesApsides::nodApsUt(
            2451545.0,
            Catalog::SE_MOON,
            Catalog::SEFLG_SPEED,
            Catalog::SE_NODBIT_MEAN
        );

        $result = NodesApsides::nodApsUtResult(
            2451545.0,
            Catalog::SE_MOON,
            Catalog::SEFLG_SPEED,
            Catalog::SE_NODBIT_MEAN
        );

        self::assertSame($array, $result->toArray());
        self::assertTrue($result->isOk());
    }

    public function testNodApsResultCanRepresentError(): void
    {
        $result = NodesApsides::nodApsResult(
            2451545.0,
            Catalog::SE_MEAN_NODE,
            Catalog::SEFLG_SPEED,
            Catalog::SE_NODBIT_MEAN
        );

        self::assertSame(SwissDate::ERR, $result->rc);
        self::assertFalse($result->isOk());
        self::assertTrue($result->hasError());
        self::assertStringContainsString('not implemented', $result->error);
    }

    public function testCalculatorNodApsResultDelegatesToNodesApsides(): void
    {
        $expected = NodesApsides::nodApsResult(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_HELCTR | Catalog::SEFLG_TRUEPOS | Catalog::SEFLG_NONUT,
            Catalog::SE_NODBIT_MEAN
        );

        $actual = Calculator::nodApsResult(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_HELCTR | Catalog::SEFLG_TRUEPOS | Catalog::SEFLG_NONUT,
            Catalog::SE_NODBIT_MEAN
        );

        self::assertSame($expected->toArray(), $actual->toArray());
    }
}