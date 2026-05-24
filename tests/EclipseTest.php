<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Catalog;
use SwissEph\Eclipse;
use SwissEph\EclipseResult;
use SwissEph\Observer;
use SwissEph\SwissDate;

final class EclipseTest extends TestCase
{
    public function testLunarHowDetectsTotalLunarEclipse(): void
    {
        $tjdUt = SwissDate::julday(2000, 1, 21, 4.75, SwissDate::GREGORIAN_CALENDAR);

        $result = Eclipse::lunarHow($tjdUt);

        self::assertSame(Catalog::SE_ECL_TOTAL, $result['rc']);
        self::assertSame('', $result['error']);
        self::assertEqualsWithDelta(1.3460034547997217, $result['attr'][0], 1e-12);
        self::assertEqualsWithDelta(2.3287133012900636, $result['attr'][1], 1e-12);
        self::assertEqualsWithDelta(0.28687222415874203, $result['attr'][7], 1e-12);
        self::assertEqualsWithDelta($result['attr'][0], $result['attr'][8], 1e-15);
        self::assertSame(-99999999.0, $result['attr'][9]);
        self::assertSame(-99999999.0, $result['attr'][10]);

        self::assertEqualsWithDelta(1.2095718390975446e-5, $result['dcore'][0], 1e-17);
        self::assertEqualsWithDelta(6.350966766530361e-5, $result['dcore'][1], 1e-17);
        self::assertEqualsWithDelta(0.00010918136370441543, $result['dcore'][2], 1e-17);
    }

    public function testLunarHowDetectsPenumbralLunarEclipse(): void
    {
        $tjdUt = SwissDate::julday(2024, 3, 25, 7.2, SwissDate::GREGORIAN_CALENDAR);

        $result = Eclipse::lunarHow($tjdUt);

        self::assertSame(Catalog::SE_ECL_PENUMBRAL, $result['rc']);
        self::assertSame('', $result['error']);
        self::assertSame(0.0, $result['attr'][0]);
        self::assertEqualsWithDelta(0.7678350565020254, $result['attr'][1], 1e-12);
        self::assertEqualsWithDelta(1.049351316670851, $result['attr'][7], 1e-12);
    }

    public function testLunarHowReturnsNoEclipse(): void
    {
        $result = Eclipse::lunarHow(2451545.0);

        self::assertSame(0, $result['rc']);
        self::assertStringContainsString('no lunar eclipse', $result['error']);
        self::assertSame(0.0, $result['attr'][0]);
        self::assertEqualsWithDelta(-95.32796218737185, $result['attr'][1], 1e-10);
        self::assertSame(0.0, $result['attr'][7]);
    }

    public function testLunarHowResultWrapsArrayResult(): void
    {
        $tjdUt = SwissDate::julday(2000, 1, 21, 4.75, SwissDate::GREGORIAN_CALENDAR);

        $result = Eclipse::lunarHowResult($tjdUt);

        self::assertInstanceOf(EclipseResult::class, $result);
        self::assertTrue($result->isTotal());
        self::assertEqualsWithDelta(1.3460034547997217, $result->umbralMagnitude(), 1e-12);
        self::assertEqualsWithDelta(2.3287133012900636, $result->penumbralMagnitude(), 1e-12);
        self::assertEqualsWithDelta(0.28687222415874203, $result->distanceFromOpposition(), 1e-12);
    }

    public function testLunarHowAddsLocalMoonAzimuthAndAltitude(): void
    {
        $tjdUt = SwissDate::julday(2000, 1, 21, 4.75, SwissDate::GREGORIAN_CALENDAR);

        $result = Eclipse::lunarHow(
            $tjdUt,
            Catalog::SEFLG_DEFAULTEPH,
            new Observer(13.4050, 52.5200, 34.0)
        );

        self::assertSame(Catalog::SE_ECL_TOTAL, $result['rc']);
        self::assertEqualsWithDelta(96.07878510708167, $result['attr'][4], 1e-12);
        self::assertEqualsWithDelta(20.514613252028976, $result['attr'][5], 1e-12);
        self::assertEqualsWithDelta(20.557682179695288, $result['attr'][6], 1e-12);
    }

    public function testLunarHowReturnsZeroWhenLocalEclipseIsBelowHorizon(): void
    {
        $tjdUt = SwissDate::julday(2000, 1, 21, 4.75, SwissDate::GREGORIAN_CALENDAR);

        $result = Eclipse::lunarHow(
            $tjdUt,
            Catalog::SEFLG_DEFAULTEPH,
            new Observer(180.0, 0.0, 0.0)
        );

        self::assertSame(0, $result['rc']);
        self::assertSame('', $result['error']);
        self::assertEqualsWithDelta(1.3460034547997217, $result['attr'][0], 1e-12);
        self::assertEqualsWithDelta(248.84800223233083, $result['attr'][4], 1e-12);
        self::assertEqualsWithDelta(-20.290458167839667, $result['attr'][5], 1e-12);
        self::assertEqualsWithDelta(-20.290458167839667, $result['attr'][6], 1e-12);
    }

    public function testLunarHowRejectsInvalidObserverAltitude(): void
    {
        $result = Eclipse::lunarHow(
            2451545.0,
            Catalog::SEFLG_DEFAULTEPH,
            new Observer(0.0, 0.0, 30000.0)
        );

        self::assertSame(SwissDate::ERR, $result['rc']);
        self::assertSame(
            'location for eclipses must be between -500 and 25000 m above sea',
            $result['error']
        );
        self::assertSame(array_fill(0, 20, 0.0), $result['attr']);
    }

    public function testLunarWhenFindsNextTotalLunarEclipse(): void
    {
        $result = Eclipse::lunarWhen(2451545.0);

        self::assertSame(Catalog::SE_ECL_TOTAL, $result['rc']);
        self::assertSame('', $result['error']);
        self::assertEqualsWithDelta(
            SwissDate::julday(2000, 1, 21, 4.75, SwissDate::GREGORIAN_CALENDAR),
            $result['tret'][0],
            0.02
        );
        self::assertGreaterThan(1.0, $result['attr'][0]);
        self::assertGreaterThan(2.0, $result['attr'][1]);
    }

    public function testLunarWhenCanSearchBackward(): void
    {
        $start = SwissDate::julday(2000, 2, 1, 0.0, SwissDate::GREGORIAN_CALENDAR);

        $result = Eclipse::lunarWhen($start, Catalog::SEFLG_DEFAULTEPH, Catalog::SE_ECL_ALLTYPES_LUNAR, true);

        self::assertSame(Catalog::SE_ECL_TOTAL, $result['rc']);
        self::assertLessThan($start, $result['tret'][0]);
        self::assertEqualsWithDelta(
            SwissDate::julday(2000, 1, 21, 4.75, SwissDate::GREGORIAN_CALENDAR),
            $result['tret'][0],
            0.02
        );
    }
}