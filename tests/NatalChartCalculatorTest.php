<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\AspectSet;
use SwissEph\Catalog;
use SwissEph\Houses;
use SwissEph\NatalChart;
use SwissEph\NatalChartCalculator;

final class NatalChartCalculatorTest extends TestCase
{
    public function testCalculateReturnsNatalChartWithPointsAndHouses(): void
    {
        $chart = NatalChartCalculator::calculate(
            2451545.0,
            55.7558,
            37.6173,
            Houses::HSYS_PLACIDUS,
            [
                Catalog::SE_SUN,
                Catalog::SE_MOON,
                Catalog::SE_MERCURY,
            ]
        );

        self::assertInstanceOf(NatalChart::class, $chart);
        self::assertSame(2451545.0, $chart->tjdUt);
        self::assertSame(55.7558, $chart->geoLat);
        self::assertSame(37.6173, $chart->geoLon);
        self::assertSame(Houses::HSYS_PLACIDUS, $chart->houseSystem);

        self::assertTrue($chart->hasPoint('Sun'));
        self::assertTrue($chart->hasPoint('Moon'));
        self::assertTrue($chart->hasPoint('Mercury'));
        self::assertCount(3, $chart->points);
        self::assertCount(12, $chart->houses);

        self::assertGreaterThanOrEqual(1, $chart->point('Sun')->house);
        self::assertLessThanOrEqual(12, $chart->point('Sun')->house);
        self::assertGreaterThanOrEqual(0.0, $chart->point('Sun')->normalizedLongitude());
        self::assertLessThan(360.0, $chart->point('Sun')->normalizedLongitude());
    }

    public function testCalculateCanBuildAspects(): void
    {
        $chart = NatalChartCalculator::calculate(
            2451545.0,
            55.7558,
            37.6173,
            Houses::HSYS_PLACIDUS,
            [
                Catalog::SE_SUN,
                Catalog::SE_MOON,
                Catalog::SE_MERCURY,
                Catalog::SE_VENUS,
            ],
            Catalog::SEFLG_DEFAULTEPH,
            AspectSet::major(8.0)
        );

        self::assertNotSame([], $chart->aspects);

        foreach ($chart->aspects as $aspect) {
            self::assertContains($aspect->first, ['Sun', 'Moon', 'Mercury', 'Venus']);
            self::assertContains($aspect->second, ['Sun', 'Moon', 'Mercury', 'Venus']);
            self::assertGreaterThanOrEqual(0.0, $aspect->orb);
            self::assertLessThanOrEqual(8.0, $aspect->orb);
        }
    }

    public function testCalculateArrayExportContainsComputedFields(): void
    {
        $chart = NatalChartCalculator::calculate(
            2451545.0,
            0.0,
            0.0,
            Houses::HSYS_EQUAL,
            [
                Catalog::SE_SUN,
            ]
        );

        $array = $chart->toArray();

        self::assertArrayHasKey('Sun', $array['points']);
        self::assertArrayHasKey(1, $array['houses']);
        self::assertSame('Capricorn', $array['points']['Sun']['signName']);
        self::assertSame(9, $array['points']['Sun']['signIndex']);
    }
}