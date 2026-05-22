<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\AspectSet;
use SwissEph\Catalog;
use SwissEph\Houses;
use SwissEph\NatalChartCalculator;
use SwissEph\NatalChartRenderer;
use function PHPUnit\Framework\assertStringContainsString;

final class NatalChartRendererTest extends TestCase
{
    public function testRenderSvgReturnsStandalongSvg(): void
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

        $svg = NatalChartRenderer::renderSvg($chart, 480);

        self::assertStringStartsWith('<svg ', $svg);
        self::assertStringContainsString('viewBox="0 0 480 480"', $svg);
        self::assertStringContainsString('Natal chart', $svg);
        self::assertStringContainsString('<circle', $svg);
        self::assertStringContainsString('<line', $svg);
        self::assertStringContainsString('Sun', $svg);
        self::assertStringContainsString('Moon', $svg);
        self::assertStringEndsWith('</svg>', $svg);

        self::assertStringContainsString('ASC', $svg);
        assertStringContainsString('MC', $svg);
    }

    public function testRenderSvgHasMinimumSize(): void
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

        $svg = NatalChartRenderer::renderSvg($chart, 100);

        self::assertStringContainsString('viewBox="0 0 320 320"', $svg);
    }
}