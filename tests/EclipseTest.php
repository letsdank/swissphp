<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Catalog;
use SwissEph\Eclipse;
use SwissEph\EclipseResult;
use SwissEph\EclipseWhenResult;
use SwissEph\Observer;
use SwissEph\SolarEclipseResult;
use SwissEph\SolarEclipseWhenResult;
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
        self::assertEqualsWithDelta(2451564.6171892, $result['tret'][2], 1e-7);
        self::assertEqualsWithDelta(2451564.7604602, $result['tret'][3], 1e-7);
        self::assertEqualsWithDelta(2451564.6599024, $result['tret'][4], 1e-7);
        self::assertEqualsWithDelta(2451564.7177528, $result['tret'][5], 1e-7);
        self::assertEqualsWithDelta(2451564.5774370, $result['tret'][6], 1e-7);
        self::assertEqualsWithDelta(2451564.8002619, $result['tret'][7], 1e-7);
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

    public function testLunarWhenResultWrapsArrayResult(): void
    {
        $result = Eclipse::lunarWhenResult(2451545.0);

        self::assertInstanceOf(EclipseWhenResult::class, $result);
        self::assertTrue($result->isTotal());
        self::assertEqualsWithDelta(
            SwissDate::julday(2000, 1, 21, 4.75, SwissDate::GREGORIAN_CALENDAR),
            $result->maximumTime(),
            0.02
        );
        self::assertGreaterThan(1.0, $result->umbralMagnitude());
    }

    public function testLunarWhenLocFindsNextVisibleLocalLunarEclipse(): void
    {
        $result = Eclipse::lunarWhenLoc(
            2451545.0,
            Catalog::SEFLG_DEFAULTEPH,
            new Observer(13.4050, 52.5200, 34.0)
        );

        self::assertSame(
            Catalog::SE_ECL_TOTAL
            | Catalog::SE_ECL_VISIBLE
            | Catalog::SE_ECL_MAX_VISIBLE
            | Catalog::SE_ECL_PARTBEG_VISIBLE
            | Catalog::SE_ECL_PARTEND_VISIBLE
            | Catalog::SE_ECL_TOTBEG_VISIBLE
            | Catalog::SE_ECL_TOTEND_VISIBLE
            | Catalog::SE_ECL_PENUMBBEG_VISIBLE
            | Catalog::SE_ECL_PENUMBEND_VISIBLE,
            $result['rc']
        );
        self::assertSame('', $result['error']);
        self::assertEqualsWithDelta(2451564.687058892, $result['tret'][0], 1e-9);
        self::assertEqualsWithDelta(1.3970831041773457, $result['attr'][0], 1e-12);
        self::assertEqualsWithDelta(2.3797310345076195, $result['attr'][1], 1e-12);
        self::assertEqualsWithDelta(93.19097105806173, $result['attr'][4], 1e-12);
        self::assertEqualsWithDelta(22.803691629925098, $result['attr'][5], 1e-12);
        self::assertEqualsWithDelta(22.842037538364337, $result['attr'][6], 1e-12);
    }

    public function testLunarWhenLocCanSearchBackward(): void
    {
        $result = Eclipse::lunarWhenLoc(
            SwissDate::julday(2000, 2, 1, 0.0, SwissDate::GREGORIAN_CALENDAR),
            Catalog::SEFLG_DEFAULTEPH,
            new Observer(13.4050, 52.5200, 34.0),
            true
        );

        self::assertSame(
            Catalog::SE_ECL_TOTAL
            | Catalog::SE_ECL_VISIBLE
            | Catalog::SE_ECL_MAX_VISIBLE
            | Catalog::SE_ECL_PARTBEG_VISIBLE
            | Catalog::SE_ECL_PARTEND_VISIBLE
            | Catalog::SE_ECL_TOTBEG_VISIBLE
            | Catalog::SE_ECL_TOTEND_VISIBLE
            | Catalog::SE_ECL_PENUMBBEG_VISIBLE
            | Catalog::SE_ECL_PENUMBEND_VISIBLE,
            $result['rc']
        );
        self::assertEqualsWithDelta(2451564.687058892, $result['tret'][0], 1e-9);
    }

    public function testLunarWhenLocAddsMoonriseDuringEclipse(): void
    {
        $result = Eclipse::lunarWhenLoc(
            2451545.0,
            Catalog::SEFLG_DEFAULTEPH,
            new Observer(-150.0, -60.0, 0.0)
        );

        self::assertSame(
            Catalog::SE_ECL_PENUMBRAL
            | Catalog::SE_ECL_VISIBLE
            | Catalog::SE_ECL_MAX_VISIBLE
            | Catalog::SE_ECL_PENUMBEND_VISIBLE,
            $result['rc']
        );

        self::assertEqualsWithDelta(2451564.781816452, $result['tret'][0], 1e-9);
        self::assertSame(0.0, $result['tret'][2]);
        self::assertSame(0.0, $result['tret'][3]);
        self::assertSame(0.0, $result['tret'][4]);
        self::assertSame(0.0, $result['tret'][5]);
        self::assertSame(0.0, $result['tret'][6]);
        self::assertEqualsWithDelta(2451564.8002618942, $result['tret'][7], 1e-9);
        self::assertEqualsWithDelta(2451564.781816452, $result['tret'][8], 1e-9);
        self::assertSame(0.0, $result['tret'][9]);
        self::assertEqualsWithDelta(0.4576533834410415, $result['attr'][1], 1e-12);
        self::assertEqualsWithDelta(0.23684938584046766, $result['attr'][6], 1e-12);
    }

    public function testLunarWhenLocAddsMoonsetDuringEclipse(): void
    {
        $result = Eclipse::lunarWhenLoc(
            2451545.0,
            Catalog::SEFLG_DEFAULTEPH,
            new Observer(-50.0, -60.0, 0.0)
        );

        self::assertSame(
            Catalog::SE_ECL_TOTAL
            | Catalog::SE_ECL_VISIBLE
            | Catalog::SE_ECL_MAX_VISIBLE
            | Catalog::SE_ECL_PARTBEG_VISIBLE
            | Catalog::SE_ECL_PARTEND_VISIBLE
            | Catalog::SE_ECL_TOTBEG_VISIBLE
            | Catalog::SE_ECL_TOTEND_VISIBLE
            | Catalog::SE_ECL_PENUMBBEG_VISIBLE,
            $result['rc']
        );

        self::assertEqualsWithDelta(2451564.687058892, $result['tret'][0], 1e-9);
        self::assertEqualsWithDelta(2451564.617189198, $result['tret'][2], 1e-9);
        self::assertEqualsWithDelta(2451564.760460168, $result['tret'][3], 1e-9);
        self::assertEqualsWithDelta(2451564.659902407, $result['tret'][4], 1e-9);
        self::assertEqualsWithDelta(2451564.717752806, $result['tret'][5], 1e-9);
        self::assertEqualsWithDelta(2451564.5774369743, $result['tret'][6], 1e-9);
        self::assertSame(0.0, $result['tret'][7]);
        self::assertSame(0.0, $result['tret'][8]);
        self::assertEqualsWithDelta(2451564.7974327924, $result['tret'][9], 1e-9);
    }

    public function testSolarWhenGlobFindsNextGlobalMinimum(): void
    {
        $result = Eclipse::solarWhenGlob(2460400.0);

        self::assertSame(Catalog::SE_ECL_CENTRAL | Catalog::SE_ECL_TOTAL, $result['rc']);
        self::assertSame('', $result['error']);
        self::assertEqualsWithDelta(2460409.2240756718, $result['tret'][0], 1e-9);
        self::assertEqualsWithDelta(2460409.1162655386, $result['tret'][2], 1e-9);
        self::assertEqualsWithDelta(2460409.3317089113, $result['tret'][3], 1e-9);
        self::assertSame(0.0, $result['tret'][1]);
        self::assertEqualsWithDelta(1.0, $result['attr'][0], 1e-12);
        self::assertEqualsWithDelta(1.057075496809707, $result['attr'][1], 1e-12);
        self::assertEqualsWithDelta(1.0, $result['attr'][2], 1e-12);
        self::assertEqualsWithDelta(-188.0898259351609, $result['attr'][3], 1e-9);
        self::assertEqualsWithDelta(0.0004788657999566075, $result['attr'][7], 1e-12);
        self::assertEqualsWithDelta(-188.0898259351609, $result['dcore'][0], 1e-9);
    }

    public function testSolarWhenGlobFindsPreviousGlobalMaximum(): void
    {
        $result = Eclipse::solarWhenGlob(
            2460410.0,
            Catalog::SEFLG_DEFAULTEPH,
            Catalog::SE_ECL_ALLTYPES_SOLAR,
            true
        );

        self::assertSame(Catalog::SE_ECL_CENTRAL | Catalog::SE_ECL_TOTAL, $result['rc']);
        self::assertSame('', $result['error']);
        self::assertEqualsWithDelta(2460409.2240756718, $result['tret'][0], 1e-9);
    }

    public function testSolarWhenGlobFiltersTotalEclipses(): void
    {
        $result = Eclipse::solarWhenGlob(
            2460000.0,
            Catalog::SEFLG_DEFAULTEPH,
            Catalog::SE_ECL_TOTAL
        );

        self::assertSame(Catalog::SE_ECL_CENTRAL | Catalog::SE_ECL_TOTAL, $result['rc']);
        self::assertSame('', $result['error']);
        self::assertEqualsWithDelta(2460054.6498784726, $result['tret'][0], 1e-9);
        self::assertEqualsWithDelta(2460054.5360783623, $result['tret'][2], 1e-9);
        self::assertEqualsWithDelta(2460054.7639449453, $result['tret'][3], 1e-9);
        self::assertEqualsWithDelta(1.0149337171324408, $result['attr'][1], 1e-12);
        self::assertEqualsWithDelta(-51.28509088820063, $result['dcore'][0], 1e-9);
    }

    public function testSolarWhenGlobFiltersAnnularEclipses(): void
    {
        $result = Eclipse::solarWhenGlob(
            2460000.0,
            Catalog::SEFLG_DEFAULTEPH,
            Catalog::SE_ECL_ANNULAR
        );

        self::assertSame(Catalog::SE_ECL_CENTRAL | Catalog::SE_ECL_ANNULAR, $result['rc']);
        self::assertSame('', $result['error']);
        self::assertEqualsWithDelta(2460232.210229274, $result['tret'][0], 1e-9);
        self::assertEqualsWithDelta(2460232.0923260623, $result['tret'][2], 1e-9);
        self::assertEqualsWithDelta(2460232.328423156, $result['tret'][3], 1e-9);
        self::assertEqualsWithDelta(0.9519455289045887, $result['attr'][1], 1e-12);
        self::assertEqualsWithDelta(176.4252588062989, $result['dcore'][0], 1e-9);
    }

    public function testSolarWhenGlobFiltersPartialEclipses(): void
    {
        $result = Eclipse::solarWhenGlob(
            2451545.0,
            Catalog::SEFLG_DEFAULTEPH,
            Catalog::SE_ECL_PARTIAL
        );

        self::assertSame(Catalog::SE_ECL_NONCENTRAL | Catalog::SE_ECL_PARTIAL, $result['rc']);
        self::assertSame('', $result['error']);
        self::assertEqualsWithDelta(2451580.030268962, $result['tret'][0], 1e-9);
        self::assertEqualsWithDelta(2451579.9428501227, $result['tret'][2], 1e-9);
        self::assertEqualsWithDelta(2451580.117957648, $result['tret'][3], 1e-9);
        self::assertEqualsWithDelta(0.7425117182492289, $result['attr'][0], 1e-12);
        self::assertEqualsWithDelta(0.6570883694204279, $result['attr'][2], 1e-12);
    }

    public function testSolarWhenGlobFiltersBackwardAnnularEclipses(): void
    {
        $result = Eclipse::solarWhenGlob(
            2460400.0,
            Catalog::SEFLG_DEFAULTEPH,
            Catalog::SE_ECL_ANNULAR,
            true
        );

        self::assertSame(Catalog::SE_ECL_CENTRAL | Catalog::SE_ECL_ANNULAR, $result['rc']);
        self::assertSame('', $result['error']);
        self::assertEqualsWithDelta(2460232.210229274, $result['tret'][0], 1e-9);
    }

    public function testSolarWhenGlobRejectsImpossibleCentralPartialType(): void
    {
        $result = Eclipse::solarWhenGlob(
            2460409.0,
            Catalog::SEFLG_DEFAULTEPH,
            Catalog::SE_ECL_PARTIAL | Catalog::SE_ECL_CENTRAL
        );

        self::assertSame(SwissDate::ERR, $result['rc']);
        self::assertSame(array_fill(0, 10, 0.0), $result['tret']);
        self::assertSame(array_fill(0, 20, 0.0), $result['attr']);
        self::assertSame(array_fill(0, 10, 0.0), $result['dcore']);
        self::assertSame('central partial eclipses do not exist', $result['error']);
    }

    public function testSolarWhenGlobRejectsImpossibleNoncentralHybridType(): void
    {
        $result = Eclipse::solarWhenGlob(
            2460409.0,
            Catalog::SEFLG_DEFAULTEPH,
            Catalog::SE_ECL_ANNULAR_TOTAL | Catalog::SE_ECL_NONCENTRAL
        );

        self::assertSame(SwissDate::ERR, $result['rc']);
        self::assertSame('non-central hybrid (annular-total) eclipses do not exist', $result['error']);
    }

    public function testSolarWhenGlobResultWrapsArrayResult(): void
    {
        $result = Eclipse::solarWhenGlobResult(2460400.0);

        self::assertInstanceOf(SolarEclipseWhenResult::class, $result);
        self::assertTrue($result->isEclipse());
        self::assertTrue($result->isTotal());
        self::assertEqualsWithDelta(2460409.2240756718, $result->maximumTime(), 1e-9);
        self::assertEqualsWithDelta(1.0, $result->magnitude(), 1e-12);
        self::assertEqualsWithDelta(1.0, $result->obscuration(), 1e-12);
        self::assertEqualsWithDelta(2460409.1162655386, $result->partialBeginTime(), 1e-9);
        self::assertEqualsWithDelta(2460409.3317089113, $result->partialEndTime(), 1e-9);
    }

    public function testSolarWhereReturnsBasicGeometry(): void
    {
        $result = Eclipse::solarWhere(2460409.222222222);

        self::assertSame(Catalog::SE_ECL_CENTRAL | Catalog::SE_ECL_TOTAL, $result['rc']);
        self::assertSame('', $result['error']);
        self::assertEqualsWithDelta(-81.98639653728043, $result['geopos'][0], 1e-12);
        self::assertEqualsWithDelta(19.984901425093963, $result['geopos'][1], 1e-12);
        self::assertEqualsWithDelta(1.0, $result['attr'][0], 1e-12);
        self::assertEqualsWithDelta(1.057079344450735, $result['attr'][1], 1e-12);
        self::assertEqualsWithDelta(1.0, $result['attr'][2], 1e-12);
        self::assertEqualsWithDelta(-188.1032147468757, $result['attr'][3], 1e-9);
        self::assertEqualsWithDelta(0.00042287745690826735, $result['attr'][7], 1e-12);
        self::assertEqualsWithDelta(-188.1032147468757, $result['dcore'][0], 1e-9);
        self::assertEqualsWithDelta(6780.885492356764, $result['dcore'][1], 1e-9);
    }

    public function testSolarWhereReturnsNoEclipseShape(): void
    {
        $result = Eclipse::solarWhere(2451545.0);

        self::assertSame(0, $result['rc']);
        self::assertSame(array_fill(0, 10, 0.0), $result['geopos']);
        self::assertSame(array_fill(0, 20, 0.0), $result['attr']);
        self::assertSame(array_fill(0, 10, 0.0), $result['dcore']);
        self::assertSame('no solar eclipse at tjd = 2451545.000000', $result['error']);
    }

    public function testSolarWhereResultWrapsArrayResult(): void
    {
        $result = Eclipse::solarWhereResult(2460409.222222222);

        self::assertInstanceOf(SolarEclipseResult::class, $result);
        self::assertTrue($result->isEclipse());
        self::assertTrue($result->isTotal());
        self::assertEqualsWithDelta(-188.1032147468757, $result->coreShadowDiameterKm(), 1e-9);
        self::assertEqualsWithDelta(-81.98639653728043, $result->geographicLongitude(), 1e-12);
        self::assertEqualsWithDelta(19.984901425093963, $result->geographicLatitude(), 1e-12);
    }

    public function testSolarHowReturnsBasicGeometry(): void
    {
        $result = Eclipse::solarHow(
            2460409.224305555,
            new Observer(-104.9903, 39.7392, 1609.0)
        );

        self::assertSame(Catalog::SE_ECL_PARTIAL, $result['rc']);
        self::assertSame('', $result['error']);
        self::assertSame(array_fill(0, 10, 0.0), $result['dcore']);
        self::assertEqualsWithDelta(0.20984960298279676, $result['attr'][0], 1e-12);
        self::assertEqualsWithDelta(1.0524028059918422, $result['attr'][1], 1e-12);
        self::assertEqualsWithDelta(0.11319702966267742, $result['attr'][2], 1e-12);
        self::assertEqualsWithDelta(319.4585096022168, $result['attr'][4], 1e-12);
        self::assertEqualsWithDelta(51.038560775196515, $result['attr'][5], 1e-12);
        self::assertEqualsWithDelta(51.04915968756047, $result['attr'][6], 1e-12);
        self::assertEqualsWithDelta(0.43458425674577317, $result['attr'][7], 1e-12);
        self::assertEqualsWithDelta($result['attr'][0], $result['attr'][8], 1e-15);
        self::assertSame(-99999999.0, $result['attr'][9]);
        self::assertSame(-99999999.0, $result['attr'][10]);
    }

    public function testSolarHowDetectsTotalSolarEclipseGeometry(): void
    {
        $result = Eclipse::solarHow(
            2460409.222222222,
            new Observer(-82.0, 20.0, 0.0)
        );

        self::assertSame(Catalog::SE_ECL_TOTAL, $result['rc']);
        self::assertSame('', $result['error']);
        self::assertEqualsWithDelta(1.0, $result['attr'][0], 1e-12);
        self::assertEqualsWithDelta(1.0570773811676644, $result['attr'][1], 1e-12);
        self::assertEqualsWithDelta(1.0, $result['attr'][2], 1e-12);
        self::assertEqualsWithDelta(0.00036379674764709755, $result['attr'][7], 1e-12);
        self::assertEqualsWithDelta($result['attr'][1], $result['attr'][8], 1e-15);
    }

    public function testSolarHowDetectsAnnularSolarEclipseGeometry(): void
    {
        $result = Eclipse::solarHow(
            2460232.200173611,
            new Observer(-79.15, 33.07, 0.0)
        );

        self::assertSame(Catalog::SE_ECL_ANNULAR, $result['rc']);
        self::assertSame('', $result['error']);
        self::assertEqualsWithDelta(0.9756893403481246, $result['attr'][0], 1e-12);
        self::assertEqualsWithDelta(0.9516066314584308, $result['attr'][1], 1e-12);
        self::assertEqualsWithDelta(0.9055564213989262, $result['attr'][2], 1e-12);
        self::assertEqualsWithDelta(0.0000610910691333284, $result['attr'][7], 1e-12);
        self::assertEqualsWithDelta($result['attr'][0], $result['attr'][8], 1e-15);
    }

    public function testSolarHowResultWrapsArrayResult(): void
    {
        $result = Eclipse::solarHowResult(
            2460409.224305555,
            new Observer(-104.9903, 39.7392, 1609.0)
        );

        self::assertSame('', $result->result->error);
        self::assertTrue($result->isEclipse());
        self::assertTrue($result->isPartial());
        self::assertEqualsWithDelta(51.04915968756047, $result->sunApparentAltitude(), 1e-12);
        self::assertEqualsWithDelta(0.43458425674577317, $result->elongation(), 1e-12);
    }

    public function testSolarHowRejectsInvalidObserverAltitude(): void
    {
        $result = Eclipse::solarHow(
            2460409.25,
            new Observer(-104.9903, 39.7392, 30000.0)
        );

        self::assertSame(SwissDate::ERR, $result['rc']);
        self::assertSame(array_fill(0, 20, 0.0), $result['attr']);
        self::assertSame(array_fill(0, 10, 0.0), $result['dcore']);
        self::assertSame(
            'location for eclipses must be between -500 and 25000 m above sea',
            $result['error']
        );
    }
}