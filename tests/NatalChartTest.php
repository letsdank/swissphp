<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SwissEph\Catalog;
use SwissEph\NatalChart;
use SwissEph\NatalChartAspect;
use SwissEph\NatalChartHouse;
use SwissEph\NatalChartPoint;

final class NatalChartTest extends TestCase
{
    public function testPointCalculatesSignAndRetrogradeState(): void
    {
        $point = new NatalChartPoint(
            Catalog::SE_MERCURY,
            'Mercury',
            48.5,
            1.25,
            0.9,
            -0.3,
            2
        );

        self::assertSame(1, $point->signIndex());
        self::assertSame('Taurus', $point->signName());
        self::assertEqualsWithDelta(18.5, $point->signDegree(), 1e-12);
        self::assertTrue($point->isRetrograde());
        self::assertSame(2, $point->toArray()['house']);
    }

    public function testHouseCalculatesSignPosition(): void
    {
        $house = new NatalChartHouse(10, 281.25);

        self::assertSame(9, $house->signIndex());
        self::assertEqualsWithDelta(11.25, $house->signDegree(), 1e-12);
        self::assertSame(10, $house->toArray()['number']);
    }

    public function testChartAccessorsAndArrayExport(): void
    {
        $sun = new NatalChartPoint(Catalog::SE_SUN, 'Sun', 280.0, 0.0, 0.98, 1.0, 4);
        $moon = new NatalChartPoint(Catalog::SE_MOON, 'Moon', 120.0, 3.0, 0.0025, 13.0, 10);
        $asc = new NatalChartHouse(1, 155.0);
        $aspect = new NatalChartAspect('Sun', 'Moon', 'trine', 120.0, 0.0, true);

        $chart = new NatalChart(
            2451545.0,
            55.7558,
            37.6173,
            'P',
            [
                'Sun' => $sun,
                'Moon' => $moon,
            ],
            [
                1 => $asc,
            ],
            [
                $aspect,
            ]
        );

        self::assertTrue($chart->hasPoint('Sun'));
        self::assertFalse($chart->hasPoint('Mars'));
        self::assertSame($sun, $chart->point('Sun'));
        self::assertSame($asc, $chart->house(1));

        $array = $chart->toArray();

        self::assertSame(2451545.0, $array['tjdUt']);
        self::assertSame('Capricorn', $array['points']['Sun']['signName']);
        self::assertSame('trine', $array['aspects'][0]['name']);
    }

    public function testChartRejectsMissingPoint(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new NatalChart(2451545.0, 0.0, 0.0, 'P', []))->point('Sun');
    }

    public function testChartRejectsMissingHouse(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new NatalChart(2451545.0, 0.0, 0.0, 'P', []))->house(1);
    }
}