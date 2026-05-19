<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\AspectMatrix;
use SwissEph\AspectSet;
use SwissEph\CalculationResult;
use SwissEph\Catalog;

final class AspectMatrixText extends TestCase
{
    public function testBetweenFindsAspectsForUniquePairs(): void
    {
        $positions = [
            'Sun' => new CalculationResult(Catalog::SEFLG_SPEED, [10.0, 0.0, 1.0, 1.0, 0.0, 0.0]),
            'Moon' => new CalculationResult(Catalog::SEFLG_SPEED, [101.5, 0.0, 1.0, 0.0, 0.0, 0.0]),
            'Mars' => new CalculationResult(Catalog::SEFLG_SPEED, [250.0, 0.0, 1.0, 0.0, 0.0, 0.0]),
        ];

        $aspects = AspectMatrix::between($positions, AspectSet::major(2.0));

        self::assertCount(1, $aspects);
        self::assertSame('Sun', $aspects[0]['first']);
        self::assertSame('Moon', $aspects[0]['second']);
        self::assertSame('square', $aspects[0]['aspect']->name());
    }

    public function testCrossFindsAspectsAcrossTwoSets(): void
    {
        $first = [
            'natal Sun' => new CalculationResult(Catalog::SEFLG_SPEED, [10.0, 0.0, 1.0, 1.0, 0.0, 0.0]),
        ];

        $second = [
            'transit Mars' => new CalculationResult(Catalog::SEFLG_SPEED, [101.5, 0.0, 1.0, 0.0, 0.0, 0.0]),
            'transit Venus' => new CalculationResult(Catalog::SEFLG_SPEED, [140.0, 0.0, 1.0, 0.0, 0.0, 0.0]),
        ];

        $aspects = AspectMatrix::cross($first, $second, AspectSet::major(2.0));

        self::assertCount(1, $aspects);
        self::assertSame('natal Sun', $aspects[0]['first']);
        self::assertSame('transit Mars', $aspects[0]['second']);
        self::assertSame('square', $aspects[0]['aspect']->name());
    }
}