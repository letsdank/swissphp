<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Catalog;
use SwissEph\SolarEclipseWhenResult;

final class SolarEclipseWhenResultTest extends TestCase
{
    public function testWrapsSolarEclipseSearchArray(): void
    {
        $array = [
            'rc' => Catalog::SE_ECL_ANNULAR | Catalog::SE_ECL_VISIBLE | Catalog::SE_ECL_MAX_VISIBLE,
            'tret' => [
                0 => 2460409.25,
                1 => 2460409.10,
                2 => 2460409.18,
                3 => 2460409.32,
                4 => 2460409.40,
                5 => 2460409.12,
                6 => 2460409.38,
            ],
            'attr' => [
                0 => 0.95,
                2 => 0.91,
            ],
            'dcore' => [],
            'error' => '',
        ];

        $result = SolarEclipseWhenResult::fromArray($array);

        self::assertTrue($result->isEclipse());
        self::assertTrue($result->isVisible());
        self::assertTrue($result->isMaximumVisible());
        self::assertFalse($result->isTotal());
        self::assertTrue($result->isAnnular());
        self::assertFalse($result->isPartial());
        self::assertSame(2460409.25, $result->maximumTime());
        self::assertSame(2460409.10, $result->firstContactTime());
        self::assertSame(2460409.18, $result->secondContactTime());
        self::assertSame(2460409.32, $result->thirdContactTime());
        self::assertSame(2460409.40, $result->fourthContactTime());
        self::assertSame(2460409.12, $result->sunriseTime());
        self::assertSame(2460409.38, $result->sunsetTime());
        self::assertSame(0.95, $result->magnitude());
        self::assertSame(0.91, $result->obscuration());
        self::assertSame($array, $result->toArray());
    }
}