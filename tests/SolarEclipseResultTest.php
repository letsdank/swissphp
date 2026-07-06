<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Catalog;
use SwissEph\SolarEclipseResult;

final class SolarEclipseResultTest extends TestCase
{
    public function testWrapsSolarEclipseArray(): void
    {
        $array = [
            'rc' => Catalog::SE_ECL_ANNULAR,
            'geopos' => [
                0 => -81.98639653728043,
                1 => 19.984901425093963,
            ],
            'attr' => [
                0 => 0.95,
                1 => 0.97,
                2 => 0.91,
                3 => -12.5,
                4 => 135.0,
                5 => 41.0,
                6 => 41.1,
                7 => 0.21,
                8 => 0.97,
                9 => 147.0,
                10 => 33.0,
            ],
            'dcore' => [
                0 => -12.5,
            ],
            'error' => '',
        ];

        $result = SolarEclipseResult::fromArray($array);

        self::assertTrue($result->isEclipse());
        self::assertFalse($result->isTotal());
        self::assertTrue($result->isAnnular());
        self::assertFalse($result->isPartial());
        self::assertSame(-81.98639653728043, $result->geographicLongitude());
        self::assertSame(19.984901425093963, $result->geographicLatitude());
        self::assertSame(0.95, $result->magnitude());
        self::assertSame(0.97, $result->lunarSolarDiameterRatio());
        self::assertSame(0.91, $result->obscuration());
        self::assertSame(-12.5, $result->coreShadowDiameterKm());
        self::assertSame(135.0, $result->sunAzimuth());
        self::assertSame(41.0, $result->sunTrueAltitude());
        self::assertSame(41.1, $result->sunApparentAltitude());
        self::assertSame(0.21, $result->elongation());
        self::assertSame(0.97, $result->nasaMagnitude());
        self::assertSame(147, $result->sarosSeries());
        self::assertSame(33, $result->sarosMember());
        self::assertSame($array, $result->toArray());
    }
}