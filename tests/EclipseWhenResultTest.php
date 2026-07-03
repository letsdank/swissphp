<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Catalog;
use SwissEph\EclipseWhenResult;

final class EclipseWhenResultTest extends TestCase
{
    public function testFromArrayAndToArray(): void
    {
        $array = [
            'rc' => Catalog::SE_ECL_TOTAL | Catalog::SE_ECL_VISIBLE | Catalog::SE_ECL_MAX_VISIBLE,
            'tret' => [
                0 => 2451564.697917,
            ],
            'attr' => [
                0 => 1.25,
                1 => 2.1,
                4 => 180.0,
                5 => 22.5,
                6 => 22.6,
                7 => 0.08,
                9 => 136.0,
                10 => 29.0,
            ],
            'dcore' => [
                0 => 100.0,
                1 => 200.0,
            ],
            'error' => '',
        ];

        $result = EclipseWhenResult::fromArray($array);

        self::assertTrue($result->isEclipse());
        self::assertTrue($result->isVisible());
        self::assertTrue($result->isMaximumVisible());
        self::assertTrue($result->isTotal());
        self::assertFalse($result->isPartial());
        self::assertFalse($result->isPenumbral());
        self::assertSame(2451564.697917, $result->maximumTime());
        self::assertSame(1.25, $result->umbralMagnitude());
        self::assertSame(2.1, $result->penumbralMagnitude());
        self::assertSame(180.0, $result->moonAzimuth());
        self::assertSame(22.5, $result->moonTrueAltitude());
        self::assertSame(22.6, $result->moonApparentAltitude());
        self::assertSame(0.08, $result->distanceFromOpposition());
        self::assertSame(136, $result->sarosSeries());
        self::assertSame(29, $result->sarosMember());
        self::assertSame($array, $result->toArray());
    }

    public function testNoEclipseResult(): void
    {
        $result = new EclipseWhenResult(
            0,
            array_fill(0, 10, 0.0),
            array_fill(0, 20, 0.0),
            [],
            'no lunar eclipse found within search window'
        );

        self::assertFalse($result->isEclipse());
        self::assertFalse($result->isVisible());
        self::assertFalse($result->isMaximumVisible());
        self::assertSame(0.0, $result->maximumTime());
        self::assertSame('no lunar eclipse found within search window', $result->error);
    }
}