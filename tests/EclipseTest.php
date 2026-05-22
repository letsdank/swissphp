<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Catalog;
use SwissEph\Eclipse;
use SwissEph\EclipseResult;
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
}