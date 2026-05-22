<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Catalog;
use SwissEph\EclipseResult;

final class EclipseResultTest extends TestCase
{
    public function testLunarEclipseAttributes(): void
    {
        $result = new EclipseResult(
            Catalog::SE_ECL_TOTAL,
            [
                0 => 1.25,
                1 => 2.1,
                4 => 180.0,
                5 => 22.5,
                6 => 22.6,
                7 => 0.08,
                8 => 1.25,
                9 => 136.0,
                10 => 29.0,
            ],
            [
                0 => 100.0,
                1 => 200.0,
                2 => 300.0,
            ]
        );

        self::assertTrue($result->isEclipse());
        self::assertTrue($result->isTotal());
        self::assertFalse($result->isPartial());
        self::assertFalse($result->isPenumbral());
        self::assertSame(1.25, $result->umbralMagnitude());
        self::assertSame(2.1, $result->penumbralMagnitude());
        self::assertSame(180.0, $result->moonAzimuth());
        self::assertSame(22.5, $result->moonTrueAltitude());
        self::assertSame(22.6, $result->moonApparentAltitude());
        self::assertSame(0.08, $result->distanceFromOpposition());
        self::assertSame(136, $result->sarosSeries());
        self::assertSame(29, $result->sarosMember());
    }

    public function testFromArrayAndToArray(): void
    {
        $array = [
            'rc' => Catalog::SE_ECL_PENUMBRAL,
            'attr' => array_fill(0, 20, 0.0),
            'dcore' => array_fill(0, 10, 0.0),
            'error' => '',
        ];

        $result = EclipseResult::fromArray($array);

        self::assertTrue($result->isPenumbral());
        self::assertSame($array, $result->toArray());
    }

    public function testNoEclipseResult(): void
    {
        $result = new EclipseResult(0, array_fill(0, 20, 0.0), [], 'no lunar eclipse');

        self::assertFalse($result->isEclipse());
        self::assertFalse($result->isTotal());
        self::assertSame('no lunar eclipse', $result->error);
        self::assertSame(0.0, $result->umbralMagnitude());
    }
}