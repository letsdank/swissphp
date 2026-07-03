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
                1 => 2451564.55,
                2 => 2451564.60,
                3 => 2451564.79,
                4 => 2451564.62,
                5 => 2451564.77,
                6 => 2451564.45,
                7 => 2451564.94,
                8 => 2451564.50,
                9 => 2451564.90,
            ],
            'attr' => [
                0 => 1.25,
                1 => 2.1,
                2 => 0.91,
                3 => 120.5,
                4 => 180.0,
                5 => 22.5,
                6 => 22.6,
                7 => 0.08,
                8 => 1.02,
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
        self::assertSame(2451564.55, $result->firstContactTime());
        self::assertSame(2451564.60, $result->secondContactTime());
        self::assertSame(2451564.79, $result->thirdContactTime());
        self::assertSame(2451564.62, $result->fourthContactTime());
        self::assertSame(2451564.60, $result->partialBeginTime());
        self::assertSame(2451564.79, $result->partialEndTime());
        self::assertSame(2451564.62, $result->totalityBeginTime());
        self::assertSame(2451564.77, $result->totalityEndTime());
        self::assertSame(2451564.45, $result->penumbralBeginTime());
        self::assertSame(2451564.94, $result->penumbralEndTime());
        self::assertSame(2451564.50, $result->moonriseTime());
        self::assertSame(2451564.90, $result->moonsetTime());
        self::assertSame(2451564.77, $result->sunriseTime());
        self::assertSame(2451564.45, $result->sunsetTime());
        self::assertSame(1.25, $result->umbralMagnitude());
        self::assertSame(2.1, $result->penumbralMagnitude());
        self::assertSame(1.25, $result->solarMagnitude());
        self::assertSame(2.1, $result->lunarSolarDiameterRatio());
        self::assertSame(0.91, $result->obscuration());
        self::assertSame(120.5, $result->coreShadowDiameterKm());
        self::assertSame(180.0, $result->moonAzimuth());
        self::assertSame(22.5, $result->moonTrueAltitude());
        self::assertSame(22.6, $result->moonApparentAltitude());
        self::assertSame(180.0, $result->sunAzimuth());
        self::assertSame(22.5, $result->sunTrueAltitude());
        self::assertSame(22.6, $result->sunApparentAltitude());
        self::assertSame(0.08, $result->distanceFromOpposition());
        self::assertSame(0.08, $result->solarElongation());
        self::assertSame(1.02, $result->nasaMagnitude());
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
        self::assertSame(0.0, $result->firstContactTime());
        self::assertSame(0.0, $result->secondContactTime());
        self::assertSame(0.0, $result->thirdContactTime());
        self::assertSame(0.0, $result->fourthContactTime());
        self::assertSame(0.0, $result->partialBeginTime());
        self::assertSame(0.0, $result->partialEndTime());
        self::assertSame(0.0, $result->totalityBeginTime());
        self::assertSame(0.0, $result->totalityEndTime());
        self::assertSame(0.0, $result->penumbralBeginTime());
        self::assertSame(0.0, $result->penumbralEndTime());
        self::assertSame(0.0, $result->moonriseTime());
        self::assertSame(0.0, $result->moonsetTime());
        self::assertSame(0.0, $result->sunriseTime());
        self::assertSame(0.0, $result->sunsetTime());
        self::assertSame('no lunar eclipse found within search window', $result->error);
    }
}