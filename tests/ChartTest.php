<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SwissEph\AspectSet;
use SwissEph\CalculationResult;
use SwissEph\Catalog;
use SwissEph\Chart;
use SwissEph\HousesResult;

final class ChartTest extends TestCase
{
    public function testPositionAccessors(): void
    {
        $sun = new CalculationResult(Catalog::SEFLG_SPEED, [10.0, 0.0, 1.0, 1.0, 0.0, 0.0]);
        $chart = new Chart(['Sun' => $sun]);

        self::assertTrue($chart->hasPosition('Sun'));
        self::assertFalse($chart->hasPosition('Moon'));
        self::assertSame($sun, $chart->position('Sun'));
        self::assertSame(['Sun' => $sun], $chart->positions());
    }

    public function testPositionRejectsMissingName(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Chart([]))->position('Sun');
    }

    public function testAspects(): void
    {
        $chart = new Chart([
            'Sun' => new CalculationResult(Catalog::SEFLG_SPEED, [10.0, 0.0, 1.0, 1.0, 0.0, 0.0]),
            'Moon' => new CalculationResult(Catalog::SEFLG_SPEED, [101.5, 0.0, 1.0, 0.0, 0.0, 0.0]),
        ]);

        $aspects = $chart->aspects(AspectSet::major(2.0));

        self::assertCount(1, $aspects);
        self::assertSame('square', $aspects[0]['aspect']->name());
    }

    public function testAspectsToOtherChart(): void
    {
        $natal = new Chart([
            'Sun' => new CalculationResult(Catalog::SEFLG_SPEED, [10.0, 0.0, 1.0, 1.0, 0.0, 0.0]),
        ]);

        $transit = new Chart([
            'Mars' => new CalculationResult(Catalog::SEFLG_SPEED, [101.5, 0.0, 1.0, 0.0, 0.0, 0.0]),
        ]);

        $aspects = $natal->aspectsTo($transit, AspectSet::major(2.0));

        self::assertCount(1, $aspects);
        self::assertSame('Sun', $aspects[0]['first']);
        self::assertSame('Mars', $aspects[0]['second']);
    }

    public function testWithHousesReturnsNewChart(): void
    {
        $chart = new Chart([]);
        $houses = new HousesResult([1 => 10.0], [0 => 10.0, 1 => 280.0, 2 => 300.0]);

        $withHouses = $chart->withHouses($houses);

        self::assertNotSame($chart, $withHouses);
        self::assertNull($chart->houses);
        self::assertSame($houses, $withHouses->houses);
    }
}