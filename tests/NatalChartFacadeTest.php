<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\AspectSet;
use SwissEph\Catalog;
use SwissEph\Houses;
use SwissEph\NatalChartCalculator;
use SwissEph\NatalChartFacade;
use SwissEph\SwissDate;

final class NatalChartFacadeTest extends TestCase
{
    public function testFromLocalDateTimeConvertsToUtAndCalculatesChart(): void
    {
        $chart = NatalChartFacade::fromLocalDateTime(
            2000,
            1,
            1,
            15,
            0,
            0.0,
            3.0,
            55.7558,
            37.6173,
            Houses::HSYS_PLACIDUS,
            [
                Catalog::SE_SUN,
                Catalog::SE_MOON,
            ]
        );

        $expected = NatalChartCalculator::calculate(
            SwissDate::julday(2000, 1, 1, 12.0, SwissDate::GREGORIAN_CALENDAR),
            55.7558,
            37.6173,
            Houses::HSYS_PLACIDUS,
            [
                Catalog::SE_SUN,
                Catalog::SE_MOON,
            ]
        );

        self::assertEqualsWithDelta($expected->tjdUt, $chart->tjdUt, 1e-12);
        self::assertEqualsWithDelta(
            $expected->point('Sun')->longitude,
            $chart->point('Sun')->longitude,
            1e-12
        );
        self::assertEqualsWithDelta(
            $expected->point('Moon')->longitude,
            $chart->point('Moon')->longitude,
            1e-12
        );
    }

    public function testSvgFromLocalDateTimeReturnsSvg(): void
    {
        $svg = NatalChartFacade::svgFromLocalDateTime(
            2000,
            1,
            1,
            15,
            0,
            0.0,
            3.0,
            55.7558,
            37.6173,
            Houses::HSYS_PLACIDUS,
            [
                Catalog::SE_SUN,
                Catalog::SE_MOON,
                Catalog::SE_MERCURY,
            ],
            Catalog::SEFLG_DEFAULTEPH,
            AspectSet::major(8.0),
            SwissDate::GREGORIAN_CALENDAR,
            480
        );

        self::assertStringStartsWith('<svg ', $svg);
        self::assertStringContainsString('viewBox="0 0 480 480"', $svg);
        self::assertStringContainsString('Sun', $svg);
        self::assertStringContainsString('Moon', $svg);
    }
}